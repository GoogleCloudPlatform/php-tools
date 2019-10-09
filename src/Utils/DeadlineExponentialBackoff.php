<?php
/**
 * Copyright 2019 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Utils;

/**
 * Exponential backoff implementation based off timeout instead of retries.
 * @internal
 */
class DeadlineExponentialBackoff extends ExponentialBackoff
{
    const TIMEOUT_SECONDS_DEFAULT = 60; // one minute

    /**
     * @var float
     */
    private $deadlineMicros;

    /**
     * @param float $deadlineMicros [optional] Duration from first execution to retry in seconds.
     * @param callable $retryFunction [optional] returns bool for whether or not to retry.
     */
    public function __construct($deadlineMicros = null, callable $retryFunction = null)
    {
        if (is_null($deadlineMicros)) {
            $deadlineMicros = microtime(true) + self::TIMEOUT_SECONDS_DEFAULT;
        }
        $this->deadlineMicros = $deadlineMicros;

        // Call retry backoff with -1 for infinite retry count.
        parent::__construct(-1, $retryFunction);
    }

    /**
     * Function which returns bool for whether or not to retry.
     *
     * @param Exception $exception
     * @return bool
     */
    protected function shouldRetry(\Exception $exception)
    {
        // Retry forever when $deadlineMicros is -1
        if (-1 === $this->deadlineMicros || $this->deadlineMicros > microtime(true)){
            if (parent::shouldRetry($exception)) {
                return true;
            }
        }

        return false;
    }
}
