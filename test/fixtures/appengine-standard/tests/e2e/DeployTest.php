<?php
/**
 * Copyright 2016 Google Inc.
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
namespace Google\Cloud\Test;

use Google\Cloud\TestUtils\AppEngineDeploymentTrait;

class DeployTest extends \PHPUnit_Framework_TestCase
{
    use AppEngineDeploymentTrait;
    use HelloTestTrait;

    /**
     * Called before deploying the app.
     */
    private static function beforeDeploy()
    {
        // Copy app.yaml, making sure this function is called before the
        // deployment.
        copy('app.yaml.dist', 'app.yaml');
        // Call setter for $dir
        self::$gcloudWrapper->setDir(realpath(__DIR__ . '/../..'));
    }

    /**
     * Called after deploying the app.
     */
    private static function afterDeploy()
    {
        // Delete app.yaml.
        unlink('app.yaml');
    }
}
