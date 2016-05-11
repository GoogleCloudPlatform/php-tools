# Testing utilities for Google Cloud Platform

## Install

Add `google/cloud-tools` to the `require-dev` section of your
`composer.json`.

You can also run the following command:

```
$ composer require google/cloud-tools --dev
```

## Examples

The example test cases are available in
[`test/fixtures/appengine-standard`](https://github.com/GoogleCloudPlatform/php-testutils/tree/master/test/fixtures/appengine-standard) directory.

## Environment variables

There are multiple environment variables to control the behavior of
our test traits.

- `RUN_DEVSERVER_TESTS`:
  Set to `true` if you want to run tests with DevAppserverTestTrait
- `PHP_CGI_PATH`:
  Path to `php-cgi` for running dev_appserver
- `LOCAL_TEST_TARGETS`:
  You can specify multiple yaml files if your test need multiple services.
- `RUN_DEPLOYMENT_TESTS`:
  Set to `true` if you want to run tests with AppEngineDeploymentTrait
- `GOOGLE_PROJECT_ID`:
  The project id for deploying the application
- `GOOGLE_VERSION_ID`:
  The version id for deploying the application
