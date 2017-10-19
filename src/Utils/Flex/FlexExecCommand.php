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

namespace Google\Cloud\Utils\Flex;

use Google\Cloud\Utils\ContainerExec;
use Google\Cloud\Utils\Gcloud;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * CLI command for running a command with an image deployed to App Engine
 * Flex.
 *
 * You can specify `--service` and `--target-version`, or leave it to the
 * tool's automatic detection. The `service` defaults to `default` and the
 * `version` is resolved to the latest deployed version on the service. You
 * can also specify the image directly with `--image` option.
 *
 * When using the deployed image, it also detects the Cloud SQL connection
 * configured for the deployment, and enable it for the command execution.
 */
class FlexExecCommand extends Command
{
    const DEFAULT_SERVICE = 'default';

    /* @var Gcloud */
    private $gcloud;

    public function __construct(Gcloud $gcloud = null)
    {
        parent::__construct();
        $this->gcloud = ($gcloud == null) ? new Gcloud() : $gcloud;
    }

    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Execute a command with the deployed image')
            ->addOption(
                'service',
                's',
                InputOption::VALUE_REQUIRED,
                'Service name for the deployed image, defaults to `default`, '
                . 'ignored when the `--image` option is specificed'
            )
            ->addOption(
                'target-version',
                't',
                InputOption::VALUE_REQUIRED,
                'Version name for the deployed image, ignored when the '
                . '`--image` option is specificed. If this option is not '
                . 'specified, it will automatically pick the latest deployed '
                . 'version'
            )
            ->addOption(
                'image',
                'i',
                InputOption::VALUE_REQUIRED,
                'Docker image name'
            )
            ->addOption(
                'cloud-sql-instances',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma separated cloud sql instance connection names'
            )
            ->addOption(
                'preserve-workdir',
                'p',
                InputOption::VALUE_NONE,
                'Preserve the temporary workdir'
            )
            ->addOption(
                'workdir',
                'd',
                InputOption::VALUE_NONE,
                'Temporary workdir'
            )
            ->addArgument(
                'commands',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Commands to execute'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        $commands = $input->getArgument('commands');
        $preserveWorkdir = $input->getOption('preserve-workdir');
        $image = $input->getOption('image');
        $cloudSqlInstances = $input->getOption('cloud-sql-instances');
        if (empty($image)) {
            list($image, $cloudSqlInstancesFromDeploy) = $this->resolveImage(
                $input,
                $output
            );
            // If the `cloud-sql-instances` option is given, always use that
            // value. Otherwise, use the value from the deployment.
            $cloudSqlInstances = ($cloudSqlInstances === null)
                ? $cloudSqlInstancesFromDeploy
                : $cloudSqlInstances;
        }
        if (empty($image)) {
            $output->writeln('<error>Could not resolve the image</error>');
            exit(1);
        }
        $output->writeln("Using image: <info>$image</info>");
        if (!empty($cloudSqlInstances)) {
            $output->writeln(
                "Using cloudSqlInstances: <info>$cloudSqlInstances</info>"
            );
        }
        $workdir = $input->getOption('workdir');
        if (empty($workdir)) {
            // Use tempnam for unique dir name
            $tempnam = tempnam(sys_get_temp_dir(), 'flex-exec');
            // Delete on shutdown
            register_shutdown_function(function () use ($tempnam) {
                unlink($tempnam);
            });
            $workdir = $tempnam . '_workdir';
            try {
                $fs->mkdir($workdir);
            } catch (IOExceptionInterface $e) {
                $output->writeln("<error>Failed to create $workdir</error>");
                $output->writeln($e->getTraceAsString());
                exit(1);
            }
        }
        $output->writeln("Using workdir: <info>$workdir</info>");
        if ($preserveWorkdir) {
            $output->writeln("<info>Preserving the workdir</info>");
        } else {
            register_shutdown_function(function () use ($workdir, $fs) {
                $fs->remove($workdir);
            });
        }
        $output->writeln(
            'Running command: <info>'
            . implode(' ', $commands)
            . '</info>'
        );
        $containerExec = new ContainerExec(
            $image,
            $commands,
            $workdir,
            $cloudSqlInstances,
            $this->gcloud
        );
        $output->writeln($containerExec->run());
        $output->writeln(
            '<info>`'
            . implode(' ', $commands)
            . '` succeeded</info>'
        );
    }

    /**
     * Resolve the image name for the command execution. If the service is not
     * specified, it uses `default` service. If the version is not specified,
     * it will use the version of the latest deployment for the service.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array An array with two values, the first is the image name and
     *         the second is the Cloud SQL instance names.
     */
    protected function resolveImage(
        InputInterface $input,
        OutputInterface $output
    ) {
        $service = $input->getOption('service');
        if (empty($service)) {
            $service = self::DEFAULT_SERVICE;
        }
        $version = $input->getOption('target-version');
        if (empty($version)) {
            $version = $this->detectLatestDeployedVersion(
                $service,
                $input,
                $output
            );
        }
        $output->writeln("Using service: <info>$service</info>");
        $output->writeln("Using version: <info>$version</info>");
        list($ret, $cmdOutput) = $this->gcloud->exec(
            [
                'app',
                'versions',
                'describe',
                $version,
                "--service=$service",
                "--format=json"
            ]
        );
        if ($ret !== 0) {
            $output->writeln('<error>Failed running `gcloud app versions describe`</error>');
            exit(1);
        }
        $describe = json_decode(implode(PHP_EOL, $cmdOutput), true);
        if (empty($describe)) {
            $output->writeln('<error>Could not decode the result of `gcloud app versions describe`</error>');
            exit(1);
        }
        if ($describe['env'] !== 'flexible') {
            $output->writeln('<error>The deployment must be for App Engine Flex</error>');
            exit(1);
        }
        $image = $describe['deployment']['container']['image'];
        $cloudSqlInstances = '';
        if (array_key_exists('betaSettings', $describe)
            && array_key_exists('cloud_sql_instances', $describe['betaSettings'])) {
            $cloudSqlInstances = $describe['betaSettings']['cloud_sql_instances'];
        }
        return [$image, $cloudSqlInstances];
    }

    /**
     * Detect the version of the latest deployment for the given service.
     *
     * @param string $service
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string The version for the latest deployment for the given
     *         service.
     */
    protected function detectLatestDeployedVersion(
        $service,
        InputInterface $input,
        OutputInterface $output
    ) {
        list($_, $cmdOutput) = $this->gcloud->exec(
            [
                'app',
                'versions',
                'list',
                "--service=$service",
                "--format=get(version.id)",
                "--sort-by=~version.createTime",
                "--limit=1"
            ]
        );
        if (!empty($cmdOutput)) {
            return  preg_split('/\s+/', $cmdOutput[0])[0];
        }
    }
}
