#!/bin/bash

export -p > /app/.cached_env

if [ "$RUN_CRON" = true ] ; then
    cat /app/build/crontab | crontab -
fi

if [ "$RUN_COMPOSER_INSTALL" = true ] ; then
    su application -c "cd /app && composer install"
fi

if [ "$RUN_MIGRATION" = true ] ; then
    su application -c "php /app/artisan migrate --force"
fi
