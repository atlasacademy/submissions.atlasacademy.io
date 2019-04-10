#!/bin/bash

source /app/.cached_env

su -p -c "php /app/artisan $@" application
