#!/usr/bin/env bash

function phpcs() {
    XDEBUG_MODE=off php74 -d memory_limit=4G ./vendor/bin/phpcs

    return 0;
}

function phpcbf() {
    XDEBUG_MODE=off php74 -d memory_limit=4G ./vendor/bin/phpcbf;

    return 0;
}
