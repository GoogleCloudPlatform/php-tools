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
     * @param int $retries The number of retry
     * @param bool $catchAllExceptions Indicates whether or not catch all the
     *             exceptions other than the test failure.
     */
    private function runEventuallyConsistentTest(
        callable $func,
        $retries = null,
        $catchAllExceptions = null
    ) {
        if (is_null($retries)) {
            $retries = $this->eventuallyConsistentRetryCount;
        }
        if (is_null($catchAllExceptions)) {
            $catchAllExceptions = $this->catchAllExceptions;
        }
        $attempts = 0;
        while ($attempts <= $retries) {
            try {
                $func();
                return;
            } catch (
                \PHPUnit\Framework\ExpectationFailedException $testException) {
                sleep(pow(2, ++$attempts));
            } catch (\Exception $testException) {
                if ($catchAllExceptions) {
                    sleep(pow(2, ++$attempts));
                } else {
                    throw $testException;
                }
            }
        }
        throw $testException;
    }
}
