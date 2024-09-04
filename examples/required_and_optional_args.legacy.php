<?php

namespace Google\Cloud\Samples\Dlp;

use Google\Cloud\Dlp\V2\DlpServiceClient;
use Google\Cloud\Dlp\V2\InspectConfig;
use Google\Cloud\Dlp\V2\InspectJobConfig;
use Google\Cloud\Dlp\V2\Likelihood;
use Google\Cloud\Dlp\V2\StorageConfig;

// Instantiate a client.
$dlp = new DlpServiceClient();

// required args variable and optional args variable
$dlp->createDlpJob($parent, $optionalArgs);

// required args variable and optional args array
$dlp->createDlpJob($parent, ['jobId' => 'abc', 'locationId' => 'def']);

// required args string and optional variable
$dlp->createDlpJob('path/to/parent', ['jobId' => 'abc', 'locationId' => 'def']);

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
    ])
]);
