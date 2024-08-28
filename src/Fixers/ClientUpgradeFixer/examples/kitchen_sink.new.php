<?php

namespace Google\Cloud\Samples\Dlp;

use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
use Google\Cloud\Dlp\V2\CreateDlpJobRequest;
use Google\Cloud\Dlp\V2\InspectConfig;
use Google\Cloud\Dlp\V2\InspectJobConfig;
use Google\Cloud\Dlp\V2\Likelihood;
use Google\Cloud\Dlp\V2\ListInfoTypesRequest;
use Google\Cloud\Dlp\V2\StorageConfig;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\CreateSecretRequest;
use Google\Cloud\SecretManager\V1\Replication;
use Google\Cloud\SecretManager\V1\Replication\Automatic;
use Google\Cloud\SecretManager\V1\Secret;
use Google\Cloud\Unordered\Namespace;

// Instantiate a client.
$dlp = new DlpServiceClient();

// no args
$listInfoTypesRequest = new ListInfoTypesRequest();
$infoTypes = $dlp->listInfoTypes($listInfoTypesRequest);

// optional args array (variable form)
$listInfoTypesRequest2 = new ListInfoTypesRequest();
$dlp->listInfoTypes($listInfoTypesRequest2);

// required args variable
$createDlpJobRequest = (new CreateDlpJobRequest())
    ->setParent($foo);
$dlp->createDlpJob($createDlpJobRequest);

// required args string
$createDlpJobRequest2 = (new CreateDlpJobRequest())
    ->setParent('this/is/a/parent');
$dlp->createDlpJob($createDlpJobRequest2);

// required args array
$createDlpJobRequest3 = (new CreateDlpJobRequest())
    ->setParent(['jobId' => 'abc', 'locationId' => 'def']);
$dlp->createDlpJob($createDlpJobRequest3);

// required args variable and optional args array
$createDlpJobRequest4 = (new CreateDlpJobRequest())
    ->setParent($parent)
    ->setJobId('abc')
    ->setLocationId('def');
$dlp->createDlpJob($createDlpJobRequest4);

// required args variable and optional args variable
$createDlpJobRequest5 = (new CreateDlpJobRequest())
    ->setParent($parent);
$dlp->createDlpJob($createDlpJobRequest5);

// required args variable and optional args array with nested array
$createDlpJobRequest6 = (new CreateDlpJobRequest())
    ->setParent($parent)
    ->setInspectJob(new InspectJobConfig([
        'inspect_config' => (new InspectConfig())
            ->setMinLikelihood(likelihood::LIKELIHOOD_UNSPECIFIED)
            ->setLimits($limits)
            ->setInfoTypes($infoTypes)
            ->setIncludeQuote(true),
        'storage_config' => (new StorageConfig())
            ->setCloudStorageOptions(($cloudStorageOptions))
            ->setTimespanConfig($timespanConfig),
    ]))
    ->setTrailingComma(true);
$job = $dlp->createDlpJob($createDlpJobRequest6);

$projectId = 'my-project';
$secretId = 'my-secret';

// Create the Secret Manager client.
$client = new SecretManagerServiceClient();

// Build the parent name from the project.
$parent = $client->projectName($projectId);

// Create the parent secret.
$createSecretRequest = (new CreateSecretRequest())
    ->setParent($parent)
    ->setSecretId($secretId)
    ->setSecret(new Secret([
        'replication' => new Replication([
            'automatic' => new Automatic(),
        ]),
    ]));
$secret = $client->createSecret($createSecretRequest);
