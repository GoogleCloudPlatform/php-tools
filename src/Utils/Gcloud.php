<?php
/*
 * Copyright 2017 Google Inc. All Rights Reserved.
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

namespace Google\Cloud\Utils;

class Gcloud
{
    const E_NOTFOUND = 127;

    /**
     * Print out the message to STDERR.
     *
     * @param string $message
     */
    private static function warn($message)
    {
        if (defined('STDERR')) {
            fwrite(STDERR, $message . PHP_EOL);
        } else {
            $stderr = fopen('php://stderr', 'w');
            fwrite($stderr, $message . PHP_EOL);
            fclose($stderr);
        }
    }

    /**
     * Make sure gcloud is authenticated.
     */
    public function __construct()
    {
        $auths = exec(
            escapeshellcmd("gcloud auth list --format=value(account)"),
            $output,
            $ret
        );
        if ($ret !== 0) {
            self::warn('gcloud failed');
            exit($ret);
        }
        if (empty($auths)) {
            self::warn('gcloud not authenticated');
            exit(1);
        }
        $project = exec(
            escapeshellcmd(
                'gcloud config list core/project --format=value(core.project)'
            )
        );
        if (empty($project)) {
            self::warn('gcloud project configuration not set');
            exit(1);
        }
    }

    /**
     * Execute gcloud with the given argument.
     *
     * @param array<string> $args
     * @param array $output It will capture the command output and set the
     *        output to this variable.
     * @return int The shell return value
     */
    public function exec($args, &$output)
    {
        $cmd = 'gcloud ' . implode(' ', array_map('escapeshellarg', $args));
        exec(
            $cmd,
            $output,
            $ret
        );
        return $ret;
    }
}
