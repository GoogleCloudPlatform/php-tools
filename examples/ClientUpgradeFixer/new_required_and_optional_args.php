<?php

namespace Google\Cloud\Samples\Dlp;

use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
use Google\Cloud\Dlp\V2\CreateDlpJobRequest;
use Google\Cloud\Dlp\V2\InspectConfig;
use Google\Cloud\Dlp\V2\InspectJobConfig;
use Google\Cloud\Dlp\V2\Likelihood;
use Google\Cloud\Dlp\V2\StorageConfig;

// Instantiate a client.
$dlp = new DlpServiceClient();

// required args variable and optional args variable
$createDlpJobRequest = (new CreateDlpJobRequest())
    ->setParent($parent);
$dlp->createDlpJob($createDlpJobRequest);

// required args variable and optional args array
$createDlpJobRequest2 = (new CreateDlpJobRequest())
    ->setParent($parent)
    ->setJobId('abc')
    ->setLocationId('def');
$dlp->createDlpJob($createDlpJobRequest2);

// required args string and optional variable
$createDlpJobRequest3 = (new CreateDlpJobRequest())
    ->setParent('path/to/parent')
    ->setJobId('abc')
    ->setLocationId('def');
$dlp->createDlpJob($createDlpJobRequest3);

// required args variable and optional args array with nested array
$createDlpJobRequest4 = (new CreateDlpJobRequest())
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
    ]));
$job = $dlp->createDlpJob($createDlpJobRequest4);
