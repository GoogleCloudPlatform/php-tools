<?php

use Symfony\Component\Yaml\Yaml;

if (!$rulesJson = getenv('RULES')) {
    $workflow = Yaml::parse(file_get_contents(__DIR__ . '/.github/workflows/code-standards.yml'));
    $rulesJson = $workflow['on']['workflow_call']['inputs']['rules']['default'];
}

$excludePatterns = json_decode(getenv('EXCLUDE_PATTERNS') ?: '[]', true);

return (new PhpCsFixer\Config())
    ->setRules(json_decode($rulesJson, true))
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(getenv('CONFIG_PATH') ?: __DIR__)
            ->notPath($excludePatterns)
    )
;
