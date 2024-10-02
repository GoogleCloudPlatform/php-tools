<?php

use Symfony\Component\Yaml\Yaml;

$rulesJson = getenv('RULES');
$configPath = getenv('CONFIG_PATH');
$excludePatternsJson = getenv('EXCLUDE_PATTERNS');

if (!$rulesJson) {
    // Use default rules
    $workflow = Yaml::parse(file_get_contents(__DIR__ . '/.github/workflows/code-standards.yml'));
    $rulesJson = $workflow['on']['workflow_call']['inputs']['rules']['default'];
}

$rules = json_decode($rulesJson, true);
$excludePatterns = json_decode($excludePatternsJson ?: '[]', true);

return (new PhpCsFixer\Config())
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in($configPath ?: __DIR__)
            ->notPath($excludePatterns)
    )
;
