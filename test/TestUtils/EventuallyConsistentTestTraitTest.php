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

namespace Google\Cloud\TestUtils\test;

use Google\Cloud\TestUtils\EventuallyConsistentTestTrait;

/**
 * Class EventuallyConsistentTestTrait
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing EventuallyConsistentTestTrait.
 */
class EventuallyConsistentTestTraitTest extends \PHPUnit_Framework_TestCase
{
    use EventuallyConsistentTestTrait;

    public function setUp()
    {
        // Setting the default value. Each test should set it again in the
        // test itself if necessary.
        $this->catchAllExceptions = false;
    }

    public function testRunEventuallyConsistentTest()
    {
        $retries = 4;
        $i = 0;
        $func = function () use (&$i, $retries) {
            if (++$i == $retries) {
                // return on the final retry
                return;
            }
            $this->assertTrue(false);
        };
        $this->runEventuallyConsistentTest($func, $retries);
        $this->assertEquals($i, $retries);
    }

    public function testCatchAllExceptionsTest()
    {
        $retries = 4;
        $i = 0;
        $func = function () use (&$i, $retries) {
            if (++$i == $retries) {
                // return on the final retry
                return;
            }
            throw new \Exception('Something goes wrong');
        };
        $this->runEventuallyConsistentTest($func, $retries, true);
        $this->assertEquals($i, $retries);
    }

    public function testCatchAllExceptionsWithInstanceVarTest()
    {
        $retries = 4;
        $i = 0;
        $func = function () use (&$i, $retries) {
            if (++$i == $retries) {
                // return on the final retry
                return;
            }
            throw new \Exception('Something goes wrong');
        };
        $this->catchAllExceptions = true;
        $this->runEventuallyConsistentTest($func, $retries);
        $this->assertEquals($i, $retries);
    }
    /**
     * @expectedException \Exception
     */
    public function testNoCatchAllExceptionsTest()
    {
        $retries = 4;
        $i = 0;
        $func = function () use (&$i, $retries) {
            if (++$i == $retries) {
                // return on the final retry
                return;
            }
            throw new \Exception('Something goes wrong');
        };
        $this->runEventuallyConsistentTest($func, $retries);
    }
}
