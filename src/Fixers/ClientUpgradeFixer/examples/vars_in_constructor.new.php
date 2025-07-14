<?php

namespace Google\Cloud\Samples\VarsInConstructor;

// new client surface exists
use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
use Google\Cloud\Dlp\V2\ListInfoTypesRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\ListSecretsRequest;

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
        $listInfoTypesRequest = new ListInfoTypesRequest();
        $infoTypes = $this->dlp->listInfoTypes($listInfoTypesRequest);
    }

    public function callSecretManager()
    {
        $listSecretsRequest = (new ListSecretsRequest())
            ->setParent('this/is/a/parent');
        $secrets = $this->secretmanager->listSecrets($listSecretsRequest);
    }

    public function callStatic()
    {
        // These shouldn't update
        $secrets = self::$dlp->listInfoTypes(); // @phpstan-ignore-line
        $secrets = self::$secretmanager->listSecrets('this/is/a/parent'); // @phpstan-ignore-line

        // This should
        $listInfoTypesRequest2 = new ListInfoTypesRequest();
        $secrets = self::$staticDlp->listInfoTypes($listInfoTypesRequest2);
    }
}
