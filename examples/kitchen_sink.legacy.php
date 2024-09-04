<?php

namespace Google\Cloud\Samples\Dlp;

use Google\Cloud\Dlp\V2\DlpServiceClient;
use Google\Cloud\Dlp\V2\InspectConfig;
use Google\Cloud\Dlp\V2\InspectJobConfig;
use Google\Cloud\Dlp\V2\Likelihood;
use Google\Cloud\Dlp\V2\StorageConfig;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\SecretManager\V1\Replication;
use Google\Cloud\SecretManager\V1\Replication\Automatic;
use Google\Cloud\SecretManager\V1\Secret;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\Unordered\Namespace;

// Instantiate a client.
$dlp = new DlpServiceClient();

// no args
$infoTypes = $dlp->listInfoTypes();

// optional args array (variable form)
$dlp->listInfoTypes($foo);

// required args variable
$dlp->createDlpJob($foo);

// required args string
$dlp->createDlpJob('this/is/a/parent');

// required args array
$dlp->createDlpJob(['jobId' => 'abc', 'locationId' => 'def']);

// required args variable and optional args array
$dlp->createDlpJob($parent, ['jobId' => 'abc', 'locationId' => 'def']);

// required args variable and optional args variable
$dlp->createDlpJob($parent, $optionalArgs);

// required args variable and optional args array with nested array
$job = $dlp->createDlpJob($parent, [
    'inspectJob' => new InspectJobConfig([
        'inspect_config' => (new InspectConfig())
            ->setMinLikelihood(likelihood::LIKELIHOOD_UNSPECIFIED)
            ->setLimits($limits)
            ->setInfoTypes($infoTypes)
            ->setIncludeQuote(true),
        'storage_config' => (new StorageConfig())
            ->setCloudStorageOptions(($cloudStorageOptions))
            ->setTimespanConfig($timespanConfig),
    ]),
    'trailingComma' => true,
]);

$projectId = 'my-project';
$secretId = 'my-secret';

// Create the Secret Manager client.
$client = new SecretManagerServiceClient();

// Build the parent name from the project.
$parent = $client->projectName($projectId);

// Create the parent secret.
$secret = $client->createSecret($parent, $secretId,
    new Secret([
        'replication' => new Replication([
            'automatic' => new Automatic(),
        ]),
    ])
);
