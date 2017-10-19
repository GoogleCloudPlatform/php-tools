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

namespace Google\Cloud\Utils\Flex\Test;

use Google\Cloud\Utils\Flex\FlexExecCommand;
use Google\Cloud\Utils\Gcloud;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Class FlexExecCommandTest
 * @package Google\Cloud\Utils\Flex\Test
 *
 * A class for testing FlexExecCommand class
 */
class FlexExecCommandTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @dataProvider dataProvider
     */
    public function testFlexExecCommand($describeResult, $cloudbuildYaml)
    {
        $version = 'my-version';
        $this->gcloud->exec(
            [
                'app',
                'versions',
                'list',
                '--service=default',
                "--format=get(version.id)",
                "--sort-by=~version.createTime",
                "--limit=1"
            ]
        )
            ->shouldBeCalledTimes(1)
            ->willReturn(
                [
                    0,
                    [$version]
                ]
            );
        $this->gcloud->exec(
            [
                'app',
                'versions',
                'describe',
                $version,
                "--service=default",
                "--format=json"
            ]
        )
            ->shouldBeCalledTimes(1)
            ->willReturn(
                [
                    0,
                    explode(
                        PHP_EOL,
                        file_get_contents($describeResult)
                    )
                ]
            );
        $this->gcloud->exec(
            [
                'container',
                'builds',
                'submit',
                "--config=$this->workdir/cloudbuild.yaml",
                "$this->workdir"
            ]
        )
            ->shouldBeCalledTimes(1)
            ->willReturn(
                [
                    0,
                    ['Build succeeded']
                ]
            );
        $flexExecCommand = new FlexExecCommand($this->gcloud->reveal());
        $commandTester = new CommandTester($flexExecCommand);
        $commandTester->execute(
            [
                'commands' => ['ls', 'my dir'],
                '--preserve-workdir' => true,
                '--workdir' => $this->workdir
            ]
        );
        // Check the contents of the generated cloudbuild.yaml
        $cloudbuild = file_get_contents("$this->workdir/cloudbuild.yaml");
        $this->assertEquals(
            file_get_contents($cloudbuildYaml),
            $cloudbuild
        );
    }

    public function dataProvider()
    {
        return [
            [
                // with cloud-sql-proxy
                __DIR__ . '/data/cloudsql-describe-result',
                __DIR__ . '/data/cloudsql-cloudbuild-yaml'
            ],
            [
                // without cloud-sql-proxy
                __DIR__ . '/data/basic-describe-result',
                __DIR__ . '/data/basic-cloudbuild-yaml'
            ],
        ];
    }
}
