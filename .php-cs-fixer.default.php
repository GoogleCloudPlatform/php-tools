<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$workflow = Yaml::parse(file_get_contents(__DIR__ . '/.github/workflows/code-standards.yml'));
$rules = json_decode($workflow['on']['workflow_call']['inputs']['rules']['default'], true);

return (new PhpCsFixer\Config())
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
    )
;
