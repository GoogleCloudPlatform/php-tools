<?php

namespace Google\Cloud\Samples\Dlp;

// new client surface exists
use Google\Cloud\Dlp\V2\DlpServiceClient;

// Instantiate a client.
$dlp = new DlpServiceClient();

// this should update (from detection)
$infoTypes = $dlp->listInfoTypes();
// this should also update (from config)
$secrets = $secretmanager->listSecrets('this/is/a/parent');

// these shouldn't update
$operations = $longrunning->listOperations();
$serviceAccount = $storage->getServiceAccount();

class MyClass extends SomethingWhichDefinedAClient
{
    public function callTheDlpClient()
    {
        $this->dlpClient->listInfoTypes();
    }

    public function callTheDlpClientStatic()
    {
        self::$dlpClient->listInfoTypes();
    }
}