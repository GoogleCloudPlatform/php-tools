<?php

require_once 'vendor/autoload.php';

use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\Dlp\V2\DlpServiceClient;

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
?>

<!doctype html>
<html>
    <head><meta charset="utf-8"></head>
    <body>
        <header><h1>Google Cloud Sample App</h1></header>
        <div class="main-content">
            <h2 class="collapsible">List Secrets</h2>
            <div id="listSecrets" class="collapsible-content">
                <?php foreach ($secretManagerClient->listSecrets($parent) as $secret): ?>
                    <pre><?= print_message($secret) ?></pre>
                <?php endforeach ?>
            </div>

            <h2 class="collapsible">List DLP Jobs</h2>
            <div id="listDlpJobs" class="collapsible-content">
                <?php foreach ($dlpClient->listDlpJobs($parent) as $job): ?>
                    <pre><?= print_message($job) ?></pre>
                <?php endforeach ?>
            </div>
        </div>
    </body>
</html>
