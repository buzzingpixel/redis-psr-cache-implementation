#!/usr/bin/env bash

COMPOSER_PATH=$(which composer);

function composer() {
    XDEBUG_MODE=off php74 "${COMPOSER_PATH}" "${allArgsExceptFirst}"

    return 0;
}
