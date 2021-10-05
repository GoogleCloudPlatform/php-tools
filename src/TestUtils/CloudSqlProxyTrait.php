<?php
/*
 * Copyright 2021 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\TestUtils;

use Symfony\Component\Process\Process;
use Exception;

/**
 * Trait CloudSqlTestTrait
 * @package Google\Cloud\TestUtils
 *
 * Use this trait to run the CloudSqlProxy
 */
trait CloudSqlProxyTrait
{
    private static $cloudSqlProxyProcess;

    public static function startCloudSqlProxy($connectionName, $socketDir, $port = null)
    {
        // create the directory to store the unix socket for cloud_sql_proxy
        if (!is_dir($socketDir) && !@mkdir($socketDir, 0755, true)) {
            throw new Exception('Unable to create socket dir ' . $socketDir);
        }

        $instances = sprintf('-instances=%s', $connectionName);
        if ($port) {
            $instances = sprintf('%s=tcp:%s,%s', $instances, $port, $connectionName);
        }

        $process = new Process(['cloud_sql_proxy', $instances, '-dir', $socketDir]);
        $process->start();
        $process->waitUntil(function ($type, $buffer) {
            print($buffer);
            return str_contains($buffer, 'Ready for new connections');
        });
        if (!$process->isRunning()) {
            if ($output = $process->getOutput()) {
                print($output);
            }
            if ($errorOutput = $process->getErrorOutput()) {
                print($errorOutput);
            }
            throw new Exception('Failed to start cloud_sql_proxy');
        }
        return self::$cloudSqlProxyProcess = $process;
    }

    /**
     * @afterClass
     */
    public static function stopCloudSqlProxy(): void
    {
        if (self::$cloudSqlProxyProcess && self::$cloudSqlProxyProcess->isRunning()) {
            self::$cloudSqlProxyProcess->stop();
        }
    }
}
