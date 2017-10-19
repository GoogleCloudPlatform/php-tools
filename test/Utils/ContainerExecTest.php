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

use Google\Cloud\Utils\ContainerExec;
use Google\Cloud\Utils\Gcloud;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Class ContainerExecTest
 * @package Google\Cloud\Utils\Test
 *
 * A class for testing ContainerExec class
 */
class ContainerExecTest extends \PHPUnit_Framework_TestCase
{
    private $gcloud;
    private $fs;
    private $tempnam;
    private $workdir;

    public function setUp()
    {
        $this->gcloud = $this->prophesize(Gcloud::class);
        $this->fs = new Filesystem();
        $this->tempnam = tempnam(sys_get_temp_dir(), 'flex-exec-test');
        $this->workdir = $this->tempnam . '_workdir';
        try {
            $this->fs->mkdir($this->workdir);
        } catch (IOExceptionInterface $e) {
            $this->fail("Failed to crete a workdir: " . $e->getTraceAsString());
        }
    }

    public function tearDown()
    {
        unlink($this->tempnam);
        $this->fs->remove($this->workdir);
    }

    public function testContainerExecInit()
    {
        $image = 'gcr.io/my-project/my-image';
        $containerExec = new ContainerExec(
            $image,
            ['ls', '-al', 'my dir'],
            $this->workdir,
            '',
            $this->gcloud->reveal()
        );
    }

    public function testContainerExecNonDir()
    {
        $image = 'gcr.io/my-project/my-image';
        try {
            $containerExec = new ContainerExec(
                $image,
                ['ls', '-al', 'my dir'],
                $this->workdir . '-non-existing',
                '',
                $this->gcloud->reveal()
            );
        } catch (\InvalidArgumentException $e) {
            //  $e here has the function scope, we can assert it later.
        }
        $this->assertNotNull($e);
        $this->assertEquals(
            $this->workdir . '-non-existing is not a directory',
            $e->getMessage()
        );
    }

    public function testContainerExecRun()
    {
        $image = 'gcr.io/my-project/my-image';
        $this->gcloud->exec(
            [
                'container',
                'builds',
                'submit',
                "--config=$this->workdir/cloudbuild.yaml",
                "$this->workdir"
            ]
        )->shouldBeCalledTimes(1)->willReturn([0, ['output']]);
        $containerExec = new ContainerExec(
            $image,
            ['ls', '-al', 'my dir'],
            $this->workdir,
            '',
            $this->gcloud->reveal()
        );
        $containerExec->run();
    }
}
