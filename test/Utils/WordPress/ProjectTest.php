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

namespace Google\Cloud\Utils\WordPress;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * Class WordPress\ProjectTest
 * @package Google\Cloud\Utils\Test
 *
 * A class for testing WordPress\Project class.
 */
class ProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testInitializeDatabase()
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $helper = $this->createMock(QuestionHelper::class);
        $i = 0;
        $input
            ->expects($this->exactly(7))
            ->method('getOption')
            ->will($this->returnCallback(function ($optionName) {
                if ($optionName == 'dir') {
                    return sys_get_temp_dir() . '/wp-project' . rand();
                }
                if ($optionName == 'db_region') {
                    return 'us-central1';
                }
                return null;
            }));
        $input
            ->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue(true));
        $helper
            ->expects($this->any())
            ->method('ask')
            ->will($this->returnCallback(function ($optionName) use (&$i) {
                return 'value_' . $i++;
            }));

        $project = new Project($input, $output, $helper);
        $project->initializeProject();
        $params = $project->initializeDatabase();

        $this->assertEquals([
            'project_id' => 'value_1',
            'db_instance' => 'value_2',
            'db_name' => 'value_3',
            'db_user' => 'value_4',
            'db_password' => 'value_5',
            'db_connection' => 'value_1:us-central1:value_2',
            'local_db_user' => 'value_4',
            'local_db_password' => 'value_5',
        ], $params);
    }

    public function testDownloadWordPress()
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $input
            ->expects($this->exactly(2))
            ->method('getOption')
            ->with($this->logicalOr(
                $this->equalTo('dir'),
                $this->equalTo('wordpress_url')
            ))
            ->will($this->returnCallback(function ($optionName) {
                if ($optionName == 'dir') {
                    return sys_get_temp_dir() . '/wp-project' . rand();
                }
                return Project::LATEST_WP;
            }));

        $project = new Project($input, $output);
        $dir = $project->initializeProject();
        $project->downloadWordpress();
        $this->assertFileExists($dir . '/wordpress/wp-login.php');

        // test downloading a plugin
        $project->downloadGcsPlugin();
        $this->assertFileExists($dir . '/wordpress/wp-content/plugins/gcs/readme.txt');
    }

    public function testDownloadWordPressToDifferentDirectory()
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $dir = sys_get_temp_dir() . '/wp-project' . rand();
        $input
            ->expects($this->once())
            ->method('getOption')
            ->with($this->logicalOr(
                $this->equalTo('dir'),
                $this->equalTo('wordpress_url')
            ))
            ->will($this->returnCallback(function ($optionName) {
                return Project::LATEST_WP;
            }));

        $project = new Project($input, $output);
        $project->downloadWordpress($dir);
        $project->initializeProject($dir);

        $this->assertFileExists($dir . '/wp-login.php');

        // test downloading a plugin
        $project->downloadGcsPlugin();
        $this->assertFileExists($dir . '/wp-content/plugins/gcs/readme.txt');
    }
}
