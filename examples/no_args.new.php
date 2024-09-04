<?php

namespace Google\Cloud\Samples\Dlp;

use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
use Google\Cloud\Dlp\V2\ListInfoTypesRequest;

// Instantiate a client.
$dlp = new DlpServiceClient();

// no args
$listInfoTypesRequest = new ListInfoTypesRequest();
$infoTypes = $dlp->listInfoTypes($listInfoTypesRequest);
