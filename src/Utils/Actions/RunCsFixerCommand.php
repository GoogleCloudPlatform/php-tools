<?php
/**
 * Copyright 2024 Google Inc.
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

namespace Google\Cloud\Utils\Actions;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * CLI command for running the CS Fixer shared workflow.
 */
class RunCsFixerCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('cs-fixer')
            ->setDescription('Execute a command with the deployed image')
            ->addArgument(
                'repo',
                InputArgument::REQUIRED,
                'The name of the repo to run the CS fixer for'
            )
            ->addOption(
                'workflow-file',
                '',
                InputOption::VALUE_REQUIRED,
                'name of the github workflow file which contains the configuration',
                'lint.yaml'
            )
            ->addOption(
                'ref',
                '',
                InputOption::VALUE_REQUIRED,
                'The branch of the repo run the CS fixer for',
                'main'
            )
            ->addOption(
                'flags',
                '',
                InputOption::VALUE_REQUIRED,
                'The flags to pass down to the CS fixer',
                '--dry-run --diff'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $input->getArgument('repo');
        if (false === strpos($repo, '/')) {
            throw new \Exception('Invalid repo name. Use the format: owner/repo');
        }

        $url = sprintf(
            'https://raw.githubusercontent.com/%s/%s/.github/workflows/%s',
            $repo,
            $input->getOption('ref'),
            $input->getOption('workflow-file'),
        );

        $client = new Client(['http_errors' => false]);
        $response = $client->request('GET', $url);
        if ($response->getStatusCode() === 404) {
            throw new \Exception(sprintf(
                'Failed to fetch the workflow file at "%s", maybe it doesn\'t exist? '
                . 'Try supplying the "--workflow-file" option.',
                $url
            ));
        }
        $workflow = Yaml::parse($response->getBody());
        $job = null;

        foreach ($workflow['jobs'] as $id => $workflowJob) {
            if (str_contains($workflowJob['uses'] ?? '', '.github/workflows/code-standards.yml')) {
                $output->writeln(sprintf('Found job "%s"', $workflowJob['name'] ?? $id));
                $job = $workflowJob;
                break;
            }
        }
        if (!$job) {
            throw new \Exception('No job found for php-tools/code-standards.yaml in the workflow file');
        }

        // get the default config
        $defaultWorkflow = Yaml::parse(file_get_contents(__DIR__ . '/../../../.github/workflows/code-standards.yml'));
        $defaults = [];
        foreach ($defaultWorkflow['on']['workflow_call']['inputs'] as $name => $inputOptions) {
            $defaults[$name] = $inputOptions['default'] ?? '';
        }
        $options = array_merge($defaults, $job['with']);

        if (str_starts_with($options['config'], 'GoogleCloudPlatform/php-tools/')) {
            // use local file
            $options['config'] = str_replace(
                'GoogleCloudPlatform/php-tools/',
                __DIR__ . '/../../../',
                $options['config']
            );
            // strip branch (we'll ignore it in favor of the current branch)
            if (false !== $i = strpos($options['config'], '@')) {
                $options['config'] = substr($options['config'], 0, $i);
            }
            if (!file_exists($options['config'])) {
                throw new \Exception('config file not found: ' . realpath($options['config']));
            }
        }

        // go through config options and set env vars accordingly
        $rules = json_encode(array_merge(
            json_decode($options['rules'], true),
            json_decode($options['add-rules'], true)
        ));

        $excludePatterns = str_replace(["\n", ' '], '', $options['exclude-patterns']);

        // use config path only if EXCLUDE_PATTERN is empty
        if ($options['config']) {
            // set environment variables so they're available in the CONFIG file
            $env = sprintf(
                'CONFIG_PATH=%s RULES=$\'%s\' EXCLUDE_PATTERNS=$\'%s\'',
                $options['path'],
                $rules,
                $excludePatterns,
            );
            // Run command using the --config flag
            $cmd = sprintf(
                '%s ~/.composer/vendor/bin/php-cs-fixer fix --config=%s %s',
                $env,
                $options['config'],
                $input->getOption('flags')
            );
        } else {
            // Run command using the --rules flag
            $cmd = sprintf(
                '~/.composer/vendor/bin/php-cs-fixer fix %s --rules=$\'%s\' %s',
                $options['path'],
                $rules,
                $input->getOption('flags')
            );
        }

        $output->writeln('Executing the following command: ');
        $output->writeln('');
        $output->writeln("<info>\t" . $cmd . '</>');
        $output->writeln('');

        // @TODO use Symfony process component to run this
        passthru($cmd, $resultCode);

        return $resultCode;
    }
}
