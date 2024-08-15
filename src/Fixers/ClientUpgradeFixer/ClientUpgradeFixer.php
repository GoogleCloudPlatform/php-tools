<?php

namespace Google\Cloud\Fixers\ClientUpgradeFixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;

class ClientUpgradeFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    use ConfigurableFixerTrait;

    /**
     * Check if the fixer is a candidate for given Tokens collection.
     *
     * Fixer is a candidate when the collection contains tokens that may be fixed
     * during fixer work. This could be considered as some kind of bloom filter.
     * When this method returns true then to the Tokens collection may or may not
     * need a fixing, but when this method returns false then the Tokens collection
     * need no fixing for sure.
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return true;
    }

    /**
     * Defines the available configuration options of the fixer.
     */
    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('clientVars', 'A map of client variables to their new class names'))
                ->setAllowedTypes(['array'])
                ->setDefault([])
                ->getOption(),
        ]);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        if (!class_exists('Google\Auth\OAuth2')) {
            throw new \LogicException(
                'In order for Google\Cloud\NewSurfaceFixer to work, you must install the google '
                . 'cloud client library and include its autoloader in .php-cs-fixer.dist.php'
            );
        }

        $clients = [];
        $useDeclarations = UseStatement::getUseDeclarations($tokens);
        foreach (UseStatement::getImportedClients($useDeclarations) as $clientClass => $useDeclaration) {
            $newClientName = ClientVar::getNewClassFromClassname($clientClass);
            if (class_exists($newClientName)) {
                // Rename old clients to new namespaces
                $tokens->overrideRange(
                    $useDeclaration->getStartIndex(),
                    $useDeclaration->getEndIndex(),
                    UseStatement::getTokensFromClassName($newClientName)
                );
                $clients[] = $clientClass;
            }
        }

        // Get variable names for all clients
        $clientShortNames = [];
        foreach ($clients as $clientClass) {
            // Save the client shortnames so we can search for them below
            $parts = explode('\\', $clientClass);
            $shortName = array_pop($parts);
            $clientShortNames[$clientClass] = $shortName;
        }
        $clientVars = array_merge(
            ClientVar::getClientVarsFromNewKeyword($tokens, $clientShortNames),
            ClientVar::getClientVarsFromVarTypehint($tokens, $clientShortNames),
            ClientVar::getClientVarsFromConfiguration($this->configuration),
        );

        // Find the RPC methods being called on the clients
        $classesToImport = [];
        $counter = new RequestVariableCounter();
        $importStart = $this->getImportStart($tokens);
        $insertStart = null;
        for ($index = 0; $index < count($tokens); $index++) {
            $clientVar = $clientVars[$tokens[$index]->getContent()] ?? null;
            if (is_null($clientVar)) {
                // The token does not contain a client var
                continue;
            }

            if (!$clientVar->isDeclaredAt($tokens, $index)) {
                // The token looks like our client var but isn't
                continue;
            }

            $operatorIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$operatorIndex]->isGivenKind(T_OBJECT_OPERATOR)) {
                // The client var is not calling a method
                continue;
            }

            // The method being called by the client variable
            $methodIndex = $tokens->getNextMeaningfulToken($operatorIndex);
            if (!$rpcMethod = $clientVar->getRpcMethod($tokens[$methodIndex]->getContent())) {
                // The method doesn't exist, or is not an RPC call
                continue;
            }

            // Get the arguments being passed to the RPC method
            [$arguments, $firstIndex, $lastIndex] = RpcParameter::getRpcCallParameters($tokens, $methodIndex);

            // determine where to insert the new tokens
            $lineStart = $clientVar->getLineStart($tokens);

            // Handle differently when we are dealing with inline PHP
            $isInlinePhpCall = $tokens[$lineStart]->getId() === T_OPEN_TAG;

            $indent = '';
            if (!$isInlinePhpCall) {
                $indent = str_replace("\n", '', $tokens[$lineStart]->getContent());
            }

            $requestClass = $rpcMethod->getRequestClass();
            $requestVarName = $counter->getNextVariableName($requestClass->getShortName());

            // Tokens for the setters called on the new request object
            $requestSetterTokens = $rpcMethod->getRequestSetterTokens($tokens, $arguments, $indent);

            // Tokens for initializing the new request variable
            $newRequestTokens = $requestClass->getInitTokens(
                $requestVarName,
                count($requestSetterTokens) > 0
            );

            // Add them together
            $newRequestTokens = array_merge(
                [new Token([T_WHITESPACE,  PHP_EOL . $indent])],
                $newRequestTokens,
                $requestSetterTokens,
                [new Token(';')]
            );

            // When inserting for inline PHP, add a newline before the first request variable
            if ($isInlinePhpCall && $counter->isFirstVar()) {
                array_unshift($newRequestTokens, new Token([T_WHITESPACE, PHP_EOL]));
            }

            // Determine where the request variable tokens should be inserted
            if ($isInlinePhpCall) {
                // If we are inline, insert right before the first closing PHP tag
                if (is_null($insertStart)) {
                    $insertStart = $tokens->getNextTokenOfKind($importStart, ['?>', [T_CLOSE_TAG]]) - 1;
                }
            } else {
                // else, insert at beginning of the line of the original RPC call
                $insertStart = $lineStart;
            }

            // insert the request variable tokens
            $tokens->insertAt($insertStart, $newRequestTokens);

            // Replace the original RPC call arguments with the new request variable
            $tokens->overrideRange(
                $firstIndex + 1 + count($newRequestTokens),
                $lastIndex - 1 + count($newRequestTokens),
                [new Token([T_VARIABLE, $requestVarName])]
            );

            // Increment the current $index and $insertStart
            $index = $firstIndex + 1 + count($newRequestTokens);
            if ($isInlinePhpCall) {
                $insertStart = $insertStart + count($newRequestTokens);
            }

            // Add the request class to be imported later
            $classesToImport[$requestClass->getName()] = $requestClass;
        }

        // Import the new request classes
        if ($classesToImport) {
            $importedClasses = array_map(fn ($useDeclaration) => $useDeclaration->getFullName(), $useDeclarations);
            $classesToImport = array_filter(
                $classesToImport,
                fn ($requestClass) => !isset($importedClasses[$requestClass->getName()])
            );
            $requestClassImportTokens = array_map(
                fn ($requestClass) => $requestClass->getImportTokens(),
                array_values($classesToImport)
            );
            $tokens->insertAt($importStart, array_merge(...$requestClassImportTokens));
            // Ensure new imports are in the correct order
            $orderFixer = new OrderedImportsFixer();
            $orderFixer->fix($file, $tokens);
        }
    }

    private function getImportStart(Tokens $tokens)
    {
        $useDeclarations = UseStatement::getUseDeclarations($tokens);
        if (count($useDeclarations) > 0) {
            return $useDeclarations[count($useDeclarations) - 1]->getEndIndex() + 1;
        }

        // There will be no changes made if there are no imports, so this logic
        // should not matter

        return $tokens->getNextMeaningfulToken(0);
    }

    /**
     * Returns the definition of the fixer.
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition('Upgrade code to the new Google Cloud PHP client surface', []);
    }

    /**
     * Returns the name of the fixer.
     *
     * The name must be all lowercase and without any spaces.
     *
     * @return string The name of the fixer
     */
    public function getName(): string
    {
        return 'GoogleCloud/upgrade_clients';
    }

    /**
     * {@inheritdoc}
     *
     * Must run before OrderedImportsFixer.
     */
    public function getPriority(): int
    {
        return 0;
    }
}
