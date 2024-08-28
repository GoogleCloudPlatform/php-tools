<?php

namespace Google\Cloud\Samples\Dlp;

// new client surface doesn't exist (yet)
use Google\ApiCore\LongRunning\OperationsClient;
// new client surface exists
use Google\Cloud\Dlp\V2\DlpServiceClient;
// invalid client
use Google\Cloud\Dlp\V2\NonexistentClient;
// new client surface exists
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
// new client surface won't exist (not a generator client)
use Google\Cloud\Storage\StorageClient;

// Instantiate some clients.
/** @var DlpServiceClient $dlp */
$dlp = get_dlp_service_client();

/** @var OperationsClient $longrunning */
$longrunning = new OperationsClient();

/** @var SecretManagerServiceClient $secretmanager */
$secretmanager = get_secretmanager_service_client();

// these should update
$infoTypes = $dlp->listInfoTypes();
$secrets = $secretmanager->listSecrets('this/is/a/parent');

// these shouldn't update
$operations = $longrunning->listOperations();

function get_dlp_service_client()
{
    return new DlpServiceClient();
}

function get_secretmanager_service_client()
{
    return new SecretManagerServiceClient();
}

function get_operations_service_client()
{
    return new DlpServiceClient();
}

class VariablesInsideClass extends TestCase
{
    /** @var DlpServiceClient $dlp */
    private $dlp;
    private SecretManagerServiceClient $secretmanager;

    public function callDlp()
    {
        // These should update
        $infoTypes = $this->dlp->listInfoTypes();
        $secrets = $this->secretmanager->listSecrets('this/is/a/parent');
    }
}
