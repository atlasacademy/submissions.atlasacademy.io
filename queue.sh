#!/bin/bash

set -e

if [ "$RUN_QUEUE" = true ] ; then
    php /app/artisan queue:work --queue=default --sleep=3 --timeout=1800 --tries=1
else
    tail -f /dev/null
fi
