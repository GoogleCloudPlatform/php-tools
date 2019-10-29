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

use Google\Cloud\TestUtils\GcloudCloudRunWrapper;

/**
 * Class GcloudCloudRunWrapperTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing GcloudAppEngine class.
 */
class GcloudCloudRunWrapperTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \Google\Cloud\TestUtils\GcloudCloudRunWrapper */
    private $mockGcloudWrapper;

    private static $image = 'gcr.io/my-project/image';

    public function testDeployAndDeleteWithDefault()
    {
        $this->mockGcloudWrapper = $this->getMockBuilder(
            '\Google\Cloud\TestUtils\GcloudCloudRunWrapper'
        )
            ->setMethods(['execWithRetry'])
            ->setConstructorArgs(['project'])
            ->getMock();
        $this->mockGcloudWrapper->method('execWithRetry')->willReturn(true);
        $deployCmd = 'gcloud beta run deploy default --image ' . self::$image
            . ' --region us-central1 --platform managed'
            . ' --project project';
        $deleteCmd = 'gcloud beta run services delete default'
            . ' --region us-central1 --platform managed'
            . ' --project project';
        $this->mockGcloudWrapper->expects($this->exactly(2))
            ->method('execWithRetry')
            ->withConsecutive(
                [$this->equalTo($deployCmd), $this->equalTo(3)],
                [$this->equalTo($deleteCmd), $this->equalTo(3)]
            );

        $this->mockGcloudWrapper->deploy(self::$image);

        $this->mockGcloudWrapper->delete();
    }

    public function testDeployAndDeleteWithCustomArgs()
    {
        $this->mockGcloudWrapper = $this->getMockBuilder(
            '\Google\Cloud\TestUtils\GcloudCloudRunWrapper'
        )
            ->setMethods(['execWithRetry'])
            ->setConstructorArgs(['project', [
                'platform' => 'gke',
                'region' => 'us-central2',
                'service' => 'foo',
            ]])
            ->getMock();
        $this->mockGcloudWrapper->method('execWithRetry')->willReturn(true);
        $deployCmd = 'gcloud beta run deploy foo --image ' . self::$image
            . ' --region us-central2 --platform gke'
            . ' --project project';
        $deleteCmd = 'gcloud beta run services delete foo'
            . ' --region us-central2 --platform gke'
            . ' --project project';
        $this->mockGcloudWrapper->expects($this->exactly(2))
            ->method('execWithRetry')
            ->withConsecutive(
                [$this->equalTo($deployCmd), $this->equalTo(4)],
                [$this->equalTo($deleteCmd), $this->equalTo(4)]
            );

        $this->mockGcloudWrapper->deploy(self::$image, ['retries' => 4]);

        $this->mockGcloudWrapper->delete(['retries' => 4]);
    }
}
