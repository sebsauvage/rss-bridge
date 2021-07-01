ARG FROM_ARCH=amd64

# Multi-stage build, see https://docs.docker.com/develop/develop-images/multistage-build/
FROM alpine AS builder

# Download QEMU
ADD https://github.com/balena-io/qemu/releases/download/v5.2.0%2Bbalena4/qemu-5.2.0.balena4-arm.tar.gz .
RUN tar zxvf qemu-5.2.0.balena4-arm.tar.gz --strip-components 1
ADD https://github.com/balena-io/qemu/releases/download/v5.2.0%2Bbalena4/qemu-5.2.0.balena4-aarch64.tar.gz .
RUN tar zxvf qemu-5.2.0.balena4-aarch64.tar.gz --strip-components 1

FROM $FROM_ARCH/php:7-apache

LABEL description="RSS-Bridge is a PHP project capable of generating RSS and Atom feeds for websites that don't have one."
LABEL repository="https://github.com/RSS-Bridge/rss-bridge"
LABEL website="https://github.com/RSS-Bridge/rss-bridge"

# Add QEMU
COPY --from=builder qemu-arm-static /usr/bin
COPY --from=builder qemu-aarch64-static /usr/bin

ENV APACHE_DOCUMENT_ROOT=/app

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
	&& apt-get --yes update \
	&& apt-get --yes --no-install-recommends install \
		zlib1g-dev \
		libmemcached-dev \
	&& rm -rf /var/lib/apt/lists/* \
	&& pecl install memcached \
	&& docker-php-ext-enable memcached \
	&& sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
	&& sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
	&& sed -ri -e 's/(MinProtocol\s*=\s*)TLSv1\.2/\1None/' /etc/ssl/openssl.cnf \
	&& sed -ri -e 's/(CipherString\s*=\s*DEFAULT)@SECLEVEL=2/\1/' /etc/ssl/openssl.cnf

COPY --chown=www-data:www-data ./ /app/

CMD ["/app/docker-entrypoint.sh"]
