# Soccer Manager API

## Setup
```bash
./vendor/bin/sail up -d

./vendor/bin/sail artisan migrate

./vendor/bin/sail artisan db:seed
```

## Run tests

```bash
./vendor/bin/sail test
```

## Swagger
```bash
./vendor/bin/sail artisan l5-swagger:generate
```
## Fix code style using Pint
```bash
./vendor/bin/sail pint
```
