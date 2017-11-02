<?php
/**
 * Dumps the contents of the environment variable GOOGLE_CREDENTIALS_BASE64 to
 * a file.
 *
 * To setup Travis to run on your fork, read TRAVIS.md.
 */
$cred = getenv('GOOGLE_CREDENTIALS_BASE64');
$fpath = getenv('GOOGLE_APPLICATION_CREDENTIALS');
if ($cred !== false && $fpath !== false) {
    file_put_contents($fpath, base64_decode($cred));
}

$iap_cred = getenv('IAP_CREDENTIALS_BASE64');
$iap_fpath = getenv('IAP_SERVICE_ACCOUNT');
if ($iap_cred !== false && $iap_fpath !== false) {
    file_put_contents($iap_fpath, base64_decode($iap_cred));
}
