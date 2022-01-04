#!/usr/bin/env bash

function phpunit() {
    XDEBUG_MODE=coverage php74 ./vendor/bin/phpunit;

    return 0;
}
