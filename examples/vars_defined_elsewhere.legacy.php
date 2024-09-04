<?php

namespace Google\Cloud\Samples\Dlp;

// new client surface exists
use Google\Cloud\Dlp\V2\DlpServiceClient;

// Instantiate a client.
$dlpServiceClient = new DlpServiceClient();

// this should update (from detection)
$infoTypes = $dlpServiceClient->listInfoTypes();
// this should also update (from config)
$secrets = $secretmanagerFromConfig->listSecrets('this/is/a/parent');

// these shouldn't update
$operations = $longrunning->listOperations();
$serviceAccount = $storage->getServiceAccount();

class MyClass extends SomethingWhichDefinedAClient
{
    public $parent;

    public function callTheDlpClient()
    {
        // These are updated from values in the "clientVars" confguration
        $this->dlpFromConfig->listInfoTypes();
        self::$dlpFromConfig->listInfoTypes(); // @phpstan-ignore-line
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
        $this->secretManagerClientFromConfig->listSecrets();
        $this::$secretManagerClientFromConfig->listSecrets(); // @phpstan-ignore-line
        $this->parent->secretManagerClientFromConfig->listSecrets();
        $this->parent::$secretManagerClientFromConfig->listSecrets();
    }
}

class SomethingWhichDefinedAClient
{
    public $dlpFromConfig;
    public $secretManagerClientFromConfig;
}
