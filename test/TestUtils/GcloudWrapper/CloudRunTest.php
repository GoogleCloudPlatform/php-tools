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

use Google\Cloud\TestUtils\GcloudWrapper\CloudRun;

/**
 * Class GcloudWrapper\AppEngineTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing GcloudAppEngine class.
 */
class CloudRunTest extends \PHPUnit_Framework_TestCase
{
    private static $image = 'gcr.io/my-project/image';

    public function testDeployAndDeleteWithDefault()
    {
        $mockGcloudWrapper = $this->getMockBuilder(CloudRun::class)
            ->setMethods(['execWithRetry'])
            ->setConstructorArgs(['project'])
            ->getMock();
        $deployCmd = 'gcloud beta run deploy default --image ' . self::$image
            . ' --region us-central1 --platform managed'
            . ' --project project';
        $deleteCmd = 'gcloud beta run services delete default'
            . ' --region us-central1 --platform managed'
            . ' --project project';
        $mockGcloudWrapper->expects($this->exactly(2))
            ->method('execWithRetry')
            ->withConsecutive(
                [$this->equalTo($deployCmd), $this->equalTo(3)],
                [$this->equalTo($deleteCmd), $this->equalTo(3)]
            )
            ->will($this->returnValue(false));

        $mockGcloudWrapper->deploy(self::$image);

        $mockGcloudWrapper->delete();
    }

    public function testDeployAndDeleteWithCustomArgs()
    {
        $mockGcloudWrapper = $this->getMockBuilder(CloudRun::class)
            ->setMethods(['execWithRetry'])
            ->setConstructorArgs(['project', [
                'platform' => 'gke',
                'region' => '',
                'service' => 'foo',
            ]])
            ->getMock();
        $mockGcloudWrapper->method('execWithRetry')->willReturn(true);
        $deployCmd = 'gcloud beta run deploy foo --image ' . self::$image
            . ' --platform gke --project project';
        $deleteCmd = 'gcloud beta run services delete foo'
            . ' --platform gke --project project';
        $mockGcloudWrapper->expects($this->exactly(2))
            ->method('execWithRetry')
            ->withConsecutive(
                [$this->equalTo($deployCmd), $this->equalTo(4)],
                [$this->equalTo($deleteCmd), $this->equalTo(4)]
            );

        $mockGcloudWrapper->deploy(self::$image, ['retries' => 4]);

        $mockGcloudWrapper->delete(['retries' => 4]);
    }
}
