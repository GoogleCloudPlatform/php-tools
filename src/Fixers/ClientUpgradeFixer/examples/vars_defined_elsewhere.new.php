<?php

namespace Google\Cloud\Samples\Dlp;

// new client surface exists
use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
use Google\Cloud\Dlp\V2\ListInfoTypesRequest;
use Google\Cloud\SecretManager\V1\ListSecretsRequest;

// Instantiate a client.
$dlpServiceClient = new DlpServiceClient();

// this should update (from detection)
$listInfoTypesRequest = new ListInfoTypesRequest();
$infoTypes = $dlpServiceClient->listInfoTypes($listInfoTypesRequest);
// this should also update (from config)
$listSecretsRequest = (new ListSecretsRequest())
    ->setParent('this/is/a/parent');
$secrets = $secretmanager->listSecrets($listSecretsRequest);

// these shouldn't update
$operations = $longrunning->listOperations();
$serviceAccount = $storage->getServiceAccount();

class MyClass extends SomethingWhichDefinedAClient
{
    public $parent;

    public function callTheDlpClient()
    {
        $listInfoTypesRequest2 = new ListInfoTypesRequest();
        $this->dlp->listInfoTypes($listInfoTypesRequest2);
        $listInfoTypesRequest3 = new ListInfoTypesRequest();
        self::$dlp->listInfoTypes($listInfoTypesRequest3);
    }

    public function callTheDlpClientWithADifferentParent()
    {
        // these should not be updated
        $this->parent->dlp->listInfoTypes();
        $this->parent::$dlp->listInfoTypes();
    }

    public function callSecretManagerWithWildcardParent()
    {
        $listSecretsRequest2 = new ListSecretsRequest();
        $this->secretManagerClient->listSecrets($listSecretsRequest2);
        $listSecretsRequest3 = new ListSecretsRequest();
        $this::$secretManagerClient->listSecrets($listSecretsRequest3);
        $listSecretsRequest4 = new ListSecretsRequest();
        $this->parent->secretManagerClient->listSecrets($listSecretsRequest4);
        $listSecretsRequest5 = new ListSecretsRequest();
        $this->parent::$secretManagerClient->listSecrets($listSecretsRequest5);
    }
}
