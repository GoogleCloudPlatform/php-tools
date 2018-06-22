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

if (!class_exists('\PHPUnit\Framework\ExpectationFailedException', true)) {
    class_alias(
        '\PHPUnit_Framework_ExpectationFailedException',
        '\PHPUnit\Framework\ExpectationFailedException'
    );
}

/**
 * Trait EventuallyConsistentTestTrait
 * @package Google\Cloud\TestUtils
 *
 * Use this trait to run a test that may have to wait for eventual consistency.
 */
trait EventuallyConsistentTestTrait
{
    /* @var int The number of retries for eventually consistent tests. You may
     *      override this value in your concrete class. */
    protected $eventuallyConsistentRetryCount = 3;

    /* @var bool Indicates whether or not catch all the exceptions other than
     *      the test failure. You may override this value in your concrete
     *      class. */
    protected $catchAllExceptions = false;

    /**
     * @param callable $func The callable that runs tests
     * @param int $maxAttempts The number of attempts to make total
     * @param bool $catchAllExceptions Indicates whether or not catch all the
     *             exceptions other than the test failure.
     */
    private function runEventuallyConsistentTest(
        callable $func,
        $maxAttempts = null,
        $catchAllExceptions = null
    ) {
        if (is_null($maxAttempts)) {
            $maxAttempts = $this->eventuallyConsistentRetryCount;
        }
        if (is_null($catchAllExceptions)) {
            $catchAllExceptions = $this->catchAllExceptions;
        }
        $attempts = 0;
        while ($attempts < $maxAttempts) {
            try {
                return $func();
            } catch (
                \PHPUnit\Framework\ExpectationFailedException $testException) {
            } catch (\Exception $testException) {
                if (!$catchAllExceptions) {
                    throw $testException;
                }
            }
            // Increment the number of attempts, and if we are going to attempt
            // again, run the sleep function.
            $attempts++;
            if ($attempts < $maxAttempts) {
                $this->retrySleepFunc($attempts);
            }
        }
        throw $testException;
    }

    private function retrySleepFunc($attempts)
    {
        sleep(pow(2, $attempts));
    }
}
