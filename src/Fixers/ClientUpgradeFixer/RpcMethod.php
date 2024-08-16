<?php

namespace Google\Cloud\Fixers\ClientUpgradeFixer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionMethod;

class RpcMethod
{
    private ReflectionMethod $legacyReflection;
    private ReflectionMethod $newReflection;

    public function __construct(ClientVar $clientVar, string $methodName)
    {
        $this->legacyReflection = new ReflectionMethod($clientVar->getClassName(), $methodName);
        $this->newReflection = new ReflectionMethod($clientVar->getNewClassName(), $methodName);
    }

    public function getRequestClass(): RequestClass
    {
        $firstParameter = $this->newReflection->getParameters()[0];
        return new RequestClass($firstParameter->getType()->getName());
    }

    public function getRequestSetterTokens(Tokens $tokens, array $arguments, string $indent)
    {
        $argIndex = 0;
        $requestSetterTokens = [];
        foreach ($arguments as $startIndex => $argumentTokens) {
            $setters = $this->getSettersFromTokens($tokens, $startIndex, $argIndex, $argumentTokens);
            foreach ($setters as $setter) {
                $requestSetterTokens = array_merge(
                    $requestSetterTokens,
                    $this->getTokensForSetter($setter, $indent)
                );
            }
            $argIndex++;
        }
        return $requestSetterTokens;
    }

