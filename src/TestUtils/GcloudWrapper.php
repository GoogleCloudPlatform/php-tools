<?php
/**
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\TestUtils;

use Symfony\Component\Process\Process;

/**
 * Class GcloudWrapper
 * @package Google\Cloud\TestUtils
 *
 * A class representing App Engine application.
 */
class GcloudWrapper
{
    /** @var string */
    private $project;

    /** @var string */
    private $version;

    /** @var string */
    private $port;

    /** @var bool */
    private $deployed;

    /** @var bool */
    private $isRunning;

    /** @var \Symfony\Component\Process\Process */
    private $process;

    /** @var string */
    private $dir;

    const DEFAULT_RETRIES = 3;
    const GCLOUD_APP = 'app';
    const DEFAULT_PORT = 8080;

    private function errorLog($message)
    {
        fwrite(STDERR, $message . "\n");
    }

    protected function execWithRetry($cmd, $retries = self::DEFAULT_RETRIES)
    {
        for ($i = 0; $i <= $retries; $i++) {
            exec($cmd, $output, $ret);
            if ($ret === 0) {
                return true;
            } elseif ($i <= $retries) {
                $this->errorLog('Retrying the command: ' . $cmd);
            }
        }
        return false;
    }

    /**
     * Constructor of GcloudWrapper.
     *
     * @param string $project
     * @param string $version
     * @param string|null $dir optional
     * @param int $port optional
     */
    public function __construct(
        $project,
        $version,
        $dir = null,
        $port = self::DEFAULT_PORT
    ) {
        $this->project = $project;
        if ($version === null) {
            $version = uniqid('gaeapp-');
        }
        if ($dir === null) {
            $dir = getcwd();
        }
        $this->version = $version;
        $this->deployed = false;
        $this->isRunning = false;
        $this->dir = $dir;
        $this->port = $port;
    }

    /**
     * A setter for $dir, it's handy for using different directory for the
     * test.
     *
     * @param string $dir
     */
    public function setDir($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Deploy the app to the Google Cloud Platform.
     *
     * @param string $targets optional The yaml files for deployments.
     * @param bool $promote optional true if you want to promote the new app.
     * @param int $retries optional Number of retries upon failure.
     * @return bool true if deployment suceeds, false upon failure.
     */
    public function deploy(
        $targets = 'app.yaml',
        $promote = false,
        $retries = self::DEFAULT_RETRIES
    ) {
        if ($this->deployed) {
            $this->errorLog('The app has been already deployed.');
            return false;
        }
        $orgDir = getcwd();
        if (chdir($this->dir) === false) {
            $this->errorLog('Can not chdir to ' . $this->dir);
            return false;
        }
        $cmd = "gcloud -q " . self::GCLOUD_APP . " deploy "
            . "--project " . $this->project . " "
            . "--version " . $this->version . " ";
        if ($promote) {
            $cmd .= "--promote ";
        } else {
            $cmd .= "--no-promote ";
        }
        $cmd .= $targets;
        $ret = $this->execWithRetry($cmd, $retries);
        chdir($orgDir);
        if ($ret) {
            $this->deployed = true;
        }
        return $ret;
    }

    /**
     * Create \Symfony\Component\Process\Process with a given string.
     *
     * @param string $cmd
     * @return \Symfony\Component\Process\Process
     */
    protected function createProcess($cmd)
    {
        return new Process($cmd);
    }

    /**
     * Run the app with dev_appserver.
     *
     * @param string $targets optional The yaml files for local run.
     * @param string $phpCgiPath optional The path to php-cgi.
     * @return bool true if the app is running, otherwise false.
     */
    public function run(
        $targets = 'app.yaml',
        $phpCgiPath = '/usr/bin/php-cgi'
    ) {
        $cmd = 'exec dev_appserver.py --port ' . $this->port
            . ' --skip_sdk_update_check true'
            . ' --php_executable_path ' . $phpCgiPath
            . ' ' . $targets;
        $orgDir = getcwd();
        if (chdir($this->dir) === false) {
            $this->errorLog('Can not chdir to ' . $this->dir);
            return false;
        }
        $this->process = $this->createProcess($cmd);
        $this->process->start();
        chdir($orgDir);
        sleep(3);
        if (! $this->process->isRunning()) {
            $this->errorLog('dev_appserver failed to run.');
            $this->errorLog($this->process->getErrorOutput());
            return false;
        }
        $this->isRunning = true;
        return true;
    }

    /**
     * Stop the dev_appserver.
     */
    public function stop()
    {
        if ($this->process->isRunning()) {
            $this->process->stop();
        }
        $this->isRunning = false;
    }

    /**
     * Delete the deployed app.
     *
     * @param string $service
     * @param int $retries optional The number of retries upon failure.
     * @return bool true if the app is succesfully deleted, otherwise false
     */
    public function delete(
        $service = 'default',
        $retries = self::DEFAULT_RETRIES
    ) {
        $cmd = "gcloud -q " . self::GCLOUD_APP . " versions delete "
            . "--service " . $service . " "
            . $this->version . " --project " . $this->project;
        $ret = $this->execWithRetry($cmd, $retries);
        if ($ret) {
            $this->deployed = false;
        }
        return $ret;
    }

    /**
     * Return the base URL of the local dev_appserver.
     *
     * @return mixed returns the base URL of the running app, or false when
     *     the app is not running
     */
    public function getLocalBaseUrl()
    {
        if (! $this->isRunning) {
            $this->errorLog('The app is not running.');
            return false;
        }
        return 'http://localhost:' . $this->port;
    }

    /**
     * Return the base URL of the deployed app.
     *
     * @param string $service optional
     * @return mixed returns the base URL of the deployed app, or false when
     *     the app is not deployed.
     */
    public function getBaseUrl($service = 'default')
    {
        if (! $this->deployed) {
            $this->errorLog('The app has not been deployed.');
            return false;
        }
        if ($service === 'default') {
            $url = sprintf(
                'https://%s-dot-%s.appspot.com',
                $this->version,
                $this->project
            );
        } else {
            $url = sprintf(
                'https://%s-dot-%s-dot-%s.appspot.com',
                $this->version,
                $service,
                $this->project
            );
        }
        return $url;
    }
}
