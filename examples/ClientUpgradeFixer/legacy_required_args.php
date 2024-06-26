<?php

namespace Google\Cloud\Samples\Dlp;

use Google\Cloud\Dlp\V2\DlpServiceClient;

// Instantiate a client.
$dlp = new DlpServiceClient();

// required args string
$dlp->createDlpJob('this/is/a/parent');

// required args string (double quotes)
$dlp->createDlpJob("this/is/a/$variable");

// required args inline array
$dlp->createDlpJob(['jobId' => 'abc', 'locationId' => 'def']);

// required args variable
$dlp->createDlpJob($foo);
