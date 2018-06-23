<?php
/**
 * Copyright 2018 Google Inc.
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

use Symfony\Component\Console\Tester\CommandTester;

trait ExecuteCommandTrait
{
    use ExponentialBackoffTrait;

    private function runCommand($commandName, $args=[])
    {
        if (!isset(self::$commandFile) || !file_exists(self::$commandFile)) {
            throw new \LogicException('$commandFile is not set or is missing.');
        }
        $application = require self::$commandFile;
        $command = $application->get($commandName);
        $commandTester = new CommandTester($command);

        $testFunc = function () use ($commandTester, $args) {
            ob_start();
            try {
                $commandTester->execute($args, ['interactive' => false]);
            } finally {
                // Ensure output buffer is clean even if an exception is thrown.
                $output = ob_get_clean();
            }
            return $output;
        };

        if (self::$backoff) {
            return self::$backoff->execute($testFunc);
        }

        return $testFunc();
    }
}
