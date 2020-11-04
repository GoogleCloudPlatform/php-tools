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

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Trait GcloudWrapperTrait.
 */
trait GcloudWrapperTrait
{
    /** @var string */
    private $project;

    /** @var bool */
    private $deployed;

    /** @var bool */
    private $isRunning;

    /** @var Process */
    private $process;

    /** @var string */
    private $dir;

    /**
     * Set trait properties to default values.
     *
     * @param string      $project
     * @param string|null $dir     optional
     */
    private function setDefaultVars(
        $project,
        $dir = null
    ) {
        $this->project = $project;
        if (empty($dir)) {
            $dir = getcwd();
        }
        $this->deployed = false;
        $this->isRunning = false;
        $this->dir = $dir;
    }

    private function errorLog($message)
    {
        fwrite(STDERR, $message . "\n");
    }

    protected function execWithRetry($cmd, $retries = 3, &$output = null)
    {
        for ($i = 0; $i <= $retries; ++$i) {
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
     * Retry the provided process and return the output.
     *
     * @param Symfony\Component\Process\Process $cmd
     * @param int $retries is the number of retry attempts to make.
     *
     * @return string
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    protected function runWithRetry(Process $cmd, $retries = 3)
    {
        $this->errorLog('Running: ' . str_replace("'", "", $cmd->getCommandLine()));
        for ($i = 0; $i <= $retries; ++$i) {
            // TODO: Use ExponentialBackoffTrait for more sophisticated handling.
            // Simple geometric backoff, .25 seconds * iteration.
            usleep(250000 * $i);

            $cmd->run();
            if ($cmd->isSuccessful()) {
                return $cmd->getOutput();
            } elseif ($i < $retries) {
                $this->errorLog('Retry Attempt #' . ($i+1));
                $cmd->clearOutput();
                $cmd->clearErrorOutput();
            }
        }

        throw new ProcessFailedException($cmd);
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
     * Create \Symfony\Component\Process\Process with a given string.
     *
     * @param string $cmd
     *
     * @return Process
     */
    protected function createProcess($cmd, $dir = null, array $env = [])
    {
        return new Process(explode(' ', $cmd), $dir, $env);
    }

    /**
     * Stop the process.
     */
    public function stop()
    {
        if ($this->process->isRunning()) {
            $this->process->stop();
        }
        $this->isRunning = false;
    }
}
