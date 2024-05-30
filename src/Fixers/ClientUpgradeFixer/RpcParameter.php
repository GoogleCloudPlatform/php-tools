<?php

namespace Google\Cloud\Fixers\ClientUpgradeFixer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionParameter;

class RpcParameter
{
    private ReflectionParameter $reflection;

    public function __construct(ReflectionParameter $reflection)
    {
        $this->reflection = $reflection;
    }

    public function isOptionalArgs(): bool
    {
        return $this->reflection->getName() === 'optionalArgs';
    }

    public function getSetter(): string
    {
        return 'set' . ucfirst($this->reflection->getName());
    }

    public static function getRpcCallParameters(Tokens $tokens, int $startIndex)
    {
        $arguments = [];
        $nextIndex = $tokens->getNextMeaningfulToken($startIndex);
        $lastIndex = null;
        if ($tokens[$nextIndex]->getContent() == '(') {
            $startIndex = $nextIndex;
            $lastIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $nextIndex);
            $nextArgumentEnd = self::getNextArgumentEnd($tokens, $nextIndex);
            while ($nextArgumentEnd != $nextIndex) {
                $argumentTokens = [];
                for ($i = $nextIndex + 1; $i <= $nextArgumentEnd; $i++) {
                    $argumentTokens[] = $tokens[$i];
                }

                $arguments[$nextIndex] = $argumentTokens;
                $nextIndex = $tokens->getNextMeaningfulToken($nextArgumentEnd);
                $nextArgumentEnd = self::getNextArgumentEnd($tokens, $nextIndex);
            }
        }

        return [$arguments, $startIndex, $lastIndex];
    }

    private static function getNextArgumentEnd(Tokens $tokens, int $index): int
    {
        $nextIndex = $tokens->getNextMeaningfulToken($index);
        $nextToken = $tokens[$nextIndex];

        while ($nextToken->equalsAny([
            '$',
            '[',
            '(',
            [CT::T_ARRAY_INDEX_CURLY_BRACE_OPEN],
            [CT::T_ARRAY_SQUARE_BRACE_OPEN],
            [CT::T_DYNAMIC_PROP_BRACE_OPEN],
            [CT::T_DYNAMIC_VAR_BRACE_OPEN],
            [CT::T_NAMESPACE_OPERATOR],
            [T_NS_SEPARATOR],
            [T_STATIC],
            [T_STRING],
            [T_CONSTANT_ENCAPSED_STRING],
            [T_VARIABLE],
            [T_NEW],
            [T_ARRAY],
        ])) {
            $blockType = Tokens::detectBlockType($nextToken);

            if (null !== $blockType) {
                $nextIndex = $tokens->findBlockEnd($blockType['type'], $nextIndex);
            }

            $index = $nextIndex;
            $nextIndex = $tokens->getNextMeaningfulToken($nextIndex);
            $nextToken = $tokens[$nextIndex];
        }

        if ($nextToken->isGivenKind(T_OBJECT_OPERATOR)) {
            return self::getNextArgumentEnd($tokens, $nextIndex);
        }

        if ($nextToken->isGivenKind(T_PAAMAYIM_NEKUDOTAYIM)) {
            return self::getNextArgumentEnd($tokens, $tokens->getNextMeaningfulToken($nextIndex));
        }

        if ('"' === $nextToken->getContent()) {
            if ($endIndex = $tokens->getNextTokenOfKind($nextIndex + 1, ['"'])) {
                return $endIndex;
            }
        }

        return $index;
    }
}
