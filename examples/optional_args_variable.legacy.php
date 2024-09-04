<?php

namespace Google\Cloud\Samples\Dlp;

use Google\Cloud\Dlp\V2\DlpServiceClient;
use Google\Cloud\Dlp\V2\InspectConfig;
use Google\Cloud\Dlp\V2\InspectJobConfig;
use Google\Cloud\Dlp\V2\Likelihood;
use Google\Cloud\Dlp\V2\StorageConfig;

// Instantiate a client.
$dlp = new DlpServiceClient();

// optional args array (variable)
$infoTypes = $dlp->listInfoTypes($parent);

// optional args array (inline array)
$options = ['jobId' => 'abc', 'locationId' => 'def'];
$job = $dlp->createDlpJob($parent, $options);

// optional args array (inline with nested arrays)
$options2 = [
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
];
$job = $dlp->createDlpJob($parent, $options2);
