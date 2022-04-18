## extend and re-publish nextcloud container with Storj app pre-installed
FROM nextcloud:23.0.3-apache

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions ffi

COPY storj-ffi.ini /usr/local/etc/php/conf.d/storj-ffi.ini

COPY storj.config.php /usr/src/nextcloud/config/storj.config.php

RUN mkdir -p /usr/src/nextcloud/apps \
    && cd /usr/src/nextcloud/apps \
    && curl --output storj.tar.gz https://link.storjshare.io/raw/jvfizc37vgr5ohyxwreg7abnxxrq/nextcloud-app-assets/storj-v0.0.8.tar.gz \
    && tar -x -f storj.tar.gz \
    && rm storj.tar.gz
