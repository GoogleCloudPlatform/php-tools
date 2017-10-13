# Utilities for Google Cloud Platform

## Install

Add `google/cloud-tools` to the `require-dev` section of your
`composer.json`.

You can also run the following command:

```
$ composer require google/cloud-tools --dev
```

## Utilities

### flex_exec

The cli script `src/Utils/Flex/flex_exec` is a tool for running a
command with using the same Docker image as the application running on
App Engine Flex.

It spins up a Docker image of a Deployed App Engine Flexible App, and
runs a command in that image. For example, if you are running Laravel
application, you can invoke a command like `php artisan migrate` in
the image.

If the Flex application is requesting the cloudsql access
(`beta_settings`, `cloud_sql_instances`), this tool also provides the
connection to the same Cloud SQL instances.

The command runs on virtual machines provided by [Google Cloud
Container Builder](https://cloud.google.com/container-builder/docs/),
and has access to the credentials of the Cloud Container Builder
service account.

### Prerequisites

To use flex_exec, you will need:

* An app deployed to Google App Engine Flex
* The gcloud SDK installed and configured. See https://cloud.google.com/sdk/
* The `google/cloud-tools` composer package
  
You may also need to grant the Cloud Container Builder service account
any permissions needed by your command. For accessing Cloud SQL, you
need to add `Cloud SQL Client` permission to the service account.

You can find the service account configuration in the IAM tab in the
Cloud Console under the name `[your-project-number]@cloudbuild.gserviceaccount.com`.

### Resource usage and billing

The tool uses virtual machine resources provided by Google Cloud
Container Builder. Although a certain number of usage minutes per day
is covered under a free tier, additional compute usage beyond that
time is billed to your Google Cloud account. For more details, see:
https://cloud.google.com/container-builder/pricing

If your command makes API calls or utilizes other cloud resources, you
may also be billed for that usage. However, `flex_exec` does not use
actual App Engine instances, and you will not be billed for additional
App Engine instance usage.

### Example

```
src/Utils/Flex/flex_exec run -- php artisan migrate
```

## Testing Utilities

There are various test utilities in the `Google\Cloud\TestUtils` namespace.

## Test examples

The example test cases are available in
[`test/fixtures/appengine-standard`](https://github.com/GoogleCloudPlatform/php-testutils/tree/master/test/fixtures/appengine-standard) directory.

### Environment variables

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
