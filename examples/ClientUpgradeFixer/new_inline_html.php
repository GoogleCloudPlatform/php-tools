<?php

require_once 'vendor/autoload.php';

use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
use Google\Cloud\Dlp\V2\ListDlpJobsRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\ListSecretsRequest;

// Instantiate clients for calling the Google Cloud APIs.
$secretManagerClient = new SecretManagerServiceClient();
$dlpClient = new DlpServiceClient();

// define the parent
$parent = 'path/to/parent';

/**
 * Helper function to pretty-print a Protobuf message.
 */
function print_message(Message $message)
{
    return json_encode(
        json_decode($message->serializeToJsonString(), true),
        JSON_PRETTY_PRINT
    );
}

$listSecretsRequest = (new ListSecretsRequest())
    ->setParent($parent);
$listDlpJobsRequest = (new ListDlpJobsRequest())
    ->setParent($parent);
?>

<!doctype html>
<html>
    <head><meta charset="utf-8"></head>
    <body>
        <header><h1>Google Cloud Sample App</h1></header>
        <div class="main-content">
            <h2 class="collapsible">List Secrets</h2>
            <div id="listSecrets" class="collapsible-content">
                <?php foreach ($secretManagerClient->listSecrets($listSecretsRequest) as $secret): ?>
                    <pre><?= print_message($secret) ?></pre>
                <?php endforeach ?>
            </div>

            <h2 class="collapsible">List DLP Jobs</h2>
            <div id="listDlpJobs" class="collapsible-content">
                <?php foreach ($dlpClient->listDlpJobs($listDlpJobsRequest) as $job): ?>
                    <pre><?= print_message($job) ?></pre>
                <?php endforeach ?>
            </div>
        </div>
    </body>
</html>
