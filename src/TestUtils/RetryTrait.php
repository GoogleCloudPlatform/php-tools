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

namespace Google\Cloud\TestUtils;

use LogicException;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\Framework\SkippedTestError;
use Throwable;
use Exception;
use Error;

/**
 * Trait for adding a @retry annotation to retry flakey tests.
 *
 * @see https://blog.forma-pro.com/retry-an-erratic-test-fc4d928c57fb
 */
trait RetryTrait
{
    public function runBare()
    {
        $e = null;
        $retries = $this->getNumberOfRetries();
        for ($i = 0; $i < $retries; ++$i) {
            if ($i > 0) {
                printf('[RETRY] Attempt %s of %s' . PHP_EOL,
                    $i + 1,
                    $retries);
            }
            try {
                return parent::runBare();
            } catch (IncompleteTestError $e) {
                throw $e;
            } catch (SkippedTestError $e) {
                throw $e;
            } catch (Throwable $e) {
            } catch (Exception $e) {
            } catch (Error $e) {
            }
        }
        if ($e) {
            throw $e;
        }
    }

    /**
     * @return int
     */
    private function getNumberOfRetries()
    {
        $annotations = $this->getAnnotations();
        $retries = 1;

        if (isset($annotations['class']['retry'][0])) {
            $retries = $annotations['class']['retry'][0];
        }
        if (isset($annotations['method']['retry'][0])) {
            $retries = $annotations['method']['retry'][0];
        }

        return $this->validateRetries($retries);
    }

    private function validateRetries($retries)
    {
        if ('' === $retries) {
            throw new LogicException(
                'The @retry annotation requires a positive integer as an argument'
            );
        }
        if (false === is_numeric($retries)) {
            throw new LogicException(sprintf(
                'The @retry annotation must be an integer but got "%s"',
                var_export($retries, true)
            ));
        }
        if (floatval($retries) != intval($retries)) {
            throw new LogicException(sprintf(
                'The @retry annotation must be an integer but got "%s"',
                floatval($retries)
            ));
        }
        $retries = (int) $retries;
        if ($retries <= 0) {
            throw new LogicException(sprintf(
                'The $retries must be greater than 0 but got "%s".',
                $retries
            ));
        }
        return $retries;
    }
}
