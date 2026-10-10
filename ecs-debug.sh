#!/bin/bash
php .ecs/vendor/bin/ecs check --config .ecs/config/default.php --no-progress-bar > ecs.log 2>&1
echo "::error::$(tail -c 9000 ecs.log | tr '\n' '|')"
