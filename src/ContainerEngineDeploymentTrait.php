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
 * Trait ContainerEngineDeploymentTrait
 * @package Google\Cloud\TestUtils
 *
 * Use this trait to deploy the project to GKE for an end-to-end test.
 */
trait ContainerEngineDeploymentTrait
{
    use DeploymentTrait;

    // required for container engine
    private static $kubeService;

    private static function doDeploy()
    {
        return self::$gcloudWrapper->deployContainer(self::$kubeService);
    }

    public static function doDelete()
    {
        self::$gcloudWrapper->deleteContainer();
    }
}
