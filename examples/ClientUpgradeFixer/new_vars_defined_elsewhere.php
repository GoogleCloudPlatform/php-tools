<?php

namespace Google\Cloud\Samples\Dlp;

// new client surface exists
use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
use Google\Cloud\Dlp\V2\ListInfoTypesRequest;
use Google\Cloud\SecretManager\V1\ListSecretsRequest;

// Instantiate a client.
$dlp = new DlpServiceClient();

// this should update (from detection)
$listInfoTypesRequest = new ListInfoTypesRequest();
$infoTypes = $dlp->listInfoTypes($listInfoTypesRequest);
// this should also update (from config)
$listSecretsRequest = (new ListSecretsRequest())
    ->setParent('this/is/a/parent');
$secrets = $secretmanager->listSecrets($listSecretsRequest);

// these shouldn't update
$operations = $longrunning->listOperations();
$serviceAccount = $storage->getServiceAccount();

class MyClass extends SomethingWhichDefinedAClient
{
    public function callTheDlpClient()
    {
        $listInfoTypesRequest2 = new ListInfoTypesRequest();
        $this->dlpClient->listInfoTypes($listInfoTypesRequest2);
    }

    public function callTheDlpClientStatic()
    {
        $listInfoTypesRequest3 = new ListInfoTypesRequest();
        self::$dlpClient->listInfoTypes($listInfoTypesRequest3);
    }
}