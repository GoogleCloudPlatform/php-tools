<?php

namespace Google\Cloud\Fixers\ClientUpgradeFixer;

class RequestVariableCounter
{
    private array $varCounts = [];

    public function isFirstVar(): bool
    {
        return count($this->varCounts) == 1
            && array_values($this->varCounts)[0] == 1;
    }

    public function getNextVariableName(string $shortName): string
    {
        if (!isset($this->varCounts[$shortName])) {
            $this->varCounts[$shortName] = 0;
        }
        $num = (string) ++$this->varCounts[$shortName];
        // determine $request variable name depending on call count
        return sprintf(
            '$%s%s',
            lcfirst($shortName),
            $num == '1' ? '' : $num
        );
    }
}
