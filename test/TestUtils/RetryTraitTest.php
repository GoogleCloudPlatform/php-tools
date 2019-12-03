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

use Google\Cloud\TestUtils\RetryTrait;
use Exception;

/**
 * Class RetryTraitTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing RetryTrait.
 *
 * @retry 2
 */
class RetryTraitTest extends \PHPUnit_Framework_TestCase
{
    use RetryTrait;

    private static $timesCalled = 0;

    public function testClassRetries()
    {
        $this->assertEquals(2, $this->getNumberOfRetries());
    }

    /**
     * @retry 3
     */
    public function testMethodRetries()
    {
        $this->assertEquals(3, $this->getNumberOfRetries());
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage The @retry annotation requires a positive integer as an argument
     */
    public function testNoArgumentToRetryAnnotation()
    {
        $this->validateRetries('');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage The @retry annotation must be an integer but got "'foo'"
     */
    public function testInvalidStringArgumentTypeToRetryAnnotation()
    {
        $this->validateRetries('foo');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage The @retry annotation must be an integer but got "1.2"
     */
    public function testInvalidFloatArgumentTypeToRetryAnnotation()
    {
        $this->validateRetries('1.2');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage The $retries must be greater than 0 but got "0"
     */
    public function testNonPositiveIntegerToRetryAnnotation()
    {
        $this->validateRetries(0);
    }

    public function testValidRetryAnnotations()
    {
        $this->assertEquals(1, $this->validateRetries(1));
        $this->assertEquals(1, $this->validateRetries('1'));
        $this->assertEquals(1, $this->validateRetries(1.0));
        $this->assertEquals(1, $this->validateRetries('1.0'));
    }

    /**
     * @retry 3
     */
    public function testRetriesOnException()
    {
        self::$timesCalled++;
        $numRetries = $this->getNumberOfRetries();
        if (self::$timesCalled < $numRetries) {
            throw new Exception('Intentional Exception');
        }
        $this->assertEquals($numRetries, self::$timesCalled);
    }
}
