<?php
/**
 * Copyright 2019 Google LLC
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

namespace Google\Cloud\Utils\Test;

use Google\Cloud\Utils\DeadlineExponentialBackoff;
use PHPUnit\Framework\TestCase;

class DeadlineExponentialBackoffTest extends TestCase
{
    private $delayFunction;

    public function setUp()
    {
        $this->delayFunction = function () {
            return;
        };
    }

    public function testInfiniteRetriesDeadline()
    {
        $actualAttempts = 0;
        $backoff = new DeadlineExponentialBackoff(-1);
        $backoff->setDelayFunction($this->delayFunction);
        $backoff->execute(function () use (&$actualAttempts) {
            if ($actualAttempts < 1000) {
                $actualAttempts++;
                throw new \Exception('Intentional exception');
            }
        });

        $this->assertEquals(1000, $actualAttempts);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Intentional exception
     */
    public function testNoRetryFunc()
    {
        $neverRetryFunc = function() {
            return false;
        };
        $actualAttempts = 0;
        $backoff = new DeadlineExponentialBackoff(-1, $neverRetryFunc);
        $backoff->setDelayFunction($this->delayFunction);
        $backoff->execute(function () use (&$actualAttempts) {
            $actualAttempts++;
            throw new \Exception('Intentional exception');
        });
    }

    public function testTwoSecondDeadlineFunction()
    {
        $timeNow = microtime(true);
        $actualAttempts = 0;
        $backoff = new DeadlineExponentialBackoff($timeNow + 2);
        $backoff->setDelayFunction($this->delayFunction);
        try {
            $backoff->execute(function () use (&$actualAttempts) {
                $actualAttempts++;
                throw new \Exception('Intentional exception');
            });
        } catch (\Exception $e) {
            if ($e->getMessage() !== 'Intentional exception') {
                throw $e;
            }
        }

        $this->assertGreaterThan(1, $actualAttempts);
        $this->assertGreaterThan($timeNow + 2, microtime(true));
        $this->assertGreaterThan(microtime(true), $timeNow + 3);
    }
}
