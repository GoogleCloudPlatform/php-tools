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

// Instantiate a client.
$dlp = new DlpServiceClient();
$longrunning = new OperationsClient();
$secretmanager = new SecretManagerServiceClient();
$storage = new StorageClient();

// these should update
$listInfoTypesRequest = new ListInfoTypesRequest();
$infoTypes = $dlp->listInfoTypes($listInfoTypesRequest);
$listSecretsRequest = (new ListSecretsRequest())
    ->setParent('this/is/a/parent');
$secrets = $secretmanager->listSecrets($listSecretsRequest);

// these shouldn't update
$operations = $longrunning->listOperations();
$serviceAccount = $storage->getServiceAccount();
