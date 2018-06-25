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

namespace Google\Cloud\TestUtils\test;

use Google\Cloud\TestUtils\ExecuteCommandTrait;

/**
 * Class ExecuteCommandTraitTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing ExecuteCommandTrait.
 */
class ExecuteCommandTraitTest extends \PHPUnit_Framework_TestCase
{
    use ExecuteCommandTrait;

    private static $commandFile = __DIR__ . '/command.php';
    private static $callCount = 0;

    public function testRunCommand()
    {
        $output = $this->runCommand('test');
        $this->assertEquals("foo: , bar: ", $output);

        $output = $this->runCommand('test', ['foo' => 'yay', '--bar' => 'baz']);
        $this->assertEquals("foo: yay, bar: baz", $output);
    }

    /** @expectedException Exception */
    public function testRunCommandWithoutBackoffThrowsException()
    {
        $this->runCommand('test', ['--exception' => true]);
    }

    /** @runInSeparateProcess */
    public function testRunCommandWithBackoff()
    {
        $this->useBackoff($retries = 5);
        self::setDelayFunction(function ($delay) {
            // do nothing!
        });
        try {
            $this->runCommand('test', ['--exception' => true]);
        } catch (\Exception $e) {
        }
        $this->assertEquals($retries + 1, self::$callCount);
    }

    public static function incrementCallCount()
    {
        self::$callCount++;
    }
}
