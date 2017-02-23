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
    /* @var int The number of retries for eventually consistent tests. You may
     * override this value in your concrete class. */
    protected $eventuallyConsistentRetryCount = 3;

    private function runEventuallyConsistentTest(
        callable $func,
        $retries = null
    ) {
        if (is_null($retries)) {
            $retries = $this->eventuallyConsistentRetryCount;
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
