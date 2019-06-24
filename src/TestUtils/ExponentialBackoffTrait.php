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

use Google\ApiCore\ApiException;
use Google\Cloud\Utils\ExponentialBackoff;
use Google\Rpc\Code;
use PHPUnit\Framework\ExpectationFailedException;

trait ExponentialBackoffTrait
{
    private static $backoff;

    /* @var int The number of retries for exponential backoff tests. You may
     *      override this value in your concrete class. */
    protected $expontentialBackoffRetryCount = 5;

    private function useResourceExhaustedBackoff($retries = null)
    {
        self::useBackoff($retries, function ($exception) {
            return $exception instanceof ApiException
                && $exception->getCode() == Code::RESOURCE_EXHAUSTED;
        });
    }

    private function useExpectationFailedBackoff($retries = null)
    {
        self::useBackoff($retries, function ($exception) {
            return $exception instanceof ExpectationFailedException;
        });
    }

    private function useDeadlineExceededBackoff($retries = null)
    {
        self::useBackoff($retries, function ($exception) {
            return $exception instanceof ApiException
                && $exception->getCode() == Code::DEADLINE_EXCEEDED;
        });
    }

    private function useBackoff($retries = null, callable $retryFunction = null)
    {
        $backoff = new ExponentialBackoff(
            $retries ?: $this->expontentialBackoffRetryCount,
            $retryFunction
        );

        self::$backoff
            ? self::$backoff->combine($backoff)
            : self::$backoff = $backoff;
    }

    private function setDelayFunction(callable $delayFunction)
    {
        if (is_null(self::$backoff)) {
            throw new \LogicException('You must set self::$backoff first');
        }
        self::$backoff->setDelayFunction($delayFunction);
    }
}
