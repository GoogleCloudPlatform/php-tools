<?php

namespace Google\Cloud\Fixers\ClientUpgradeFixer;

use PhpCsFixer\Tokenizer\Tokens;
use ReflectionMethod;

class ClientVar
{
    public $varName;
    public $className;
    private $startIndex;

    public function __construct(
        $varName,
        $className
    ) {
        $this->varName = $varName;
        $this->className = $className;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @param Tokens $tokens
     * @param int $index
     * @return bool
     */
    public function isDeclaredAt(Tokens $tokens, int $index): bool
    {
        $token = $tokens[$index];
        if ($token->isGivenKind(T_VARIABLE)
            || ($token->isGivenKind(T_STRING) && $tokens[$index-1]->isGivenKind(T_OBJECT_OPERATOR))
        ) {
            $this->startIndex = $index;
            return true;
        }
        return false;
    }

    public function getNewClassName(): string
    {
        return static::getNewClassFromClassname($this->className);
    }

    public static function getNewClassFromClassname(string $className): string
    {
        $parts = explode('\\', $className);
        $shortName = array_pop($parts);
        return implode('\\', $parts) . '\\Client\\' . $shortName;
    }

    public function getRpcMethod(string $rpcName): ?RpcMethod
    {
        // Get the Request class name
        $newClientClass = $this->getNewClassname();
        if (!method_exists($newClientClass, $rpcName)) {
            // If the new method doesn't exist, there's nothing we can do
            return null;
        }
        $method = new ReflectionMethod($newClientClass, $rpcName);
        $parameters = $method->getParameters();
        if (!isset($parameters[0]) || !$type = $parameters[0]->getType()) {
            return null;
        }
        if ($type->isBuiltin()) {
            // If the first parameter is a primitive type, assume this is a helper method
            return null;
        }

        return new RpcMethod($this, $rpcName);
    }

    public function getLineStart(Tokens $tokens): int
    {
        // determine the indent
        $indent = '';
        $lineStart = $this->startIndex;
        $i = 1;
        while (
            $this->startIndex - $i >= 0
            && false === strpos($tokens[$this->startIndex - $i]->getContent(), "\n")
            && $tokens[$this->startIndex - $i]->getId() !== T_OPEN_TAG
        ) {
            $i++;
        }

        return $this->startIndex - $i;
    }

    public static function getClientVarsFromNewKeyword(Tokens $tokens, array $clientShortNames): array
    {
        $clientVars = [];
        foreach ($tokens as $index => $token) {
            // get variables which are set directly
            if (!$token->isGivenKind(T_NEW)) {
                continue;
            }

            $nextToken = $tokens[$tokens->getNextMeaningfulToken($index)];
            $shortName = $nextToken->getContent();
            if (!in_array($shortName, $clientShortNames)) {
                continue;
            }

            if (!$prevIndex = $tokens->getPrevMeaningfulToken($index)) {
                continue;
            }

            if ($tokens[$prevIndex]->getContent() !== '=') {
                continue;
            }

            if (!$prevIndex = $tokens->getPrevMeaningfulToken($prevIndex)) {
                continue;
            }

            if (
                $tokens[$prevIndex]->isGivenKind(T_VARIABLE) || (
                    $tokens[$prevIndex]->isGivenKind(T_STRING)
                    && $tokens[$prevIndex-1]->isGivenKind(T_OBJECT_OPERATOR)
                )
            ) {
                // Handle clients set to $var
                $clientClass = array_search($shortName, $clientShortNames);
                $varName = $tokens[$prevIndex]->getContent();
                $clientVars[$varName] = new ClientVar($varName, $clientClass);
            }
        }

        return $clientVars;
    }

    public static function getClientVarsFromVarTypehint(Tokens $tokens, array $clientShortNames): array
    {
        $clientVars = [];
        foreach ($tokens as $index => $token) {
            // get variables which are set directly
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            if (false === strpos($token->getContent(), '@var')) {
                continue;
            }

            $varToken = $tokens[$tokens->getNextMeaningfulToken($index)];
            if (!$varToken->isGivenKind(T_VARIABLE)) {
                continue;
            }

            $regex = sprintf('/@var (.*) \\%s/', $varToken->getContent());
            if (!preg_match($regex, $token->getContent(), $matches)) {
                continue;
            }

            $shortName = $matches[1];
            $varName = $varToken->getContent();
            if ($clientClass = array_search($shortName, $clientShortNames)) {
                $clientVars[$varName] = new ClientVar($varName, $clientClass);
            }
        }

        return $clientVars;
    }

    public static function getClientVarsFromConfiguration(array $configuration): array
    {
        $clientVars = [];
        if (isset($configuration['clientVars'])) {
            foreach ($configuration['clientVars'] as $varName => $clientClass) {
                $clientVars[$varName] = new ClientVar($varName, $clientClass);
            }
        }
        return $clientVars;
    }
}
