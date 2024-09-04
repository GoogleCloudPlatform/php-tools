<?php

namespace Google\Cloud\Samples\Dlp;


use Google\Cloud\Dlp\V2\DlpServiceClient;

// Instantiate a client.
$dlp = new DlpServiceClient();

// Call a client method which is NOT an RPC
$jobName = $dlp->dlpJobName('my-project', 'my-job');

// Call the "close" method
$job = $dlp->close();

// Call an RPC method
$job = $dlp->getDlpJob($jobName);

// Call a non-existant method!
$job = $dlp->getJob($jobName);
