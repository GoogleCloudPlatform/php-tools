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

use Google\ApiCore\ApiException;
use Google\Cloud\TestUtils\ExponentialBackoffTrait;
use Google\Rpc\Code;

/**
 * Class ExponentialBackoffTraitTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing ExponentialBackoffTrait.
 */
class ExponentialBackoffTraitTest extends \PHPUnit_Framework_TestCase
{
    use ExponentialBackoffTrait;

    public function testResourceExhaustedBackoff()
    {
        $this->useResourceExhaustedBackoff($retries = 5);
        $this->runBackoff(function () use (&$timesCalled) {
            $timesCalled++;
            throw new ApiException('Test', Code::RESOURCE_EXHAUSTED, '');
        });
        $this->assertEquals($retries + 1, $timesCalled);
    }

    public function testExpectationFailedBackoff()
    {
        $this->useExpectationFailedBackoff($retries = 5);
        $this->runBackoff(function () use (&$timesCalled) {
            $timesCalled++;
            $this->assertTrue(false);
        });
        $this->assertEquals($retries + 1, $timesCalled);
    }

    public function testExpectationFailedBackoffReturnsValue()
    {
        $this->useExpectationFailedBackoff();
        $retVal = $this->runBackoff(function () {
            return 'foo';
        });
        $this->assertEquals('foo', $retVal);
    }

    public function testRetryCountInstanceVar()
    {
        $this->expontentialBackoffRetryCount = $retries = 10;
        $this->useExpectationFailedBackoff();

        $this->runBackoff(function () use (&$timesCalled) {
            $timesCalled++;
            $this->assertTrue(false);
        });
        $this->assertEquals($retries + 1, $timesCalled);
    }

    public function testDefaultBackoffCatchesAllExceptions()
    {
        $this->useBackoff($retries = 5);
        $this->runBackoff(function () use (&$timesCalled) {
            $timesCalled++;
            throw new \Exception('Something went wrong');
        });
        $this->assertEquals($retries + 1, $timesCalled);
    }

    private function runBackoff(callable $func)
    {
        self::setDelayFunction(function ($delay) {
            // do nothing!
        });
        try {
            return self::$backoff->execute($func);
        } catch (\Exception $e) {
            // var_dump($e->getMessage());
        }
    }
}
