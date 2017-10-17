<?php
/**
 * Copyright 2017 Google Inc.
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

namespace Google\Cloud\Utils\Test;

use Google\Cloud\Utils\Gcloud;

/**
 * Class GcloudTest
 * @package Google\Cloud\Utils\Test
 *
 * A class for testing Gcloud class.
 */
class GcloudTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        require_once __DIR__ . '/mocked_exec.php';
    }

    public function testGcloudInit()
    {
        \Google\Cloud\Utils\mockExecInit(
            [],
            ['user@example.com', 'my-project'],
            [0, 0],
            ['user@example.com', 'my-project']
        );
        $gcloud = new Gcloud();
    }

    public function testGcloudInitExecFailure()
    {
        \Google\Cloud\Utils\mockExecInit(
            [],
            ['user@example.com', 'my-project'],
            [1, 1],
            ['user@example.com', 'my-project']
        );
        try {
            $gcloud = new Gcloud();
        } catch (\RuntimeException $e) {
            // Just pass, but $e is function level variable and
            // we can assert later outside of this block.
        }
        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertEquals('gcloud failed', $e->getMessage());
    }

    public function testGcloudInitNotAuthenticated()
    {
        \Google\Cloud\Utils\mockExecInit(
            [],
            ['', 'my-project'],
            [0, 0],
            ['', 'my-project']
        );
        try {
            $gcloud = new Gcloud();
        } catch (\RuntimeException $e) {
            // Just pass, but $e is function level variable and
            // we can assert later outside of this block.
        }
        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertEquals('gcloud not authenticated', $e->getMessage());
    }

    public function testGcloudInitProjectNotSet()
    {
        \Google\Cloud\Utils\mockExecInit(
            [],
            ['user@example.com', ''],
            [0, 0],
            ['user@example.com', '']
        );
        try {
            $gcloud = new Gcloud();
        } catch (\RuntimeException $e) {
            // Just pass, but $e is function level variable and
            // we can assert later outside of this block.
        }
        $this->assertInstanceOf(\RuntimeException::class, $e);
        $this->assertEquals(
            'gcloud project configuration not set',
            $e->getMessage()
        );
    }

    public function testGcloudExec()
    {
        \Google\Cloud\Utils\mockExecInit(
            [],
            ['user@example.com', 'my-project', 'output'],
            [0, 0, 0],
            ['user@example.com', 'my-project', 'result']
        );
        $gcloud = new Gcloud();
        list($ret, $output) = $gcloud->exec(['app',  'deploy', 'my dir']);
        $this->assertEquals(0, $ret);
        $this->assertEquals(['output'], $output);
        global $_commands;
        $this->assertEquals(
            "gcloud 'app' 'deploy' 'my dir'", array_pop($_commands)
        );
    }
}
