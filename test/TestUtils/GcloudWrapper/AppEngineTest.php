<?php
/**
 * Copyright 2016 Google Inc.
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

use Google\Cloud\TestUtils\GcloudWrapper\AppEngine;
use Symfony\Component\Process\Process;

/**
 * Class GcloudWrapper\AppEngineTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing GcloudAppEngine class.
 */
class AppEngineTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Symfony\Component\Process\Process */
    private $mockProcess;

    public function setUp()
    {
        $this->mockProcess = $this->getMockBuilder(Process::class)
            ->setMethods(array('start', 'isRunning', 'stop'))
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockProcess->method('start')->willReturn(null);
        $this->mockProcess->method('isRunning')->willReturn(true);
        $this->mockProcess->method('stop')->willReturn(null);
    }

    public function testDeployAndDeleteWithDefault()
    {
        $mockGcloudWrapper = $this->getMockBuilder(AppEngine::class)
            ->setMethods(array('execWithRetry'))
            ->setConstructorArgs(array('project', 'version'))
            ->getMock();
        $deployCmd = 'gcloud -q app deploy '
            . '--project project --version version '
            . '--no-promote app.yaml';
        $deleteCmd = 'gcloud -q app versions delete '
            . '--service default version '
            . '--project project';
        $mockGcloudWrapper->expects($this->exactly(2))
            ->method('execWithRetry')
            ->withConsecutive(
                array($this->equalTo($deployCmd), $this->equalTo(3)),
                array($this->equalTo($deleteCmd), $this->equalTo(3))
            )
            ->willReturn(true);

        $mockGcloudWrapper->deploy();

        $this->assertEquals(
            'https://version-dot-project.appspot.com',
            $mockGcloudWrapper->getBaseUrl()
        );

        $mockGcloudWrapper->delete();
    }

    public function testDeployAndDeleteWithCustomArgs()
    {
        $mockGcloudWrapper = $this->getMockBuilder(AppEngine::class)
            ->setMethods(array('execWithRetry'))
            ->setConstructorArgs(array('project', 'version'))
            ->getMock();
        $deployCmd = 'gcloud -q app deploy '
            . '--project project --version version '
            . '--promote app.yaml backend.yaml';
        $deleteCmd = 'gcloud -q app versions delete '
            . '--service myservice version '
            . '--project project';
        $mockGcloudWrapper->expects($this->exactly(2))
            ->method('execWithRetry')
            ->withConsecutive(
                array($this->equalTo($deployCmd), $this->equalTo(4)),
                array($this->equalTo($deleteCmd), $this->equalTo(4))
            )
            ->willReturn(true);

        $mockGcloudWrapper->deploy('app.yaml backend.yaml', true, 4);

        $this->assertEquals(
            'https://version-dot-project.appspot.com',
            $mockGcloudWrapper->getBaseUrl()
        );

        $mockGcloudWrapper->delete('myservice', 4);
    }

    public function testDeployWithArgsArray()
    {
        $mockGcloudWrapper = $this->getMockBuilder(AppEngine::class)
            ->setMethods(array('execWithRetry'))
            ->setConstructorArgs(array('project', 'version'))
            ->getMock();
        $deployCmd = 'gcloud -q beta app deploy '
            . '--project project --version version '
            . '--promote app.yaml backend.yaml';
        $deleteCmd = 'gcloud -q app versions delete '
            . '--service myservice version '
            . '--project project';
        $mockGcloudWrapper->expects($this->exactly(2))
            ->method('execWithRetry')
            ->withConsecutive(
                array($this->equalTo($deployCmd), $this->equalTo(4)),
                array($this->equalTo($deleteCmd), $this->equalTo(4))
            )
            ->willReturn(true);

        $mockGcloudWrapper->deploy([
            'targets' => 'app.yaml backend.yaml',
            'promote' => true,
            'retries' => 4,
            'release_version' => 'beta',
        ]);

        $this->assertEquals(
            'https://version-dot-project.appspot.com',
            $mockGcloudWrapper->getBaseUrl()
        );

        $mockGcloudWrapper->delete('myservice', 4);
    }

    public function testRunAndStopWithDefault()
    {
        $mockGcloudWrapper = $this->getMockBuilder(AppEngine::class)
            ->setMethods(array('createProcess'))
            ->setConstructorArgs(array('project', 'version'))
            ->getMock();
        $runCmd = 'dev_appserver.py --port 8080 '
            . '--skip_sdk_update_check true '
            . '--php_executable_path /usr/bin/php-cgi '
            . 'app.yaml';
        $mockGcloudWrapper->expects($this->once())
            ->method('createProcess')
            ->with($this->equalTo($runCmd))
            ->willReturn($this->mockProcess);

        $this->mockProcess->expects($this->once())->method('start');
        $this->mockProcess->expects($this->once())->method('stop');
        $this->mockProcess->expects($this->exactly(2))->method('isRunning');

        $mockGcloudWrapper->run();

        $this->assertEquals(
            'http://127.0.0.1:8080',
            $mockGcloudWrapper->getLocalBaseUrl()
        );

        $mockGcloudWrapper->stop();

        $this->assertEquals(false, $mockGcloudWrapper->getLocalBaseUrl());
    }

    public function testRunAndStopWithCustomArgs()
    {
        $mockGcloudWrapper = $this->getMockBuilder(AppEngine::class)
            ->setMethods(array('createProcess'))
            ->setConstructorArgs(array('project', 'version'))
            ->getMock();
        $runCmd = 'dev_appserver.py --port 8080 '
            . '--skip_sdk_update_check true '
            . '--php_executable_path /usr/local/bin/php-cgi '
            . 'app.yaml backend.yaml';
        $mockGcloudWrapper->expects($this->once())
            ->method('createProcess')
            ->with($this->equalTo($runCmd))
            ->willReturn($this->mockProcess);

        $this->mockProcess->expects($this->once())->method('start');
        $this->mockProcess->expects($this->once())->method('stop');
        $this->mockProcess->expects($this->exactly(2))->method('isRunning');

        $mockGcloudWrapper->run(
            'app.yaml backend.yaml',
            '/usr/local/bin/php-cgi'
        );

        $this->assertEquals(
            'http://127.0.0.1:8080',
            $mockGcloudWrapper->getLocalBaseUrl()
        );

        $mockGcloudWrapper->stop();

        $this->assertEquals(false, $mockGcloudWrapper->getLocalBaseUrl());
    }

    public function testRunAndStopWithCustomPort()
    {
        $mockGcloudWrapper = $this->getMockBuilder(AppEngine::class)
            ->setMethods(array('createProcess'))
            ->setConstructorArgs(array('project', 'version', null, 8000))
            ->getMock();
        $runCmd = 'dev_appserver.py --port 8000 '
            . '--skip_sdk_update_check true '
            . '--php_executable_path /usr/bin/php-cgi '
            . 'app.yaml';
        $mockGcloudWrapper->expects($this->once())
            ->method('createProcess')
            ->with($this->equalTo($runCmd))
            ->willReturn($this->mockProcess);

        $this->mockProcess->expects($this->once())->method('start');
        $this->mockProcess->expects($this->once())->method('stop');
        $this->mockProcess->expects($this->exactly(2))->method('isRunning');

        $mockGcloudWrapper->run();

        $this->assertEquals(
            'http://127.0.0.1:8000',
            $mockGcloudWrapper->getLocalBaseUrl()
        );

        $mockGcloudWrapper->stop();

        $this->assertEquals(false, $mockGcloudWrapper->getLocalBaseUrl());
    }
}
