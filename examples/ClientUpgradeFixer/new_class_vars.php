<?php

namespace Google\Cloud\Samples\Dlp;

// new client surface doesn't exist (yet)
use Google\ApiCore\LongRunning\OperationsClient;
// new client surface exists
use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
// invalid client
use Google\Cloud\Dlp\V2\ListInfoTypesRequest;
// new client surface exists
use Google\Cloud\Dlp\V2\NonexistentClient;
// new client surface won't exist (not a generator client)
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\ListSecretsRequest;
use Google\Cloud\Storage\StorageClient;

class ClientWrapper extends TestCase
{
    public $dlp;
    public $secretmanager;

    public function __comnstruct()
    {
        $this->dlp = new DlpServiceClient();
        $this->secretmanager = new SecretManagerServiceClient();
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
}

// Instantiate a wrapping object.
$wrapper = new ClientWrapper();

// these should update
$listInfoTypesRequest2 = new ListInfoTypesRequest();
$infoTypes = $wrapper->dlp->listInfoTypes($listInfoTypesRequest2);
$listSecretsRequest2 = (new ListSecretsRequest())
    ->setParent('this/is/a/parent');
$secrets = $wrapper->secretmanager->listSecrets($listSecretsRequest2);
