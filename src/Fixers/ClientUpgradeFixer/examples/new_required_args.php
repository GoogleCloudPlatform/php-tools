<?php

namespace Google\Cloud\Samples\Dlp;

use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
use Google\Cloud\Dlp\V2\CreateDlpJobRequest;

// Instantiate a client.
$dlp = new DlpServiceClient();

// required args string
$createDlpJobRequest = (new CreateDlpJobRequest())
    ->setParent('this/is/a/parent');
$dlp->createDlpJob($createDlpJobRequest);

// required args string (double quotes)
$createDlpJobRequest2 = (new CreateDlpJobRequest())
    ->setParent("this/is/a/$variable");
$dlp->createDlpJob($createDlpJobRequest2);

// required args inline array
$createDlpJobRequest3 = (new CreateDlpJobRequest())
    ->setParent(['jobId' => 'abc', 'locationId' => 'def']);
$dlp->createDlpJob($createDlpJobRequest3);

// required args variable
$createDlpJobRequest4 = (new CreateDlpJobRequest())
    ->setParent($foo);
$dlp->createDlpJob($createDlpJobRequest4);