    private function getSettersFromTokens(
        Tokens $tokens,
        int $startIndex,
        int $argIndex,
        array $argumentTokens
    ): array {
        if ($rpcParameter = $this->getParameterAtIndex($argIndex)) {
            // handle array of optional args!
            if ($rpcParameter->isOptionalArgs()) {
                $argumentStart = $tokens->getNextMeaningfulToken($startIndex);
                if ($tokens[$argumentStart]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)
                    || $tokens[$argumentStart]->isGivenKind(T_ARRAY)) {
                    // If the array is being passed directly to the RPC method
                    return $this->settersFromArgumentArray($tokens, $argumentStart);
                }

                if ($tokens[$argumentStart]->isGivenKind(T_VARIABLE)) {
                    // if a variable is being passed in, find where the variable is defined
                    $optionalArgsVar = $tokens[$argumentStart]->getContent();
                    for ($index = $argumentStart - 1; $index > 0; $index--) {
                        $token = $tokens[$index];
                        // Find where the optionalArgs variable is defined
                        if ($token->isGivenKind(T_VARIABLE) && $token->getContent() == $optionalArgsVar) {
                            $nextIndex = $tokens->getNextMeaningfulToken($index);
                            if ($tokens[$nextIndex]->getContent() == '=') {
                                $argumentStart = $tokens->getNextMeaningfulToken($nextIndex);
                                return $this->settersFromOptionalArgsVar($tokens, $argumentStart, $optionalArgsVar);
                            }
                        }
                    }
                }
            } else {
                // Just place the argument tokens in a setter
                $setterName = $rpcParameter->getSetter();
                // Remove leading whitespace
                for ($i = 0; $argumentTokens[$i]->isGivenKind(T_WHITESPACE); $i++) {
                    unset($argumentTokens[$i]);
                }
                return [[$setterName, $argumentTokens]];
            }
        } else {
            // Could not find the argument for $clientFullName and $rpcName at index $argIndex
        }
        return [];
    }

    private function getParameterAtIndex(int $index): ?RpcParameter
    {
        $params = $this->legacyReflection->getParameters();
        if (isset($params[$index])) {
            return new RpcParameter($params[$index]);
        }

        return null;
    }

    private function getSetterIndiciesFromInlineArray(Tokens $tokens, int $index, int $closeIndex)
    {
        $arrayEntries = $tokens->findGivenKind(T_DOUBLE_ARROW, $index, $closeIndex);
        $nestedArrays = $tokens->findGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN, $index + 1, $closeIndex);
        $nestedLegacyArrays = $tokens->findGivenKind(T_ARRAY, $index + 1, $closeIndex);

        // skip nested arrays
        foreach ($arrayEntries as $doubleArrowIndex => $doubleArrowIndexToken) {
            foreach ($nestedArrays as $nestedArrayIndex => $nestedArrayIndexToken) {
                $nestedArrayCloseIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $nestedArrayIndex);
                if ($doubleArrowIndex > $nestedArrayIndex && $doubleArrowIndex < $nestedArrayCloseIndex) {
                    unset($arrayEntries[$doubleArrowIndex]);
                }
            }
            foreach ($nestedLegacyArrays as $nestedLegacyArrayIndex => $nestedLegacyArrayIndexToken) {
                $nestedLegacyArrayCloseIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $nestedLegacyArrayIndex + 1);
                if ($doubleArrowIndex > $nestedLegacyArrayIndex && $doubleArrowIndex < $nestedLegacyArrayCloseIndex) {
                    unset($arrayEntries[$doubleArrowIndex]);
                }
            }
        }

        return array_keys($arrayEntries);
    }

    private function getTokensForSetter(array $setter, string $indent): array
    {
        list($method, $varTokens) = $setter;

        $tokens = [
            // whitespace (assume 4 spaces)
            new Token([T_WHITESPACE, PHP_EOL . $indent . '    ']),
            // setter method
            new Token([T_OBJECT_OPERATOR, '->']),
            new Token([T_STRING, $method]),
            // setter value
            new Token('('),
        ];
        // merge in var tokens
        $tokens = array_merge($tokens, $varTokens);

        // add closing parenthesis
        $tokens[] = new Token(')');

        return $tokens;
    }

    private function settersFromArgumentArray(Tokens $tokens, int $index): array
    {
        $setters = [];
        $legacyArraySyntax = $tokens[$index]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN);
        $closeIndex = $legacyArraySyntax
            ? $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index)
            : $closeIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, ++$index);

        $arrayEntryIndices = $this->getSetterIndiciesFromInlineArray($tokens, $index, $closeIndex, $legacyArraySyntax);

        foreach ($arrayEntryIndices as $i => $doubleArrowIndex) {
            $keyIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$keyIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }
            $setterName = 'set' . ucfirst(trim($tokens[$keyIndex]->getContent(), '"\''));
            $tokens->removeLeadingWhitespace($doubleArrowIndex + 1);
            $valueEnd = isset($arrayEntryIndices[$i+1])
                ? $tokens->getPrevTokenOfKind($arrayEntryIndices[$i+1], [new Token(',')])
                : $closeIndex;
            $varTokens = array_slice($tokens->toArray(), $doubleArrowIndex + 1, $valueEnd - $doubleArrowIndex - 1);
            // Remove trailing whitespace
            for ($i = count($varTokens)-1; $varTokens[$i]->isGivenKind(T_WHITESPACE); $i--) {
                unset($varTokens[$i]);
            }
            // Remove trailing commas
            for ($i = count($varTokens)-1; $varTokens[$i]->getContent() === ','; $i--) {
                unset($varTokens[$i]);
            }
            // Remove leading whitespace
            for ($i = 0; $varTokens[$i]->isGivenKind(T_WHITESPACE); $i++) {
                unset($varTokens[$i]);
            }
            $setters[] = [$setterName, $varTokens];
            $index = $valueEnd;
        }
        return $setters;
    }

    private function settersFromOptionalArgsVar(Tokens $tokens, int $index, string $optionalArgsVar): array
    {
        $setters = [];
        if (!$tokens[$index]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)
            && !$tokens[$index]->isGivenKind(T_ARRAY)) {
            return $setters;
        }

        $closeIndex = $tokens[$index]->isGivenKind(CT::T_ARRAY_SQUARE_BRACE_OPEN)
            ? $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $index)
            : $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index + 1);
        $arrayEntryIndices = $this->getSetterIndiciesFromInlineArray($tokens, $index, $closeIndex);

        foreach ($arrayEntryIndices as $i => $doubleArrowIndex) {
            $keyIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$keyIndex]->isGivenKind(T_CONSTANT_ENCAPSED_STRING)) {
                continue;
            }

            $setterName = 'set' . ucfirst(trim($tokens[$keyIndex]->getContent(), '"\''));
            $varTokens = [
                new Token([T_VARIABLE, $optionalArgsVar]),
                new Token([CT::T_ARRAY_SQUARE_BRACE_OPEN, '[']),
                clone $tokens[$keyIndex],
                new Token([CT::T_ARRAY_SQUARE_BRACE_CLOSE, ']']),
            ];
            $setters[] = [$setterName, $varTokens];
            $valueEnd = isset($arrayEntryIndices[$i+1])
                ? $tokens->getPrevTokenOfKind($arrayEntryIndices[$i+1], [new Token(',')])
                : $closeIndex;
            $index = $valueEnd;
        }

        return $setters;
    }
}
