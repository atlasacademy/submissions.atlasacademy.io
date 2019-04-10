FROM webdevops/php-nginx:7.3
MAINTAINER sadisticsolutione@gmail.com

ENV WEB_DOCUMENT_ROOT=/app/public \
    APP_NAME=submissions.atlas-academy.io \
    APP_URL=https://submissions.atlas-academy.io \
    DB_CONNECTION=mysql \
    DB_HOST=db \
    DB_PORT=3306 \
    DB_DATABASE=submissions \
    DB_USERNAME=root \
    DB_PASSWORD=password \
    QUEUE_CONNECTION=database \
    QUEUE_TABLE=jobs

WORKDIR /app

COPY ./build/setup.sh /opt/docker/provision/entrypoint.d/30-setup.sh
RUN chmod +x /opt/docker/provision/entrypoint.d/30-setup.sh
COPY build/queue.conf /opt/docker/etc/supervisor.d/queue.conf

# Disable Cron Syslog output
RUN sed -i "s|not facility(auth, authpriv);|not facility(auth, authpriv, cron);|g" /opt/docker/etc/syslog-ng/syslog-ng.conf

COPY --chown=application . /app
RUN su application -c "composer install --no-dev"
