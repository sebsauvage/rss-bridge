FROM php:7-apache

ENV APACHE_DOCUMENT_ROOT=/app

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && apt-get --yes update && apt-get --yes install libxml2-dev zlib1g-dev libmemcached-dev \
    && docker-php-ext-install -j$(nproc) simplexml \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e 's/(MinProtocol\s*=\s*)TLSv1\.2/\1None/' /etc/ssl/openssl.cnf \
    && sed -ri -e 's/(CipherString\s*=\s*DEFAULT)@SECLEVEL=2/\1/' /etc/ssl/openssl.cnf

RUN curl https://codeload.github.com/php-memcached-dev/php-memcached/tar.gz/v3.1.5 --output /tmp/php-memcached.tar.gz \
    && mkdir -p /usr/src/php/ext \
    && tar xzvf /tmp/php-memcached.tar.gz -C /usr/src/php/ext \
    && mv /usr/src/php/ext/php-memcached-3.1.5 /usr/src/php/ext/memcached \
    && cd /usr/src/php/ext/memcached \
    && docker-php-ext-configure /usr/src/php/ext/memcached --disable-memcached-sasl \
    && docker-php-ext-install /usr/src/php/ext/memcached \
    && rm -rf /usr/src/php/ext/memcached

RUN pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

COPY --chown=www-data:www-data ./ /app/
