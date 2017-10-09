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

use GuzzleHttp\Client;

/**
 * Trait DevAppserverTestTrait
 * @package Google\Cloud\TestUtils
 *
 * Use this trait to use dev_appserver for testing.
 */
trait DevAppserverTestTrait
{
    /** @var  \Google\Cloud\TestUtils\GcloudWrapper */
    private static $gcloudWrapper;
    /** @var  \GuzzleHttp\Client */
    private $client;

    /**
     * Called before running the dev_appserver. The concrete class can
     * override this.
     */
    private static function beforeRun()
    {
    }

    /**
     * Called after running the dev_appserver. The concrete class can
     * override this.
     */
    private static function afterRun()
    {
    }

    /**
     * Start Google App Engine devserver.
     *
     * @beforeClass
     */
    public static function startDevServer()
    {
        if (getenv('RUN_DEVSERVER_TESTS') !== 'true') {
            self::markTestSkipped(
                'To run this test, set RUN_DEVSERVER_TESTS env to true.'
            );
        }
        $phpCgi = getenv('PHP_CGI_PATH');
        if ($phpCgi === false) {
            $phpCgi = '/usr/bin/php-cgi';
        }
        $targets = getenv('LOCAL_TEST_TARGETS');
        if ($targets === false) {
            $targets = 'app.yaml';
        }
        self::$gcloudWrapper = new GcloudWrapper('', '');
        static::beforeRun();
        if (self::$gcloudWrapper->run($targets, $phpCgi) === false) {
            self::fail('dev_appserver failed');
        }
        static::afterRun();
    }

    /**
     * Stop the devserver.
     *
     * @afterClass
     */
    public static function stopServer()
    {
        self::$gcloudWrapper->stop();
    }

    /**
     * Set up the client
     *
     * @before
     */
    public function setUpClient()
    {
        $url = self::$gcloudWrapper->getLocalBaseUrl();
        $this->client = new Client(['base_uri' => $url]);
    }
}
