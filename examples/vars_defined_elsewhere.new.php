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
$secrets = $secretmanagerFromConfig->listSecrets($listSecretsRequest);

// these shouldn't update
$operations = $longrunning->listOperations();
$serviceAccount = $storage->getServiceAccount();

class MyClass extends SomethingWhichDefinedAClient
{
    public $parent;

    public function callTheDlpClient()
    {
        // These are updated from values in the "clientVars" confguration
        $listInfoTypesRequest2 = new ListInfoTypesRequest();
        $this->dlpFromConfig->listInfoTypes($listInfoTypesRequest2);
        $listInfoTypesRequest3 = new ListInfoTypesRequest();
        self::$dlpFromConfig->listInfoTypes($listInfoTypesRequest3); // @phpstan-ignore-line
    }

    public function callTheDlpClientWithADifferentParent()
    {
        // these should not be updated
        $this->parent->dlpFromConfig->listInfoTypes();
        $this->parent::$dlpFromConfig->listInfoTypes();
    }

    public function callSecretManagerWithWildcardParent()
    {
        // These are updated from values in the "clientVars" confguration
        $listSecretsRequest2 = new ListSecretsRequest();
        $this->secretManagerClientFromConfig->listSecrets($listSecretsRequest2);
        $listSecretsRequest3 = new ListSecretsRequest();
        $this::$secretManagerClientFromConfig->listSecrets($listSecretsRequest3); // @phpstan-ignore-line
        $listSecretsRequest4 = new ListSecretsRequest();
        $this->parent->secretManagerClientFromConfig->listSecrets($listSecretsRequest4);
        $listSecretsRequest5 = new ListSecretsRequest();
        $this->parent::$secretManagerClientFromConfig->listSecrets($listSecretsRequest5);
    }
}

class SomethingWhichDefinedAClient
{
    public $dlpFromConfig;
    public $secretManagerClientFromConfig;
}
