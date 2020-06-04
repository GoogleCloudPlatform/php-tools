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

namespace Google\Cloud\TestUtils\Test;

use Google\Cloud\TestUtils\FileUtil;

/**
 * Class FileUtilTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing the FileUtil class.
 */
class FileUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testCloneDirectoryIntoTemp()
    {
        $newDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/../fixtures');

        $this->assertFileExists($newDir);
        $this->assertFileExists($newDir . '/appengine-standard');
        foreach (['app.php', 'phpunit.xml'] as $file) {
            $this->assertFileExists($newDir . '/appengine-standard/' . $file);
        }
    }

    public function testCloneDirectoryIntoTempWithProgress()
    {
        $output = new \Symfony\Component\Console\Output\BufferedOutput;
        $progress = new \Symfony\Component\Console\Helper\ProgressBar($output);

        $newDir = FileUtil::cloneDirectoryIntoTmp(__DIR__ . '/../fixtures/clonedir', $progress);

        $this->assertContains('1 [', $output->fetch());
    }

    public function testCloneIntoDirectoryWithExistingFile()
    {
        $tmpDir = sys_get_temp_dir() . '/test-' . FileUtil::randomName(8);
        mkdir($tmpDir);
        $testText = 'This is the existing app.php';
        file_put_contents($tmpDir . '/app.php', $testText);
        FileUtil::copyDir(
            __DIR__ . '/../fixtures/appengine-standard',
            $tmpDir
        );

        $this->assertNotEquals($testText, file_get_contents($tmpDir . '/app.php'));
    }
}
