# Soccer Manager API

## Setup

./vendor/bin/sail up -d

./vendor/bin/sail artisan migrate

./vendor/bin/sail artisan db:seed


## Run tests

./vendor/bin/sail test

## Swagger

./vendor/bin/sail artisan l5-swagger:generate

## Fix code style using Pint

./vendor/bin/sail pint
