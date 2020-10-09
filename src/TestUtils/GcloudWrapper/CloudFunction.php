<?php
/**
 * Copyright 2020 Google LLC.
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
 * Class CloudFunction.
 */
class CloudFunction
{
    use GcloudWrapperTrait;

    /** @var string */
    private $functionName;

    /** @var string */
    private $entryPoint;

    /** @var string */
    private $region;

    /** @var string */
    private $runtime;

    /** @var string */
    private $trigger;

    /** @var string */
    private $url;

    /** @var string */
    private $port;

    const GCLOUD_COMPONENT = 'functions';
    const DEFAULT_REGION = 'us-central1';
    const DEFAULT_RUNTIME = 'php74';
    const DEFAULT_TRIGGER = '--trigger-http';
    const DEFAULT_PORT = '8080';

    /**
     * Constructor of CloudFunction.
     *
     * @param string $project
     */
    public function __construct(
        $project,
        $entryPoint,
        array $options = []
    ) {
        $options = array_merge([
            'functionName' => $entryPoint,
            'region' => self::DEFAULT_REGION,
            'runtime' => self::DEFAULT_RUNTIME,
            'trigger' => self::DEFAULT_TRIGGER,
            'port' => self::DEFAULT_PORT,
            'dir' => null,
        ], $options);

        $this->project = $project;
        $this->entryPoint = $entryPoint;

        foreach ($options as $name => $value) {
            $this->$name = $value;
        }

        $this->setDefaultVars($project, $options['dir']);
    }

    /**
     * Deploy the app to the Google Cloud Platform using App Engine.
     *
     * @param array $options list of options
     *                       $retries int Number of retries upon failure.
     *                       $release_version string Run using "alpha" or "beta" version of gcloud deploy
     *
     * @return bool true if deployment suceeds, false upon failure
     */
    public function deploy($options = [])
    {
        if ($this->deployed) {
            $this->errorLog('The function has already been deployed.');

            // If we've already deployed, assume the function is ready for use.
            return true;
        }
        // Handle old function signature
        if (!is_array($options)) {
            $options = array_filter([
                'retries' => @func_get_arg(1),
            ], 'is_numeric');
        }
        $options = array_merge([
            'retries' => 3,
            'release_version' => null,
        ], $options);
        if (!in_array($options['release_version'], [null, 'alpha', 'beta'])) {
            $this->errorLog('release_version must be "alpha" or "beta"');

            return false;
        }
        $orgDir = getcwd();
        if (chdir($this->dir) === false) {
            $this->errorLog('Can not chdir to ' . $this->dir);

            return false;
        }
        $cmd = sprintf(
            'gcloud -q %s%s deploy %s --entry-point %s --runtime %s --project %s --region %s %s --no-allow-unauthenticated',
            $options['release_version'] ? $options['release_version'] . ' ' : '',
            self::GCLOUD_COMPONENT,
            $this->functionName,
            $this->entryPoint,
            $this->runtime,
            $this->project,
            $this->region,
            $this->trigger,
        );
        $ret = $this->execWithRetry($cmd, $options['retries']);
        chdir($orgDir);
        if ($ret) {
            $this->deployed = true;
        }

        return $ret;
    }

    /**
     * Run the function with the php development server.
     *
     * @return bool true if the app is running, otherwise false
     */
    public function run($phpBin = 'php')
    {
        $type = $this->isCloudEventFunction() ? 'FUNCTION_SIGNATURE_TYPE=cloudevent' : '';
        $cmd = $type . 'FUNCTION_TARGET=' . $this->functionName . ' ' . $phpBin . ' -S localhost:' . $this->port . ' vendor/bin/router.php';
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
            $this->errorLog('php server failed to run.');
            $this->errorLog($this->process->getErrorOutput());

            return false;
        }
        $this->isRunning = true;

        return true;
    }

    /**
     * Stop the php development server.
     */
    public function stop()
    {
        if ($this->process->isRunning()) {
            $this->process->stop();
        }
        $this->isRunning = false;
    }

    /**
     * Delete the deployed function.
     *
     * @param int $retries optional The number of retries upon failure
     *
     * @return bool true if the function is succesfully deleted, otherwise false
     */
    public function delete($retries = 3)
    {
        if (!$this->deployed) {
            $this->errorLog('Nothing to delete: function not deployed.');

            return false;
        }

        $cmd = 'gcloud -q ' . self::GCLOUD_COMPONENT . ' delete '
            . $this->functionName . ' --project ' . $this->project . ' --region ' . $this->region;
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
     *               the app is not running
     */
    public function getLocalBaseUrl()
    {
        if (!$this->isRunning) {
            $this->errorLog('The function is not running.');

            return false;
        }

        return 'http://localhost:' . $this->port;
    }

    /**
     * Return the base URL of the deployed function.
     *
     * @return mixed returns the base URL of the deployed function, or false when
     *               the function is not deployed or not HTTP triggered
     */
    public function getBaseUrl($retries = 3)
    {
        if (!$this->deployed) {
            echo '$this->deployed is empty by the time getBaseUrl is called.' . PHP_EOL;
            $this->errorLog('The function has not been deployed.');

            return false;
        }

        if ($this->isCloudEventFunction()) {
            $this->errorLog('The function is deployed as a CloudEvent Function.');

            return false;
        }

        if (empty($this->url)) {
            $cmd = 'gcloud -q ' . self::GCLOUD_COMPONENT . ' describe ' . $this->functionName
            . ' --format \'value(httpsTrigger.url)\' --project ' . $this->project . ' --region ' . $this->region;
            $this->url = $this->execWithRetry($cmd, $retries, $url);
            $this->url = $url[0];
        }

        return $this->url;
    }

    // Returns true if the function is a CloudEvent function.
    private function isCloudEventFunction()
    {
        // Default trigger is an http trigger. If that's not in use, assume an event trigger.
        return  $this->trigger != self::DEFAULT_TRIGGER;
    }
}
