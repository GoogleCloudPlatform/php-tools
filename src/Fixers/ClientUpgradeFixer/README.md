# Fixer for the new Google Cloud PHP Client Surface

This repo provides a Fixer, to be used with [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer),
which will automatically** upgrade your code to the
[new Google Cloud PHP client surface](https://github.com/googleapis/google-cloud-php/discussions/5206).

**IMPORTANT** This is an alpha tool and NOT recommended to use without thorough testing!
It is also not guaranteed by Google in any way.

## Installation


Install the `google/cloud-tools` package, which includes the `ClientUpgradeFixer`,
along with the `friendsofphp/php-cs-fixer` package:

```sh
composer require --dev "google/cloud-tools" "friendsofphp/php-cs-fixer:^3.21"
```

## Running the fixer

Create the file `.php-cs-fixer.google.php` in your project, which will be
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
        // See "Configuring Client Variables" for more configuration options
        'GoogleCloud/upgrade_clients' => true,
    ])
;
```

Next run this fixer with the following command:

```sh
# use the examples provided in this repo
export DIR=vendor/google/cloud-tools/examples

# run the CS fixer for that directory using the config above
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.google.php --dry-run --diff $DIR -vv
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

## Client Variable detection

Client variables are detected in the same file as long as one of the following
takes place:

 - The variable is assigned to a client instance using the `new` keyword
   ```php
   $client = new DlpServiceClient()
   ```
 - The variable class is defined using "@var" in a comment
   ```php
   /** @var DlpServiceClient */
   $dlp = $container->getDlpClient();
   ```
 - The variable is passed into a function where the parameter is typehinted with
   the client class
   ```php
   public function callDlp(DlpServiceClient $dlp)
   ```
 - The variable is defined with a typehint as a client property
   ```php
   private DlpServiceClient $dlp;
   private static DlpServiceClient $dlpStatic;
   ```

For all other cases, a map of variable name to class name can be configured using
the `clientVars` configuration in `.php-cs-fixer.google.php` (see below).

## Configuring Client Variables

There are instances where the fixer cannot detect where a client variable is
defined. For instance, variables defined in dependency injection containers.
For these, you can add to the `clientVars` configuration a amap of your variable
names and their client classes:

```php
$clientVars = [
    // a variable that was not detected
    '$myclient' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',

    // a variable that is called inside a class
    '$this->dlp' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',
    'self::$dlp' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',

    // a variable in a class property
    '$classWrapper->dlp' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',
    '$classWrapper::$dlp' => 'Google\\Cloud\\Dlp\\V2\\DlpServiceClient',

    // match all class instance variables (regardless of where they're being called)
    'secretManagerClient' => 'Google\\Cloud\\SecretManager\\V1\\SecretManagerServiceClient',
];

return (new PhpCsFixer\Config())
    ->registerCustomFixers([
        new Google\Cloud\Fixers\ClientUpgradeFixer\ClientUpgradeFixer(),
    ])
    ->setRules([
        'GoogleCloud/upgrade_clients' => [
            // array of variable names to their legacy class names
            'clientVars' => $clientVars
        ],
    ])
;
```

This will ensure that these variables are updated wherever they are:

```diff
6c6,8
< use Google\Cloud\Dlp\V2\DlpServiceClient;
---
> use Google\Cloud\Dlp\V2\Client\DlpServiceClient;
> use Google\Cloud\Dlp\V2\ListInfoTypesRequest;
> use Google\Cloud\SecretManager\V1\ListSecretsRequest;
12c14,15
< $infoTypes = $myclient->listInfoTypes();
---
> $listInfoTypesRequest = new ListInfoTypesRequest();
> $infoTypes = $myclient->listInfoTypes($listInfoTypesRequest);
14c17,19
< $secrets = $secretmanager->listSecrets('this/is/a/parent');
---
> $listSecretsRequest = (new ListSecretsRequest())
>     ->setParent('this/is/a/parent');
> $secrets = $secretmanager->listSecrets($listSecretsRequest);
27,28c32,35
<         $this->dlp->listInfoTypes();
<         self::$dlp->listInfoTypes();
---
>         $listInfoTypesRequest2 = new ListInfoTypesRequest();
>         $this->dlp->listInfoTypes($listInfoTypesRequest2);
>         $listInfoTypesRequest3 = new ListInfoTypesRequest();
>         self::$dlp->listInfoTypes($listInfoTypesRequest3);
41,44c48,55
<         $this->secretManagerClient->listSecrets();
<         $this::$secretManagerClient->listSecrets();
<         $this->parent->secretManagerClient->listSecrets();
<         $this->parent::$secretManagerClient->listSecrets();
---
>         $listSecretsRequest2 = new ListSecretsRequest();
>         $this->secretManagerClient->listSecrets($listSecretsRequest2);
>         $listSecretsRequest3 = new ListSecretsRequest();
>         $this::$secretManagerClient->listSecrets($listSecretsRequest3);
>         $listSecretsRequest4 = new ListSecretsRequest();
>         $this->parent->secretManagerClient->listSecrets($listSecretsRequest4);
>         $listSecretsRequest5 = new ListSecretsRequest();
>         $this->parent::$secretManagerClient->listSecrets($listSecretsRequest5);
```
