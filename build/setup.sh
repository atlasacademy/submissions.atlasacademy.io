#!/bin/bash

export -p > /app/.cached_env
cat /app/build/crontab | crontab -
su application -c "php /app/artisan migrate --force"
