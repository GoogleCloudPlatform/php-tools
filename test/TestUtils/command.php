<?php

require __DIR__ . '/../../vendor/autoload.php';

use Google\Cloud\TestUtils\test\ExecuteCommandTraitTest;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

$application = new Application('Test App');
$application->add(new Command('test'))
    ->addArgument('foo', InputArgument::OPTIONAL, 'fake argument')
    ->addOption('bar', '', InputOption::VALUE_REQUIRED, 'fake option')
    ->addOption('exception', '', InputOption::VALUE_NONE, 'throw an exception once')
    ->setCode(function ($input, $output) {
        printf('foo: %s, bar: %s',
            $input->getArgument('foo'),
            $input->getOption('bar'));

        if ($input->getOption('exception')) {
            // Increment call count so we know how many times we've retried
            ExecuteCommandTraitTest::incrementCallCount();
            throw new Exception('Threw an exception!');
        }
    });

return $application;
