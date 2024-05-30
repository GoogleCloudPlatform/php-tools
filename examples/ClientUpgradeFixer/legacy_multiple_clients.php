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

// Instantiate a client.
$dlp = new DlpServiceClient();
$longrunning = new OperationsClient();
$secretmanager = new SecretManagerServiceClient();
$storage = new StorageClient();

// these should update
$infoTypes = $dlp->listInfoTypes();
$secrets = $secretmanager->listSecrets('this/is/a/parent');

// these shouldn't update
$operations = $longrunning->listOperations();
$serviceAccount = $storage->getServiceAccount();
