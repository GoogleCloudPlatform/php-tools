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

namespace Google\Cloud\TestUtils\GcloudWrapper;

/**
 * Class GcloudWrapper
 * @package Google\Cloud\TestUtils
 *
 * A class representing App Engine application.
 */
class AppEngine
{
    use GcloudWrapperTrait;

    /** @var string */
    private $version;

    /** @var string */
    private $port;

    const GCLOUD_APP = 'app';
    const DEFAULT_PORT = 8080;

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
        if ($version === null) {
            $version = uniqid('gaeapp-');
        }
        $this->version = $version;
        $this->port = $port;
        $this->setDefaultVars($project, $dir);
    }

    /**
     * Deploy the app to the Google Cloud Platform using App Engine.
     *
     * @param array $options list of options
     *      $targets string The yaml files for deployments.
     *      $promote bool True if you want to promote the new app.
     *      $retries int Number of retries upon failure.
     *      $release_version string Run using "alpha" or "beta" version of gcloud deploy
     * @return bool true if deployment suceeds, false upon failure.
     */
    public function deploy($options = [])
    {
        // Handle old function signature
        if (!is_array($options)) {
            $options = array_filter([
                'targets' => @func_get_arg(0),
                'promote' => @func_get_arg(1),
            ]) + array_filter([
                'retries' => @func_get_arg(2),
            ], 'is_numeric');
        }
        $options = array_merge([
            'targets' => 'app.yaml',
            'promote' => false,
            'retries' => 3,
            'release_version' => null,
        ], $options);
        if (!in_array($options['release_version'], [null, 'alpha', 'beta'])) {
            $this->errorLog('release_version must be "alpha" or "beta"');
            return false;
        }
        if ($this->deployed) {
            $this->errorLog('The app has been already deployed.');
            return false;
        }
        $orgDir = getcwd();
        if (chdir($this->dir) === false) {
            $this->errorLog('Can not chdir to ' . $this->dir);
            return false;
        }
        $cmd = sprintf('gcloud -q %s%s deploy --project %s --version %s %s %s',
            $options['release_version'] ? $options['release_version'] . ' ' : '',
            self::GCLOUD_APP,
            $this->project,
            $this->version,
            $options['promote'] ? '--promote' : '--no-promote',
            $options['targets']
        );
        $ret = $this->execWithRetry($cmd, $options['retries']);
        chdir($orgDir);
        if ($ret) {
            $this->deployed = true;
        }
        return $ret;
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
        $cmd = 'dev_appserver.py --port ' . $this->port
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
        if (!$this->process->isRunning()) {
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
        $retries = 3
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
        if (!$this->isRunning) {
            $this->errorLog('The app is not running.');
            return false;
        }
        return 'http://127.0.0.1:' . $this->port;
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
        if (!$this->deployed) {
            $this->errorLog('The app has not been deployed.');
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

class_alias(AppEngine::class, \Google\Cloud\TestUtils\GcloudWrapper::class);
