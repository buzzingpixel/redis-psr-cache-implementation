#!/usr/bin/env bash

function phpstan() {
    XDEBUG_MODE=off php74 -d memory_limit=4G ./vendor/bin/phpstan analyse src;

    return 0;
}
