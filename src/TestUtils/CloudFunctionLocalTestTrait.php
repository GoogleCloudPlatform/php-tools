<?php
/**
 * Copyright 2020 Google LLC.
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

use Google\Cloud\TestUtils\GcloudWrapper\CloudFunction;
use GuzzleHttp\Client;

/**
 * Trait CloudFunctionLocalTestTrait.
 *
 * Uses the function framework to run the function as a local service for system tests.
 */
trait CloudFunctionLocalTestTrait
{
    use TestTrait;

    /** @var \GuzzleHttp\Client */
    private $client;

    /** @var \Google\Cloud\TestUtils\GcloudWrapper\CloudFunction */
    private static $fn;

    /** @var \Symfony\Component\Process\Process; */
    private static $localhost;

    /**
     * Start the function service.
     *
     * @beforeClass
     */
    public static function startFunction()
    {
        $projectId = self::requireEnv('GOOGLE_PROJECT_ID');
        self::$fn = new CloudFunction($projectId, self::$name);
        self::$localhost = self::$fn->run(self::$isCloudEventFunction ?? false);
    }

    /**
     * Set up the client.
     *
     * @before
     */
    public function setUpClient()
    {
        $baseUrl = self::$fn->getLocalBaseUrl();
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'http_errors' => false
        ]);
    }

    /**
     * Stop the function.
     *
     * @afterClass
     */
    public static function stopFunction()
    {
        self::$fn->stop();
    }
}
