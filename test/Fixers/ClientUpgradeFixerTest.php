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

    public function setUp(): void
    {
        $this->fixer = new ClientUpgradeFixer();
        $this->fixer->configure([
            'clientVars' => [
                '$secretmanager' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',
                '$dlpClient' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',
                '$this->dlp' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',
                'self::$dlp' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',
                'secretManagerClient' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',
                '$secretManagerClient' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',
            ]
        ]);
    }

    /**
     * @dataProvider provideLegacySamples
     */
    public function testLegacySamples($filename)
    {
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
        return array_map(
            fn ($file) => [basename($file)],
            array_filter(
                glob(self::SAMPLES_DIR . '*'),
                fn ($file) => '.legacy.php' === substr(basename($file), -11)
            )
        );
    }
}
