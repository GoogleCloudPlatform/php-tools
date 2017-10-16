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

use Google\Cloud\TestUtils\GcloudWrapper;

/**
 * Class GcloudWrapperTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing GcloudWrapper class.
 */
class GcloudWrapperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Symfony\Component\Process\Process */
    private $mockProcess;

    /** @var  \Google\Cloud\TestUtils\GcloudWrapper */
    private $mockGcloudWrapper;

    public function setUp()
    {
        $this->mockProcess = $this->getMockBuilder(
            '\Symfony\Component\Process\Process'
        )
            ->setMethods(array('start', 'isRunning', 'stop'))
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockProcess->method('start')->willReturn(null);
        $this->mockProcess->method('isRunning')->willReturn(true);
        $this->mockProcess->method('stop')->willReturn(null);
    }

    public function testDeployAndDeleteWithDefault()
    {
        $this->mockGcloudWrapper = $this->getMockBuilder(
            '\Google\Cloud\TestUtils\GcloudWrapper'
        )
            ->setMethods(array('execWithRetry'))
            ->setConstructorArgs(array('project', 'version'))
            ->getMock();
        $this->mockGcloudWrapper->method('execWithRetry')->willReturn(true);
        $deployCmd = 'gcloud -q app deploy '
            . '--project project --version version '
            . '--no-promote app.yaml';
        $deleteCmd = 'gcloud -q app versions delete '
            . '--service default version '
            . '--project project';
        $this->mockGcloudWrapper->expects($this->exactly(2))
            ->method('execWithRetry')
            ->withConsecutive(
                array($this->equalTo($deployCmd), $this->equalTo(3)),
                array($this->equalTo($deleteCmd), $this->equalTo(3))
            );

        $this->mockGcloudWrapper->deploy();

        $this->assertEquals(
            'https://version-dot-project.appspot.com',
            $this->mockGcloudWrapper->getBaseUrl()
        );

        $this->mockGcloudWrapper->delete();

        $this->assertEquals(false, $this->mockGcloudWrapper->getBaseUrl());
    }

    public function testDeployAndDeleteWithCustomArgs()
    {
        $this->mockGcloudWrapper = $this->getMockBuilder(
            '\Google\Cloud\TestUtils\GcloudWrapper'
        )
            ->setMethods(array('execWithRetry'))
            ->setConstructorArgs(array('project', 'version'))
            ->getMock();
        $this->mockGcloudWrapper->method('execWithRetry')->willReturn(true);
        $deployCmd = 'gcloud -q app deploy '
            . '--project project --version version '
            . '--promote app.yaml backend.yaml';
        $deleteCmd = 'gcloud -q app versions delete '
            . '--service myservice version '
            . '--project project';
        $this->mockGcloudWrapper->expects($this->exactly(2))
            ->method('execWithRetry')
            ->withConsecutive(
                array($this->equalTo($deployCmd), $this->equalTo(4)),
                array($this->equalTo($deleteCmd), $this->equalTo(4))
            );

        $this->mockGcloudWrapper->deploy('app.yaml backend.yaml', true, 4);

        $this->assertEquals(
            'https://version-dot-project.appspot.com',
            $this->mockGcloudWrapper->getBaseUrl()
        );

        $this->mockGcloudWrapper->delete('myservice', 4);

        $this->assertEquals(false, $this->mockGcloudWrapper->getBaseUrl());
    }

    public function testRunAndStopWithDefault()
    {
        $this->mockGcloudWrapper = $this->getMockBuilder(
            '\Google\Cloud\TestUtils\GcloudWrapper'
        )
            ->setMethods(array('createProcess'))
            ->setConstructorArgs(array('project', 'version'))
            ->getMock();
        $this->mockGcloudWrapper->method('createProcess')
            ->willReturn($this->mockProcess);

        $runCmd = 'exec dev_appserver.py --port 8080 '
            . '--skip_sdk_update_check true '
            . '--php_executable_path /usr/bin/php-cgi '
            . 'app.yaml';
        $this->mockGcloudWrapper->expects($this->once())
            ->method('createProcess')
            ->with($this->equalTo($runCmd));

        $this->mockProcess->expects($this->once())->method('start');
        $this->mockProcess->expects($this->once())->method('stop');
        $this->mockProcess->expects($this->exactly(2))->method('isRunning');

        $this->mockGcloudWrapper->run();

        $this->assertEquals(
            'http://localhost:8080',
            $this->mockGcloudWrapper->getLocalBaseUrl()
        );

        $this->mockGcloudWrapper->stop();

        $this->assertEquals(
            false,
            $this->mockGcloudWrapper->getLocalBaseUrl()
        );
    }

    public function testRunAndStopWithCustomArgs()
    {
        $this->mockGcloudWrapper = $this->getMockBuilder(
            '\Google\Cloud\TestUtils\GcloudWrapper'
        )
            ->setMethods(array('createProcess'))
            ->setConstructorArgs(array('project', 'version'))
            ->getMock();
        $this->mockGcloudWrapper->method('createProcess')
            ->willReturn($this->mockProcess);

        $runCmd = 'exec dev_appserver.py --port 8080 '
            . '--skip_sdk_update_check true '
            . '--php_executable_path /usr/local/bin/php-cgi '
            . 'app.yaml backend.yaml';
        $this->mockGcloudWrapper->expects($this->once())
            ->method('createProcess')
            ->with($this->equalTo($runCmd));

        $this->mockProcess->expects($this->once())->method('start');
        $this->mockProcess->expects($this->once())->method('stop');
        $this->mockProcess->expects($this->exactly(2))->method('isRunning');

        $this->mockGcloudWrapper->run(
            'app.yaml backend.yaml',
            '/usr/local/bin/php-cgi'
        );

        $this->assertEquals(
            'http://localhost:8080',
            $this->mockGcloudWrapper->getLocalBaseUrl()
        );

        $this->mockGcloudWrapper->stop();

        $this->assertEquals(
            false,
            $this->mockGcloudWrapper->getLocalBaseUrl()
        );
    }

    public function testRunAndStopWithCustomPort()
    {
        $this->mockGcloudWrapper = $this->getMockBuilder(
            '\Google\Cloud\TestUtils\GcloudWrapper'
        )
            ->setMethods(array('createProcess'))
            ->setConstructorArgs(array('project', 'version', null, 8000))
            ->getMock();
        $this->mockGcloudWrapper->method('createProcess')
            ->willReturn($this->mockProcess);

        $runCmd = 'exec dev_appserver.py --port 8000 '
            . '--skip_sdk_update_check true '
            . '--php_executable_path /usr/bin/php-cgi '
            . 'app.yaml';
        $this->mockGcloudWrapper->expects($this->once())
            ->method('createProcess')
            ->with($this->equalTo($runCmd));

        $this->mockProcess->expects($this->once())->method('start');
        $this->mockProcess->expects($this->once())->method('stop');
        $this->mockProcess->expects($this->exactly(2))->method('isRunning');

        $this->mockGcloudWrapper->run();

        $this->assertEquals(
            'http://localhost:8000',
            $this->mockGcloudWrapper->getLocalBaseUrl()
        );

        $this->mockGcloudWrapper->stop();

        $this->assertEquals(
            false,
            $this->mockGcloudWrapper->getLocalBaseUrl()
        );
    }
}
