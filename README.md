# Wifidog Auth (Laravel 5.4)

This project provides a auth server for wifidog. For API details, please see the [WiFiDog Protocol V1](http://dev.wifidog.org/wiki/doc/developer/WiFiDogProtocol_V1).

## Pages

- login/
- portal/ 
- messages/ OR gw\_message.php

## Apis

- ping/
- auth/

## Getting Started

```
composer install
```

## Tech

- PHP Framework: [Laravel 5.4](https://laravel.com/docs/5.4/)
- Coding standard: following [PSR2](http://www.php-fig.org/psr/psr-2/). run `./lint.sh` to check.
- Unit Test: using PHPUnit. run `./phpunit.sh`.
- CI: using [circleci.com](https://circleci.com/)
