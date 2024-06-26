<?php

namespace Google\Cloud\Fixers\ClientUpgradeFixer;

use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionClass;

class RequestClass
{
    private ReflectionClass $reflection;

    public function __construct(string $className)
    {
        $this->reflection = new ReflectionClass($className);
    }

    public function getShortName(): string
    {
        return $this->reflection->getShortName();
    }

    public function getName(): string
    {
        return $this->reflection->getName();
    }

    public function getImportTokens(): array
    {
        return array_merge(
            [new Token([T_WHITESPACE, PHP_EOL])],
            UseStatement::getTokensFromClassName($this->getName())
        );
    }

    public function getInitTokens(string $requestVarName, bool $parenthesis)
    {
        // Add the code for creating the $request variable
        return array_filter([
            new Token([T_VARIABLE, $requestVarName]),
            new Token([T_WHITESPACE, ' ']),
            new Token('='),
            new Token([T_WHITESPACE, ' ']),
            $parenthesis ? new Token('(') : null,
            new Token([T_NEW, 'new']),
            new Token([T_WHITESPACE, ' ']),
            new Token([T_STRING, $this->getShortName()]),
            new Token('('),
            new Token(')'),
            $parenthesis ? new Token(')') : null,
        ]);
    }
}
