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
use PHPUnit\Framework\TestCase;

/**
 * Class EventuallyConsistentTestTrait
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing EventuallyConsistentTestTrait.
 */
class EventuallyConsistentTestTraitTest extends TestCase
{
    use EventuallyConsistentTestTrait;

    public function setUp(): void
    {
        // Setting the default value. Each test should set it again in the
        // test itself if necessary.
        $this->catchAllExceptions = false;
    }

    public function testRunEventuallyConsistentTest()
    {
        $retries = 4;
        $i = 0;
        $func = function () use (&$i) {
            $i++;
            $this->assertTrue(false);
        };
        try {
            $this->runEventuallyConsistentTest($func, $retries);
        } catch (\Exception $e) {
        }
        $this->assertEquals($retries, $i);
    }

    public function testEventuallyConsistentTestReturnsValue()
    {
        $func = function () {
            return 'foo';
        };
        $retVal = $this->runEventuallyConsistentTest($func);
        $this->assertEquals('foo', $retVal);
    }

    public function testRetryCountInstanceVarTest()
    {
        $retries = 10;
        $i = 0;
        $func = function () use (&$i) {
            $i++;
            $this->assertTrue(false);
        };
        $this->eventuallyConsistentRetryCount = $retries;
        try {
            $this->runEventuallyConsistentTest($func);
        } catch (\Exception $e) {
        }
        $this->assertEquals($retries, $i);
    }

    public function testCatchAllExceptionsTest()
    {
        $retries = 4;
        $i = 0;
        $func = function () use (&$i) {
            $i++;
            throw new \Exception('Something goes wrong');
        };
        try {
            $this->runEventuallyConsistentTest($func, $retries, true);
        } catch (\Exception $e) {
        }
        $this->assertEquals($retries, $i);
    }

    public function testCatchAllExceptionsWithInstanceVarTest()
    {
        $retries = 4;
        $i = 0;
        $func = function () use (&$i) {
            $i++;
            throw new \Exception('Something goes wrong');
        };
        $this->catchAllExceptions = true;
        try {
            $this->runEventuallyConsistentTest($func, $retries);
        } catch (\Exception $e) {
        }
        $this->assertEquals($i, $retries);
    }

    public function testNoCatchAllExceptionsTest()
    {
        $retries = 4;
        $i = 0;
        $func = function () use (&$i) {
            $i++;
            throw new \Exception('Something goes wrong');
        };
        try {
            $this->runEventuallyConsistentTest($func, $retries);
        } catch (\Exception $e) {
        }
        $this->assertEquals(1, $i);
    }
}
