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

/**
 * Trait GcloudWrapperTrait
 * @package Google\Cloud\TestUtils
 *
 * Traits for classes wrapping gcloud
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
     * Constructor of GcloudWrapper.
     *
     * @param string $project
     * @param string|null $dir optional
     */
    private function setDefaultVars(
        $project,
        $dir = null
    ) {
        $this->project = $project;
        if ($dir === null) {
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
     * @return Process
     */
    protected function createProcess($cmd)
    {
        return new Process(explode(' ', $cmd));
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
}
