FROM webdevops/php-nginx:7.3
MAINTAINER sadisticsolutione@gmail.com

ENV WEB_DOCUMENT_ROOT=/app/public \
    RUN_COMPOSER_INSTALL="false" \
    RUN_CRON="true" \
    RUN_MIGRATION="true" \
    RUN_QUEUE="true" \
    APP_NAME=submissions.atlasacademy.io \
    APP_URL=http://submissions.test.atlasacademy.io \
    DB_CONNECTION=mysql \
    DB_HOST=db \
    DB_PORT=3306 \
    DB_DATABASE=submissions \
    DB_USERNAME=root \
    DB_PASSWORD=password \
    LOG_CHANNEL=syslog \
    QUEUE_CONNECTION=database \
    QUEUE_TABLE=jobs \
    S3_ENDPOINT="" \
    S3_BUCKET="" \
    S3_KEY="" \
    S3_SECRET="" \
    S3_HOST="" \
    ADMIN_KEY=00000000000000000000000000000000 \
    ASSETS_HOST=http://submissions.test.atlasacademy.io/assets \
    ASSETS_DIRECTORY=/app/public/assets \
    PARSER_HOST=http://parser.test.atlasacademy.io \
    SCREENSHOTS_HOST=https://assets.atlasacademy.io/screenshots \
    SCREENSHOTS_DIRECTORY=/app/storage/screenshots

WORKDIR /app

COPY ./build/setup.sh /opt/docker/provision/entrypoint.d/30-setup.sh
RUN chmod +x /opt/docker/provision/entrypoint.d/30-setup.sh
COPY build/queue.conf /opt/docker/etc/supervisor.d/queue.conf

# Disable Cron Syslog output
RUN sed -i "s|not facility(auth, authpriv);|not facility(auth, authpriv, cron);|g" /opt/docker/etc/syslog-ng/syslog-ng.conf

# Enable real ip passthrough from nginx proxy
COPY ./build/nginx.conf /opt/docker/etc/nginx/vhost.common.d/00-real-ip.conf

COPY --chown=application . /app
RUN su application -c "composer install --no-dev"
