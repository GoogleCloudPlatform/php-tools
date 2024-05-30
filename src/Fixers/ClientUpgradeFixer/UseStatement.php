<?php

namespace Google\Cloud\Fixers\ClientUpgradeFixer;

use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

class UseStatement
{
    public static function getTokensFromClassName(string $className): array
    {
        $tokens = [
            new Token([T_USE, 'use']),
            new Token([T_WHITESPACE, ' ']),
        ];
        foreach (explode('\'', $className) as $part) {
            $tokens[] = new Token([T_STRING, $part]);
            $tokens[] = new Token([T_NS_SEPARATOR, '\\']);
        }
        array_pop($tokens); // remove last namespace separator
        $tokens[] = new Token(';');
        return $tokens;
    }

    public static function getImportedClients(array $useDeclarations): array
    {
        $clients = [];
        foreach ($useDeclarations as $useDeclaration) {
            $clientClass = $useDeclaration->getFullName();
            $clientShortName = $useDeclaration->getShortName();
            if (
                0 === strpos($clientClass, 'Google\\')
                && 'Client' === substr($clientShortName, -6)
                && false === strpos($clientClass, '\\Client\\')
                && class_exists($clientClass)
            ) {
                if (false !== strpos(get_parent_class($clientClass), '\Gapic\\')) {
                    $clients[$clientClass] = $useDeclaration;
                }
            }
        }
        return $clients;
    }

    public static function getUseDeclarations(Tokens $tokens): array
    {
        return (new NamespaceUsesAnalyzer())->getDeclarationsFromTokens($tokens);
    }
}
