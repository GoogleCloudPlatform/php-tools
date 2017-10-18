<?php
/**
 * Copyright 2017 Google Inc.
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

namespace Google\Cloud\Utils;

/**
 * A class for running command within a Docker image.
 */
class ContainerExec
{
    const CLOUD_SQL_PROXY_IMAGE = 'gcr.io/cloudsql-docker/gce-proxy:1.11';

    /* @var Gcloud */
    private $gcloud;

    /* @var \Twig_Environment */
    private $twig;

    /* @var string */
    private $image;

    /* @var array */
    private $commands;

    /* @var string */
    private $cloudSqlInstances;

    /* @var string */
    private $workdir;

    /**
     * ContainerExec constructor
     *
     * @param string $image The target image
     * @param array $commands The commands to run on the image
     * @param string $workdir Working directory
     * @param string $cloudSqlInstances Comma delimited Cloud SQL instance names
     * @param Gcloud $gcloud Mainly for testing purpose
     */
    public function __construct(
        $image,
        $commands,
        $workdir,
        $cloudSqlInstances,
        $gcloud = null
    ) {
        if (!is_dir($workdir)) {
            throw new \InvalidArgumentException("$workdir is not a directory");
        }
        $this->gcloud = ($gcloud == null) ? new Gcloud() : $gcloud;
        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/templates');
        $this->twig = new \Twig_Environment($loader);
        $this->image = $image;
        $this->commands = $commands;
        $this->cloudSqlInstances = $cloudSqlInstances;
        $this->workdir = $workdir;
    }

    /**
     * Run the commands within the image, using Cloud Container Builder
     *
     * @return string The output of the relevant build step of the Container
     *         Builder job.
     * @throws \RuntimeException thrown when the command failed
     */
    public function run()
    {
        $template = $this->twig->load('cloudbuild.yaml.tmpl');
        $context = [
            'cloud_sql_instances' => $this->cloudSqlInstances,
            'cloud_sql_proxy_image' => self::CLOUD_SQL_PROXY_IMAGE,
            'target_image' => $this->image,
            'commands' => implode(
                ',',
                array_map('escapeshellarg', $this->commands)
            )
        ];
        $cloudBuildYaml = $template->render($context);
        file_put_contents("$this->workdir/cloudbuild.yaml", $cloudBuildYaml);
        list($result, $cmdOutput) = $this->gcloud->exec(
            [
                'container',
                'builds',
                'submit',
                "--config=$this->workdir/cloudbuild.yaml",
                "$this->workdir"
            ]
        );
        file_put_contents(
            "$this->workdir/cloudbuild.log",
            implode(PHP_EOL, $cmdOutput)
        );
        if ($result !== 0) {
            throw new \RuntimeException("Failed to run the command");
        }
        $ret = '';
        if ($this->cloudSqlInstances) {
            $targetStep = 'Step #3';
        } else {
            $targetStep = 'Step #1';
        }
        foreach ($cmdOutput as $line) {
            if (\strpos($line, $targetStep) !== false) {
                $ret .= $line . PHP_EOL;
            }
        }
        return $ret;
    }
}
