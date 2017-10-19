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

/**
 * A class for executing gcloud commmands
 */
class Gcloud
{
    /**
     * Make sure gcloud is authenticated and the project is configured.
     */
    public function __construct()
    {
        $auths = exec(
            escapeshellcmd("gcloud auth list --format=value(account)"),
            $output,
            $ret
        );
        if ($ret !== 0) {
            throw new \RuntimeException('gcloud failed');
        }
        if (empty($auths)) {
            throw new \RuntimeException('gcloud not authenticated');
        }
        $project = exec(
            escapeshellcmd(
                'gcloud config list core/project --format=value(core.project)'
            ),
            $output,
            $ret
        );
        if (empty($project)) {
            throw new \RuntimeException('gcloud project configuration not set');
        }
    }

    /**
     * Execute gcloud with the given argument.
     *
     * @param array<string> $args
     * @return array [int, string] The shell return value and the command output
     */
    public function exec($args)
    {
        $cmd = 'gcloud ' . implode(' ', array_map('escapeshellarg', $args));
        exec($cmd, $output, $ret);
        return [$ret, $output];
    }
}
