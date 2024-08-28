<?php

namespace Google\Cloud\Samples\Dlp;

// new client surface exists
use Google\Cloud\Dlp\V2\DlpServiceClient;

// Instantiate a client.
$dlpServiceClient = new DlpServiceClient();

// this should update (from detection)
$infoTypes = $dlpServiceClient->listInfoTypes();
// this should also update (from config)
$secrets = $secretmanager->listSecrets('this/is/a/parent');

// these shouldn't update
$operations = $longrunning->listOperations();
$serviceAccount = $storage->getServiceAccount();

class MyClass extends SomethingWhichDefinedAClient
{
    public $parent;

    public function callTheDlpClient()
    {
        $this->dlp->listInfoTypes();
        self::$dlp->listInfoTypes();
    }

    public function callTheDlpClientWithADifferentParent()
    {
        // these should not be updated
        $this->parent->dlp->listInfoTypes();
        $this->parent::$dlp->listInfoTypes();
    }

    public function callSecretManagerWithWildcardParent()
    {
        $this->secretManagerClient->listSecrets();
        $this::$secretManagerClient->listSecrets();
        $this->parent->secretManagerClient->listSecrets();
        $this->parent::$secretManagerClient->listSecrets();
    }
}
