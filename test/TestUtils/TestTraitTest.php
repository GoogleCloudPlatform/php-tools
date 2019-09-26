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

namespace Google\Cloud\TestUtils\test;

use Google\Cloud\TestUtils\TestTrait;
use Google\Cloud\TestUtils\ExponentialBackoffTrait;

/**
 * Class TestTraitTest
 * @package Google\Cloud\TestUtils\Test
 *
 * A class for testing TestTrait.
 */
class TestTraitTest extends \PHPUnit_Framework_TestCase
{
    use TestTrait;
    use ExponentialBackoffTrait;

    public static function checkProjectEnvVarBeforeClass()
    {
        // disable checkProjectEnvVarBeforeClass
    }

    /**
     * @runInSeparateProcess
     */
    public function testCheckProjectEnvVars()
    {
        // Test GOOGLE_APPLICATION_CREDENTIALS
        putenv('GOOGLE_APPLICATION_CREDENTIALS=');
        putenv('GOOGLE_PROJECT_ID=foo');
        try {
            self::checkProjectEnvVars();
            $this->fail('should have skipped!');
        } catch (\PHPUnit_Framework_SkippedTestError $e) {
            $this->assertTrue(true);
        }
        $this->assertEquals('foo', self::$projectId);

        // Test GOOGLE_PROJECT_ID
        putenv('GOOGLE_APPLICATION_CREDENTIALS=foo');
        putenv('GOOGLE_PROJECT_ID=');
        try {
            self::checkProjectEnvVars();
            $this->fail('should have skipped!');
        } catch (\PHPUnit_Framework_SkippedTestError $e) {
            $this->assertTrue(true);
        }
    }

    public function testRequireEnvVar()
    {
        putenv('FAKE_ENV=');
        try {
            $this->requireEnv('FAKE_ENV');
            $this->fail('should have skipped!');
        } catch (\PHPUnit_Framework_SkippedTestError $e) {
            $this->assertTrue(true);
        }

        // Test GOOGLE_PROJECT_ID
        putenv('FAKE_ENV=foo');
        try {
            $val = $this->requireEnv('FAKE_ENV');
            $this->assertEquals('foo', $val);
        } catch (\PHPUnit_Framework_SkippedTestError $e) {
            $this->fail('should not have skipped!');
        }
    }

    public function testRunSnippet()
    {
        $snippet1Cmd = sprintf('php %s/../src/snippet1.php', __DIR__);
        $output1 = shell_exec($snippet1Cmd);
        $output2 = $this->runSnippet('snippet1');
        $this->assertEquals($output1, $output2);

        $output1 = shell_exec($snippet1Cmd . ' foo bar baz');
        $output2 = $this->runSnippet('snippet1', ['foo', 'bar', 'baz']);
        $this->assertEquals($output1, $output2);

        // Test escaping shell args
        $output1 = shell_exec($snippet1Cmd . ' foo bar \'\'');
        $output2 = $this->runSnippet('snippet1', ['foo', 'bar', '']);
        $this->assertEquals($output1, $output2);

        $snippet2File = __DIR__ . '/../fixtures/snippet2.php';
        $snippet2Cmd = sprintf('php ' . $snippet2File);
        $output1 = shell_exec($snippet2Cmd);
        $output2 = $this->runSnippet($snippet2File);
        $this->assertEquals($output1, $output2);

        $output1 = shell_exec($snippet2Cmd . ' foo bar baz');
        $output2 = $this->runSnippet($snippet2File, ['foo', 'bar', 'baz']);
        $this->assertEquals($output1, $output2);
    }

    public function testRunSnippetWithBackoff()
    {
        $this->useBackoff($retries = 5, function () use (&$timesCalled) {
            $timesCalled++;
            return true;
        });

        self::setDelayFunction(function ($delay) {
            // do nothing!
        });

        try {
            $output = $this->runSnippet('snippet3');
            $this->fail('Should have thrown exception');
        } catch (\Exception $e) {
            $this->assertEquals('This is expected', $e->getMessage());
        }

        $this->assertEquals($retries, $timesCalled);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage This is expected
     */
    public function testRunSnippetWithException()
    {
        $this->runSnippet('snippet3');
    }

    public function setUp()
    {
        // Clear backoffs before running each test
        self::$backoff = null;
    }
}
