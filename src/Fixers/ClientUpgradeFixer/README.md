# Fixer for the new Google Cloud PHP Client Surface

This repo provides a Fixer, to be used with [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer),
which will automatically** upgrade your code to the
[new Google Cloud PHP client surface](https://github.com/googleapis/google-cloud-php/discussions/5206).

**IMPORTANT** This is an alpha tool and NOT recommended to use without thorough testing!
It is also not guaranteed by Google in any way.

## Installation


Install the `google/cloud-tools` package, which includes the fixer:

```sh
composer require --dev "google/cloud-tools"
```

Install `friendsofphp/php-cs-fixer`:

```sh
composer require --dev "friendsofphp/php-cs-fixer:^3.21"
```

## Running the fixer

First, create a `.php-cs-fixer.google.php` in your project which will be
configured to use the custom fixer:

```php
<?php
// .php-cs-fixer.google.php

// The fixer MUST autoload google/cloud classes.
require __DIR__ . '/vendor/autoload.php';

// configure the fixer to run with the new surface fixer
return (new PhpCsFixer\Config())
    ->registerCustomFixers([
        new Google\Cloud\Fixers\ClientUpgradeFixer\ClientUpgradeFixer(),
    ])
    ->setRules([
        // See "Configuring Client Vars" below for more configuration options
        'GoogleCloud/upgrade_clients' => true,
    ])
;
```

Next run this fixer with the following command:

```sh
# use the examples provided in this repo
export DIR=vendor/google/cloud-tools/examples/ClientUpgradeFixer

# run the CS fixer for that directory using the config above
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.google.php --dry-run --diff $DIR
```

You should get an output similar to this

```diff
--- legacy_optional_args.php
+++ legacy_optional_args.php
@@ -2,10 +2,12 @@

 namespace Google\Cloud\Samples\Dlp;

-use Google\Cloud\Dlp\V2\DlpServiceClient;
+use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
+use Google\Cloud\Dlp\V2\CreateDlpJobRequest;
 use Google\Cloud\Dlp\V2\InspectConfig;
 use Google\Cloud\Dlp\V2\InspectJobConfig;
 use Google\Cloud\Dlp\V2\Likelihood;
+use Google\Cloud\Dlp\V2\ListInfoTypesRequest;
 use Google\Cloud\Dlp\V2\StorageConfig;

 // Instantiate a client.
@@ -12,14 +14,20 @@
 $dlp = new DlpServiceClient();

 // optional args array (variable)
-$infoTypes = $dlp->listInfoTypes($parent);
+$request = (new ListInfoTypesRequest());
+$infoTypes = $dlp->listInfoTypes($request);

 // optional args array (inline array)
-$job = $dlp->createDlpJob($parent, ['jobId' => 'abc', 'locationId' => 'def']);
+$request2 = (new CreateDlpJobRequest())
+    ->setParent($parent)
+    ->setJobId('abc')
+    ->setLocationId('def');
+$job = $dlp->createDlpJob($request2);

      ----------- end diff -----------
```

## Configuring Client Vars

There are instances where the fixer cannot detect where a client variable is
defined. For instance, variables defined in dependency injection containers.
For these, you can add to `clientVars` an array of variable names and their
classes:

```php
return (new PhpCsFixer\Config())
    ->registerCustomFixers([
        new Google\Cloud\Fixers\ClientUpgradeFixer\ClientUpgradeFixer(),
    ])
    ->setRules([
        'GoogleCloud/upgrade_clients' => [
            // array of variable names to their legacy class names
            'clientVars' => [
                // a variable that was not detected
                '$myclient' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',

                // a variable that is part of a class
                '$this->dlp' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',
                'self::$dlp' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',

                // match all variable by name (regardless of where they're being called)
                'secretManagerClient' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',
                '$secretManagerClient' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',
            ]
        ],
    ])
;
```
