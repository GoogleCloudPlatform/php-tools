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

use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Class CloudFunction.
 */
class CloudFunction
{
    use GcloudWrapperTrait;

    /** @var string */
    private $projectId;

    /** @var string */
    private $region;

    /** @var string */
    private $entryPoint;

    /** @var string */
    private $functionName;

    /** @var string */
    private $functionSignatureType;

    /** @var string */
    private $url;

    /** @var string */
    private $localUri;

    const GCLOUD_COMPONENT = 'functions';
    const DEFAULT_PORT = '8080';
    const DEFAULT_REGION = 'us-central1';
    const DEFAULT_RUNTIME = 'php74';
    const DEFAULT_TRIGGER = '--trigger-http';
    const DEFAULT_TIMEOUT_SECONDS = 300; // 5 minutes

    /**
     * Constructor of CloudFunction.
     *
     * @param string $project
     * @param string $entryPoint
     * @param string $functionSignatureType
     * @param string $region
     * @param string $dir
     */
    public function __construct(
        string $projectId,
        string $entryPoint,
        string $functionSignatureType,
        string $region,
        string $dir = null
    ) {
        $this->projectId = $projectId;
        $this->entryPoint = $entryPoint;
        $this->functionSignatureType = $functionSignatureType ?: 'http';
        $this->region = $region ?: self::DEFAULT_REGION;

        // Validate properties.
        foreach (['projectId', 'entryPoint', 'functionSignatureType', 'region'] as $required) {
            if (empty($this->$required)) {
                throw new \InvalidArgumentException('Missing required property: ' . $required);
            }
        }
        $typeOptions = ['http', 'cloudevent'];
        if (!in_array($this->functionSignatureType, $typeOptions)) {
            throw new \InvalidArgumentException('Function Signature Type must be one of: ' . join(', ', $typeOptions));
        }

        // Initialize derived and internal state properties.
        $this->functionName = $this->getFunctionName();
        $this->setDefaultVars($projectId, $dir);
    }

    public static function fromArray(array $arr)
    {
        $args = [];
        $argKeys = [
            'projectId',
            'entryPoint',
            'functionSignatureType',
            'region',
            'dir',
        ];

        foreach ($argKeys as $key) {
            $args[] = $arr[$key] ?? '';
        }

        return new static(...$args);
    }

    /**
     * Retrieve the function name.
     */
    public function getFunctionName()
    {
        if (empty($this->functionName)) {
            $id = getenv('GOOGLE_VERSION_ID') ?: uniqid();
            $this->functionName = $this->entryPoint . '-' . $id;
        }
        return $this->functionName;
    }

    /**
     * Deploy the app to the Google Cloud Platform using App Engine.
     *
     * To set custom deploy flags, call deploy like so:
     *
     *     CloudFunction::deploy(['--update-env-vars' => 'FOO_VAR=BAR'])
     *
     * @param array $flags optional flags to inject into the deploy command
     * @param string $trigger defines the full trigger flag for the function.
     * @param int $retries
     * @param string|null $channel gcloud release version.
     *
     * @return bool true if deployment suceeds, false upon failure
     */
    public function deploy(array $flags = [], string $trigger = self::DEFAULT_TRIGGER, int $retries = 3, string $channel = null)
    {
        if ($this->deployed) {
            $this->errorLog('The function has already been deployed.');

            // If we've already deployed, assume the function is ready for use.
            return true;
        }

        // Prepare deploy command.
        $flags = array_merge([
            '--runtime' => self::DEFAULT_RUNTIME,
        ], $flags);

        $flattenedFlags = [];
        foreach ($flags as $name => $value) {
            $flattenedFlags[] = empty($value) ? $name : "$name=$value";
        }
        $args = array_merge([
            'deploy',
            $this->functionName,
            '--entry-point=' . $this->entryPoint,
            $trigger,
            '--no-allow-unauthenticated',
        ], $flattenedFlags);

        $cmd = $this->gcloudCommand($args, $channel);
        $cmd->setTimeout(self::DEFAULT_TIMEOUT_SECONDS);

        // Run deploy command.
        try {
            $this->runWithRetry($cmd, $retries);
        } catch (ProcessFailedException $e) {
            $this->errorLog($e->getMessage());
            return false;
        }

        $this->deployed = true;
        return true;
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
            $this->errorLog('Nothing to delete: function not deployed during test');

            return false;
        }

