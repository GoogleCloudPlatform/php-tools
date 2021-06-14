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

namespace Google\Cloud\TestUtils;

use ReflectionClass;

trait TestTrait
{
    private static $projectId;

    /** @beforeClass */
    public static function checkProjectEnvVarBeforeClass()
    {
        self::checkProjectEnvVars();
    }

    private static function checkProjectEnvVars()
    {
        self::$projectId = self::requireOneOfEnv([
            'GOOGLE_CLOUD_PROJECT',
            'GOOGLE_PROJECT_ID',
        ]);
        self::requireEnv('GOOGLE_APPLICATION_CREDENTIALS');
    }

    private static function requireOneOfEnv($varNames)
    {
        foreach ((array) $varNames as $varName) {
            if ($varValue = getenv($varName)) {
                return $varValue;
            }
        }

        self::markTestSkipped(
            sprintf('Set the %s environment variable',
                implode(' or ', (array) $varName)
            )
        );
    }

    private static function requireEnv($varName)
    {
        if (!$varValue = getenv($varName)) {
            self::markTestSkipped(
                sprintf('Set the %s environment variable', $varName)
            );
        }

        return $varValue;
    }

    private static function requireGrpc()
    {
        if (!extension_loaded('grpc')) {
            self::markTestSkipped('Must have the grpc extension installed to run this test.');
        }
    }

    private static function runFunctionSnippet($sampleName, $params = [])
    {
        // Get the namespace of the sample function
        $parts = explode('\\', debug_backtrace()[1]['class']);
        array_pop($parts);
        $snippet = implode('\\', $parts) . '\\' . $sampleName;

        // The file has already been included. Execute the function directly
        if (function_exists($snippet)) {
            try {
                ob_start();
                call_user_func_array($snippet, $params);
                return ob_get_clean();
            } catch (\Exception $e) {
                ob_get_clean();
                throw $e;
            }
        }
        $parts = explode('\\', $snippet);
        $sampleName = array_pop($parts);
        return self::runSnippet($sampleName, $params);
    }

    private static function runSnippet($sampleName, $params = [])
    {
        // Determine the snippet filename
        $sampleFile = $sampleName;
        if ('/' !== $sampleName[0]) {
            // Default to 'src/' in sample directory
            $reflector = new ReflectionClass(get_class());
            $testDir = dirname($reflector->getFileName());
            $sampleFile = sprintf('%s/../src/%s.php', $testDir, $sampleName);
        }

        $testFunc = function () use ($sampleFile, $params) {
            $argv = array_merge([$sampleFile], $params);
            $argc = count($argv);
            try {
                ob_start();
                require $sampleFile;
                return ob_get_clean();
            } catch (\Exception $e) {
                ob_get_clean();
                throw $e;
            }
        };

        if (isset(self::$backoff)) {
            return self::$backoff->execute($testFunc);
        }
        return $testFunc();
    }
}
