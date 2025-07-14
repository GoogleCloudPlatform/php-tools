<?php

namespace Google\Cloud\Samples\VarsInConstructor;

// new client surface exists
use Google\Cloud\Dlp\V2\DlpServiceClient;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;

class ClientWrapper
{
    private static DlpServiceClient $staticDlp;

    public function __construct(
        private DlpServiceClient $dlp,
        private SecretManagerServiceClient $secretmanager
    ) {
    }

    public function callDlp()
    {
        $infoTypes = $this->dlp->listInfoTypes();
    }

    public function callSecretManager()
    {
        $secrets = $this->secretmanager->listSecrets('this/is/a/parent');
    }

    public function callStatic()
    {
        // These shouldn't update
        $secrets = self::$dlp->listInfoTypes(); // @phpstan-ignore-line
        $secrets = self::$secretmanager->listSecrets('this/is/a/parent'); // @phpstan-ignore-line

        // This should
        $secrets = self::$staticDlp->listInfoTypes();
    }
}
