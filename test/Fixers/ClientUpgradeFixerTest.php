<?php

namespace Google\Cloud\Fixer\Test;

use Google\Cloud\Fixers\ClientUpgradeFixer\ClientUpgradeFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class ClientUpgradeFixerTest extends TestCase
{
    private ClientUpgradeFixer $fixer;

    private const SAMPLES_DIR = __DIR__ . '/../../src/Fixers/ClientUpgradeFixer/examples/';

    /**
     * @dataProvider provideLegacySamples
     */
    public function testLegacySamples(string $filename, array $config = [])
    {
        $this->fixer = new ClientUpgradeFixer();
        if ($config) {
            $this->fixer->configure($config);
        }

        $legacyFilepath = self::SAMPLES_DIR . $filename;
        $newFilepath = str_replace('legacy.', 'new.', $legacyFilepath);
        $tokens = Tokens::fromCode(file_get_contents($legacyFilepath));
        $fileInfo = new SplFileInfo($legacyFilepath);
        $this->fixer->fix($fileInfo, $tokens);
        $code = $tokens->generateCode();
        if (!file_exists($newFilepath) || file_get_contents($newFilepath) !== $code) {
            if (getenv('UPDATE_FIXTURES=1')) {
                file_put_contents($newFilepath, $code);
                $this->markTestIncomplete('Updated fixtures');
            }
            if (!file_exists($newFilepath)) {
                $this->fail('File does not exist');
            }
        }
        $this->assertStringEqualsFile($newFilepath, $code);
    }

    public static function provideLegacySamples()
    {
        $samples = array_map(
            fn ($file) => [basename($file)],
            array_filter(
                glob(self::SAMPLES_DIR . '*'),
                fn ($file) => '.legacy.php' === substr(basename($file), -11)
            )
        );
        $samples = array_combine(
            array_map(fn ($file) => substr($file[0], 0, -11), $samples),
            $samples
        );

        // add custom config for vars_defined_elsewhere samples
        $samples['vars_defined_elsewhere'][] = [
            'clientVars' => [
                '$secretmanagerFromConfig' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',
                '$this->dlpFromConfig' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',
                'self::$dlpFromConfig' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',
                'secretManagerClientFromConfig' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',
                '$secretManagerClientFromConfig' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',
            ]
        ];

        return $samples;
    }
}
