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

namespace Google\Cloud\Utils\Test;

use Google\Cloud\Utils\Project;

/**
 * Class ProjectTest
 * @package Google\Cloud\Utils\Test
 *
 * A class for testing Project class.
 */
class ProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testProjectDir()
    {
        // using a relative directory
        $project = new Project('relative-path');
        $this->assertEquals(getcwd() . '/relative-path', $project->getDir());
        rmdir(getcwd() . '/relative-path');

        // using an existing directory
        $project = new Project($dir = sys_get_temp_dir());
        $this->assertEquals(realpath($dir), $project->getDir());

        // creating a directory
        $newDir = $dir . '/newdir' . rand();
        $project = new Project($newDir);
        $this->assertEquals(realpath($newDir), $project->getDir());

        $this->assertEquals(
            'A directory ' . $newDir . ' was created.',
            $project->getInfo()[0]
        );
    }

    public function testDownloadArchive()
    {
        $project = new Project(sys_get_temp_dir() . '/project' . rand());
        $archiveUrl = 'https://github.com/GoogleCloudPlatform/google-cloud-php/archive/master.zip';
        $project->downloadArchive('Google Cloud client libraries', $archiveUrl);
        $this->assertTrue(file_exists(
            $project->getDir() . '/google-cloud-php-master/composer.json'));
    }

    public function testCopyFiles()
    {
        $contents = "This is a TEST_PARAMETER template";
        $fromPath = tempnam(sys_get_temp_dir(), 'template');
        $fromDir = dirname($fromPath);
        $fromFile = basename($fromPath);
        file_put_contents($fromPath, $contents);

        // copy file with no parameters
        $project = new Project(sys_get_temp_dir() . '/project' . rand());
        $project->copyFiles($fromDir, [$fromFile => '/']);
        $newContents = file_get_contents($project->getDir() . '/' . $fromFile);
        $this->assertEquals($newContents, $contents);

        // copy file using parameters
        $project = new Project($dir = sys_get_temp_dir());
        $project->copyFiles($fromDir, [$fromFile => '/'], [
            'TEST_PARAMETER' => 'foo bar baz yip yip',
        ]);
        $newContents = file_get_contents($project->getDir() . '/' . $fromFile);
        $this->assertEquals($newContents, 'This is a foo bar baz yip yip template');
    }

    public function testAvailableDbRegions()
    {
        $project = new Project(__DIR__);
        $this->assertContains('us-central1', $project->getAvailableDbRegions());
    }
}
