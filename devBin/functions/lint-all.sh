#!/usr/bin/env bash

function lint-all() {
    set -e;

    phpunit;
    phpstan;
    phpcs;

    return 0;
}
