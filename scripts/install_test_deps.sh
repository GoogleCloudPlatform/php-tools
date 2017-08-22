#!/bin/bash
# Copyright 2016 Google Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

set -ex

install_gcloud()
{
    # Install gcloud
    # You need to have ${HOME}/google-cloud-sdk/bin in your ${PATH}
    if [ ! -d ${HOME}/google-cloud-sdk ]; then
        wget \
            https://dl.google.com/dl/cloudsdk/release/google-cloud-sdk.tar.gz \
            --directory-prefix=${HOME}
        pushd "${HOME}"
        tar xzf google-cloud-sdk.tar.gz
        ./google-cloud-sdk/install.sh \
            --usage-reporting false \
            --path-update false \
            --command-completion false
        popd
    fi
}

configure_gcloud()
{
    if [ -n "${CLOUDSDK_ACTIVE_CONFIG_NAME}" ]; then
        gcloud config configurations create ${CLOUDSDK_ACTIVE_CONFIG_NAME} \
            || /bin/true
    fi
    # Configure gcloud
    gcloud config set project ${GOOGLE_PROJECT_ID}
    gcloud config set app/promote_by_default false
    gcloud config set disable_prompts true
    if [ -f ${GOOGLE_APPLICATION_CREDENTIALS} ]; then
        gcloud auth activate-service-account --key-file \
            "${GOOGLE_APPLICATION_CREDENTIALS}"
    fi
    gcloud -q components install app-engine-python
    gcloud -q components install app-engine-php
    gcloud -q components update
    if [ -n "${GCLOUD_VERBOSITY}" ]; then
        gcloud -q config set verbosity ${GCLOUD_VERBOSITY}
    fi
    gcloud info
}

install_php_cs_fixer()
{
    # Install PHP-cs-fixer
    if [ ! -f php-cs-fixer ]; then
        wget http://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -O php-cs-fixer
        chmod a+x php-cs-fixer
    fi
}

if [ "${RUN_DEPLOYMENT_TESTS}" = "true" ] \
    || [ "${RUN_DEVSERVER_TESTS}" = "true" ]; then
    install_gcloud
    configure_gcloud
fi

if [ "${RUN_CS_FIXER}" = "true" ]; then
    install_php_cs_fixer
fi
