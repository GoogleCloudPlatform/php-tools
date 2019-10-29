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

namespace Google\Cloud\TestUtils;

use Symfony\Component\Process\Process;

/**
 * Class GcloudWrapper
 * @package Google\Cloud\TestUtils
 *
 * A class representing App Engine application.
 */
class GcloudCloudRunWrapper extends GcloudWrapperBase
{
    /** @var string */
    private $image;

    /** @var string */
    private $service;

    /** @var string */
    private $region;

    /** @var string */
    private $platform;

    /** @var string */
    private $url;

    const GCLOUD_RUN = 'run';
    const DEFAULT_SERVICE = 'default';
    const DEFAULT_REGION = 'us-central1';
    const DEFAULT_PLATFORM = 'managed';

    /**
     * Constructor of GcloudWrapper.
     *
     * @param string $project
     * @param string $version
     * @param string|null $dir optional
     * @param int $port optional
     */
    public function __construct($project, $options = [])
    {
        $this->project = $project;

        $options = array_merge([
            'service' => self::DEFAULT_SERVICE,
            'region' => self::DEFAULT_REGION,
            'platform' => self::DEFAULT_PLATFORM,
            'dir' => null,
        ], $options);

        $this->platform = $options['platform'];
        $this->service = $options['service'];
        $this->region = $options['region'];

        parent::__construct($project, $options['dir']);
    }

    /**
     * Deploy the app to the Google Cloud Platform using Cloud Run.
     *
     * @param string $image The container image to deploy
     * @param array $options List of options
     *      $retries int Number of retries upon failure.
     * @return bool true if deployment suceeds, false upon failure.
     */
    public function build($image, $options = [])
    {
        // Set default optioins
        $options = array_merge([
            'retries' => self::DEFAULT_RETRIES,
        ], $options);

        $orgDir = getcwd();
        if (chdir($this->dir) === false) {
            $this->errorLog('Can not chdir to ' . $this->dir);
            return false;
        }
        $cmd = sprintf('gcloud builds submit --tag %s', $image);
        $ret = $this->execWithRetry($cmd, $options['retries']);
        chdir($orgDir);
        return $ret;
    }

    /**
     * Deploy the app to the Google Cloud Platform using Cloud Run.
     *
     * @param string $image The container image to deploy
     * @param array $options List of options
     *      $retries int Number of retries upon failure.
     * @return bool true if deployment suceeds, false upon failure.
     */
    public function deploy($image, $options = [])
    {
        // Set default optioins
        $options = array_merge([
            'retries' => self::DEFAULT_RETRIES,
        ], $options);
        if ($this->deployed) {
            $this->errorLog('The app has been already deployed.');
            return false;
        }
        $orgDir = getcwd();
        if (chdir($this->dir) === false) {
            $this->errorLog('Can not chdir to ' . $this->dir);
            return false;
        }
        $cmd = sprintf('gcloud beta %s deploy %s --image %s --region %s --platform %s --project %s',
            self::GCLOUD_RUN,
            $this->service,
            $image,
            $this->region,
            $this->platform,
            $this->project
        );
        $ret = $this->execWithRetry($cmd, $options['retries']);
        chdir($orgDir);
        if ($ret) {
            $this->deployed = true;
        }
        return $ret;
    }

    /**
     * Delete the deployed app.
     *
     * @param array $options List of options
     *      $retries int Number of retries upon failure.
     * @return bool true if the app is succesfully deleted, otherwise false
     */
    public function delete($options = [])
    {
        // Set default optioins
        $options = array_merge([
            'retries' => self::DEFAULT_RETRIES,
        ], $options);
        $cmd = sprintf('gcloud beta %s services delete %s --region %s --platform %s --project %s',
            self::GCLOUD_RUN,
            $this->service,
            $this->region,
            $this->platform,
            $this->project
        );
        $ret = $this->execWithRetry($cmd, $options['retries'], $output);
        if ($ret) {
            $this->deployed = false;
            $this->url = $output[0];
        }
        return $ret;
    }

    /**
     * Delete the deployed app.
     *
     * @param array $options List of options
     *      $retries int Number of retries upon failure.
     * @return bool true if the app is succesfully deleted, otherwise false
     */
    public function deleteImage($image)
    {
        // Set default optioins
        $options = array_merge([
            'retries' => self::DEFAULT_RETRIES,
        ], $options);
        $cmd = sprintf('gcloud container images delete %s --project %s',
            $this->project,
            $image
        );
        return $this->execWithRetry($cmd, $options['retries'], $output);
    }

    /**
     * Return the base URL of the deployed app.
     *
     * @param string $service optional
     * @return mixed returns the base URL of the deployed app, or false when
     *     the app is not deployed.
     */
    public function getBaseUrl($service = 'default')
    {
        if (!$this->deployed) {
            $this->errorLog('The app has not been deployed.');
        }
        return $this->url;
    }
}
