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

use Google\Cloud\TestUtils\DevAppserverTestTrait;

class LocalTest extends \PHPUnit_Framework_TestCase
{
    use DevAppserverTestTrait;
    use HelloTestTrait;

    /**
     * Called before starting dev_appserver.
     */
    private static function beforeRun()
    {
        // Just copy app.yaml, making sure this function is called before the
        // deployment.
        copy('app.yaml.dist', 'app.yaml');
    }

    /**
     * Called after starting dev_appserver.
     */
    private static function afterRun()
    {
        // Delete app.yaml.
        unlink('app.yaml');
    }
}
