<?php
/*
 * Copyright 2016 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\TestUtils;

use PHPUnit_Framework_ExpectationFailedException;

/**
 * Trait EventuallyConsistentTestTrait
 * @package Google\Cloud\TestUtils
 *
 * Use this trait to run a test that may have to wait for eventual consistency.
 */
trait EventuallyConsistentTestTrait
{
    private function runEventuallyConsistentTest(callable $func, $retries = 0)
    {
        if ($retries <= 0) {
            // It doesn't make sense, we'll use the default value. You can
            // also define the static property $eventuallyConsistentRetryCount in
            // your concrete class then we'll use that value.
            $retries = isset(static::$eventuallyConsistentRetryCount)
                ? static::$eventuallyConsistentRetryCount
                : 3;
        }
        $attempts = 0;
        while ($attempts <= $retries) {
            try {
                $func();
                return;
            } catch (
                PHPUnit_Framework_ExpectationFailedException $testException) {
                sleep(++$attempts * 2);
            }
        }
        throw $testException;
    }
}
