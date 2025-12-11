#!/bin/bash

set -eu

mkdir -p var/config
mkdir -p config/local/

cp -r .github/ci/files/config/. config
cp -r .github/ci/files/templates/. templates
cp -r .github/ci/files/bin/console bin/console
cp -r .github/ci/files/src/. src
cp -r .github/ci/files/public/. public

cp .github/ci/files/.env ./
