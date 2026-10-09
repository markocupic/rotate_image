#!/bin/bash
php vendor/bin/phpunit --testdox > t.log 2>&1
echo "::error::$(tail -c 6000 t.log | tr '\n' '|')"
