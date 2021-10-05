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

use Google\Cloud\TestUtils\CloudSqlTestTrait;

/**
 * Class ExecuteCommandTraitTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing ExecuteCommandTrait.
 */
class CloudSqlTestTraitTest extends \PHPUnit_Framework_TestCase
{
    use CloudSqlTestTrait;

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

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Unable to create socket dir /this/is/invalid
     */
    public function testInvalidSocketDirThrowsException()
    {
        $connectionString = '';
        $socketDir = '/this/is/invalid';
        $this->startCloudSqlProxy($connectionString, $socketDir);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Failed to start cloud_sql_proxy
     */
    public function testFailedRunThrowsException()
    {
        $connectionString = '';
        $port = 'invalid port';
        $socketDir = '/tmp/cloudsql';
        $this->startCloudSqlProxy($connectionString, $socketDir, $port);
    }
}
