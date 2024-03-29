<?php
/**
 * Copyright 2021 Google Inc.
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

use Google\Cloud\TestUtils\CloudSqlProxyTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ExecuteCommandTraitTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing ExecuteCommandTrait.
 */
class CloudSqlProxyTraitTest extends TestCase
{
    use CloudSqlProxyTrait;

    /**
     * @runInSeparateProcess
     */
    public function testRunCloudSqlProxy()
    {
        $connectionString = '';
        $socketDir = '/tmp/cloudsql';
        $this->startCloudSqlProxy($connectionString, $socketDir);
        $this->assertNotNull(self::$cloudSqlProxyProcess);
        $this->assertTrue(self::$cloudSqlProxyProcess->isRunning());
        $this->stopCloudSqlProxy();
        $this->assertFalse(self::$cloudSqlProxyProcess->isRunning());
    }

    public function testInvalidSocketDirThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to create socket dir /this/is/invalid');
        $connectionString = '';
        $socketDir = '/this/is/invalid';
        $this->startCloudSqlProxy($connectionString, $socketDir);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFailedRunThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to start cloud_sql_proxy');
        $connectionString = 'invalid';
        $socketDir = '/tmp/cloudsql';
        $this->startCloudSqlProxy($connectionString, $socketDir);
    }
}