        try {
            $cmd = $this->gcloudCommand(['delete', $this->functionName]);
            $this->runWithRetry($cmd, $retries);
            $this->deployed = false;
        } catch (ProcessFailedException $e) {
            $this->errorLog($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Return the base URL of the deployed function.
     *
     * @param bool $force if true will proceed even if not deployed.
     * @param int $retries number of retries to attempt.
     * @return string returns the base URL of the deployed function, empty string for a CloudEvent function.
     * @throws \RuntimeException
     */
    public function getBaseUrl($force = false, $retries = 3)
    {
        if ($this->functionSignatureType !== 'http') {
            return '';
        }

        if (!$this->deployed && !$force) {
            throw new \RuntimeException('The function has not been deployed.');
        }

        if (empty($this->url)) {
            $cmd = $this->gcloudCommand(['describe', $this->functionName, '--format=value(httpsTrigger.url)']);
            $this->url = trim($this->runWithRetry($cmd, $retries));
        }

        return $this->url;
    }

    /**
     * Creates a gcloud process using the array of arguments as the "core command".
     *
     * @param array $args
     * @return \Symfony\Component\Process\Process
     */
    private function gcloudCommand(array $args, string $channel = null)
    {
        if (!in_array($channel, [null, 'alpha', 'beta'])) {
            $this->errorLog('gcloud channel must use product (null), "alpha" or "beta". Defaulting to production.');

            return false;
        }

        // Append current command arguments to the "permanent" arguments.
        $args = array_merge([
            'gcloud',
            $channel,
            '-q',
            self::GCLOUD_COMPONENT,
        ], $args, [
            '--project', $this->projectId,
            # Region is needed in most commands but is not global to component.
            '--region', $this->region,
        ]);

        // Strip empty values such as null channel.
        $args = array_filter($args);
        return new Process($args, $this->dir);
    }

    /**
     * Run the function with the php development server.
     *
     * @param array $env environment variables in the form "[FOO] => bar"
     * @param string $port override for local PHP server.
     * @param string $phpBin override for PHP CLI path.
     * @return \Symfony\Component\Process\Process returns the php server process
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function run(array $env = [], string $port = self::DEFAULT_PORT, string $phpBin = null)
    {
        $this->localUri = 'localhost:' . $port;

        $phpBin = $phpBin ?? (new PhpExecutableFinder())->find();
        $cmd = $phpBin . ' -S ' . $this->localUri . ' vendor/bin/router.php';

        $this->process = $this->createProcess($cmd, $this->dir, array_merge($env, [
            'FUNCTION_TARGET' => $this->entryPoint,
            'FUNCTION_SIGNATURE_TYPE' => $this->functionSignatureType,
        ]));
        $this->process->setTimeout(self::DEFAULT_TIMEOUT_SECONDS);
        $this->process->start();

        // Typically needs less than 1 second to be ready to serve requests.
        // TODO: Switch to a healthcheck mechanism.
        sleep(1);

        // Verify the server is running.
        if (!$this->process->isRunning()) {
            throw new ProcessFailedException($this->process);
        }

        // Allow the caller to directly check on process output.
        return $this->process;
    }

    /**
     * Run a CloudEvent function with the PHP development server.
     *
     * @return \Symfony\Component\Process\Process returns the php server process
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function runCloudEventFunction(string $port = self::DEFAULT_PORT, string $phpBin = null)
    {
        return $this->run(true, $port, $phpBin);
    }

    /**
     * Return the base URL of the local dev_appserver.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function getLocalBaseUrl()
    {
        if (!$this->process->isRunning()) {
            throw new \RuntimeException('PHP server is not running');
        }

        return 'http://' . $this->localUri;
    }
}
