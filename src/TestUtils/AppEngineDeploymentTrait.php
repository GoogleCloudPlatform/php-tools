<?php
/*
 * Copyright 2016 Google Inc. All Rights Reserved.
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

/**
 * Trait AppEngineDeploymentTrait
 * @package Google\Cloud\TestUtils
 *
 * Use this trait to deploy the project to GCP for an end-to-end test.
 */
trait AppEngineDeploymentTrait
{
    use TestTrait;
    use DeploymentTrait {
        DeploymentTrait::deployApp as baseDeployApp;
    }

    /** @var \Google\Cloud\TestUtils\GcloudWrapper */
    private static $gcloudWrapper;

    private static function doDeploy()
    {
        return self::$gcloudWrapper->deploy();
    }

    /**
     * Deploy the application.
     * Override DeploymentTrait::deployApp to ensure $gcloudWrapper exists.
     */
    public static function deployApp()
    {
        self::$gcloudWrapper = new GcloudWrapper(
            self::requireEnv('GOOGLE_PROJECT_ID'),
            self::requireEnv('GOOGLE_VERSION_ID')
        );
        self::baseDeployApp();
    }

    /**
     * Delete a deployed App Engine app.
     */
    private static function doDelete()
    {
        self::$gcloudWrapper->delete();
    }

    /**
     * Return the URI of the deployed App Engine app.
     */
    private function getBaseUri()
    {
        return self::$gcloudWrapper->getBaseUrl();
    }
}
