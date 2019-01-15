<?php
/**
 * Copyright 2018 Google Inc.
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

namespace Google\Cloud\Utils\WordPress;

use Exception;
use Google\Cloud\Utils\Project as BaseProject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Filesystem\Filesystem;

class Project extends BaseProject
{
    const DEFAULT_DIR = 'my-wordpress-project';
    const DEFAULT_DB_REGION = 'us-central1';

    const LATEST_WP = 'https://wordpress.org/latest.tar.gz';
    const LATEST_BATCACHE = 'https://downloads.wordpress.org/plugin/batcache.1.4.zip';
    const LATEST_MEMCACHED = 'https://downloads.wordpress.org/plugin/memcached.3.0.1.zip';
    const LATEST_GAE_WP = 'https://downloads.wordpress.org/plugin/google-app-engine.1.6.zip';
    const LATEST_GCS_PLUGIN =  'https://downloads.wordpress.org/plugin/gcs.0.1.4.zip';

    private $input;
    private $output;
    private $helper;
    private $wordPressDir;

    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $helper = null)
    {
        $this->input = $input;
        $this->output = $output;
        $this->filesystem = new Filesystem();
        $this->helper = $helper ?: new QuestionHelper();
    }

    public function initializeProject($dir = '')
    {
        if (empty($dir)) {
            $dir = $this->promptForProjectDir();
        } else {
            $dir = $this->validateProjectDir($dir);
        }

        $this->dir = $dir;
        if (empty($this->wordPressDir)) {
            $this->wordPressDir = $dir;
        }
        $this->report();

        return $this->getDir();
    }

    public function promptForProjectDir()
    {
        $dir = $this->input->getOption('dir');
        if ($dir === self::DEFAULT_DIR) {
            $q = new Question(
                'Please enter a directory path for the new project '
                . "(defaults to $dir):",
                $dir
            );
            $dir = $this->ask($q);
        }

        $q = new ConfirmationQuestion(
            "We will use the directory: <info>$dir</info>. If the directory "
            . 'exists, we will overwrite the contents. Do you want to '
            . 'continue? (Y/n)'
        );
        if (!$this->ask($q)) {
            throw new Exception('Operation canceled.');
        }
        return $this->validateProjectDir($dir);
    }

    /**
     * Set up WordPress for Cloud SQL Generation 2
     */
    public function initializeDatabase()
    {
        // Get the database region
        $region = $this->input->getOption('db_region');
        $availableDbRegions = $this->getAvailableDbRegions();
        if (!in_array($region, $availableDbRegions)) {
            $q = new ChoiceQuestion(
                'Please select the region of your Cloud SQL instance '
                . sprintf('(defaults to %s)', self::DEFAULT_DB_REGION),
                $availableDbRegions,
                array_search(self::DEFAULT_DB_REGION, $availableDbRegions)
            );
            $q->setErrorMessage('DB region %s is invalid.');
            $region = $this->ask($q);
            $this->output->writeln("Using db_region <info>$region</info>");
        }

        // Get the other DB parameters
        $params = $this->askParameters([
            'project_id' => '',
            'db_instance' => '',
            'db_name' => '',
            'db_user' => 'root',
            'db_password' => '',
        ]);

        // Set the database connection string
        $params['db_connection'] = sprintf(
            '%s:%s:%s',
            $params['project_id'],
            $region,
            $params['db_instance']
        );

        // Set parameters for a local database
        $q = new ConfirmationQuestion(
            'Do you want to use the same db user and password for local run? '
            . '(Y/n)'
        );
        if ($this->ask($q)) {
            $params += [
                'local_db_user' => $params['db_user'],
                'local_db_password' => $params['db_password'],
            ];
        } else {
            $params += $this->askParameters([
                'local_db_user' => 'root',
                'local_db_password' => '',
            ]);
        }

        return $params;
    }

    public function downloadWordpress($dir = 'wordpress')
    {
        $tmpDir = sys_get_temp_dir();

        $this->output->writeln('Downloading the WordPress archive...');
        $this->downloadArchive(
            'the WordPress archive',
            $this->input->getOption('wordpress_url'),
            $tmpDir
        );

        // set the wordpress dir
        $this->wordPressDir = $this->getRelativeDir($dir);
        $this->filesystem->rename(
            $tmpDir . DIRECTORY_SEPARATOR . 'wordpress',
            $this->wordPressDir,
            true
        );
        $this->report();
    }

    public function downloadBatcachePlugin()
    {
        $this->output->writeln('Downloading the Batcache plugin...');
        $dir = $this->getWordpressDir();
        $this->downloadArchive(
            'Batcache plugin', self::LATEST_BATCACHE,
            $dir . '/wp-content/plugins'
        );
        $this->report();
        $this->output->writeln('Copying drop-ins...');
        $this->filesystem->copy(
            $dir . '/wp-content/plugins/batcache/advanced-cache.php',
            $dir . '/wp-content/advanced-cache.php'
        );
    }

    public function downloadMemcachedPlugin()
    {
        $this->output->writeln('Downloading the Memcached plugin...');
        $dir = $this->getWordpressDir();
        $this->downloadArchive(
            'Memcached plugin', self::LATEST_MEMCACHED,
            $dir . '/wp-content/plugins'
        );
        $this->report();
        $this->output->writeln('Copying drop-ins...');
        $this->filesystem->copy(
            $dir . '/wp-content/plugins/memcached/object-cache.php',
            $dir . '/wp-content/object-cache.php'
        );
    }

    public function downloadAppEnginePlugin()
    {
        $this->output->writeln('Downloading the appengine-wordpress plugin...');
        $dir = $this->getWordpressDir();
        $this->downloadArchive(
            'App Engine WordPress plugin', self::LATEST_GAE_WP,
            $dir . '/wp-content/plugins'
        );
        $this->report();
    }

    public function downloadGcsPlugin()
    {
        $this->output->writeln('Downloading the GCS plugin...');
        $dir = $this->getWordpressDir();
        $this->downloadArchive(
            'GCS plugin', self::LATEST_GCS_PLUGIN,
            $dir . '/wp-content/plugins'
        );
        $this->report();
    }

    public function generateRandomValueParams()
    {
        return [
            'auth_key' => base64_encode(random_bytes(60)),
            'secure_auth_key' => base64_encode(random_bytes(60)),
            'logged_in_key' => base64_encode(random_bytes(60)),
            'nonce_key' => base64_encode(random_bytes(60)),
            'auth_salt' => base64_encode(random_bytes(60)),
            'secure_auth_salt' => base64_encode(random_bytes(60)),
            'logged_in_salt' => base64_encode(random_bytes(60)),
            'nonce_salt' => base64_encode(random_bytes(60)),
        ];
    }

    public function copyFiles($path, $files, $params = [])
    {
        // uppercase all the keys and prefix with "YOUR_"
        $params = array_combine(
            array_map(function ($key) {
                return 'YOUR_' . strtoupper($key);
            }, array_keys($params)),
            array_values($params)
        );

        parent::copyFiles($path, $files, $params);
        $this->report();
    }

    public function runComposer()
    {
        parent::runComposer();
        $this->report();
    }

    private function report()
    {
        foreach ($this->getInfo() as $value) {
            $this->output->writeln("<info>" . $value . "</info>");
        }
        if ($this->getErrors()) {
            throw new Exception(implode("\n", $this->getErrors()));
        }
    }

    private function askParameters(array $configKeys)
    {
        $params = [];
        foreach ($configKeys as $key => $default) {
            $value = $this->input->getOption($key);
            if ((!$this->input->isInteractive()) && empty($value)) {
                throw new Exception("$key can not be empty.");
            }
            while (empty($value)) {
                if (empty($default)) {
                    $defaultText = '(required)';
                } else {
                    $defaultText = '(defaults to \'' . $default . '\')';
                }
                $q = new Question("Please enter $key $defaultText: ", $default);
                if (strpos($key, 'password') !== false) {
                    $q->setHidden(true);
                    $q->setHiddenFallback(false);
                }
                $value = $this->ask($q);
                if (empty($value)) {
                    $this->output->writeln("<error>$key is required.</error>");
                }
            }
            $params[$key] = $value;
        }
        return $params;
    }

    private function getWordPressDir()
    {
        return $this->wordPressDir;
    }

    private function ask(Question $q)
    {
        return $this->helper->ask($this->input, $this->output, $q);
    }
}
