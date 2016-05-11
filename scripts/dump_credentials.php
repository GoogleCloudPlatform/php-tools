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
