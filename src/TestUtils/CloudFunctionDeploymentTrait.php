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

use Google\Auth\ApplicationDefaultCredentials;
use Google\Cloud\TestUtils\GcloudWrapper\CloudFunction;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * Trait CloudFunctionDeploymentTrait.
 */
trait CloudFunctionDeploymentTrait
{
    use TestTrait;
    use DeploymentTrait;

    /** @var \Google\Cloud\TestUtils\GcloudWrapper\CloudFunction */
    private static $fn;

    /** @var string */
    private static $projectId;

    /** @var string */
    private static $versionId;

    /**
     * Prepare the Cloud Function.
     *
     * @beforeClass
     */
    public static function setUpFunction()
    {
        // Avoid repeating work if this has already been called by DeploymentTrait::deployApp().
        if (is_null(self::$fn)) {
            self::$projectId = self::requireEnv('GOOGLE_PROJECT_ID');
            self::$versionId = self::requireEnv('GOOGLE_VERSION_ID');
            self::$fn = new CloudFunction(self::$projectId, self::$name, [
                'functionName' => self::$name . '-' . self::$versionId,
                'deployFlags' => self::deployFlags([])
            ]);
        }
    }

    /**
     * Define initialization properties for the CloudFunction.
     */
    protected static function deployFlags(array $flags = [])
    {
        return $flags;
    }

    /**
     * Prepare to deploy app, called from DeploymentTrait::deployApp().
     */
    private static function beforeDeploy()
    {
        // Ensure function is set up before depoyment is attempted.
        self::setUpFunction();
    }

    /**
     * Deploy the Cloud Function.
     */
    private static function doDeploy()
    {
        if (false === self::$fn->deploy()) {
            return false;
        }

        return true;
    }

    /**
     * Delete a deployed Cloud Function.
     */
    private static function doDelete()
    {
        self::$fn->delete();
    }

    /**
     * Set up the client.
     *
     * @before
     */
    public function setUpClient()
    {
        $targetAudience = self::getBaseUri();

        // Create middleware.
        $middleware = ApplicationDefaultCredentials::getIdTokenMiddleware($targetAudience);
        $stack = HandlerStack::create();
        $stack->push($middleware);

        // Create the HTTP client.
        $this->client = new Client([
            'handler' => $stack,
            'auth' => 'google_auth',
            'base_uri' => $targetAudience,
            'http_errors' => false,
        ]);
    }

    public function getBaseUri()
    {
        return self::$fn->getBaseUrl();
    }
}
