FROM scratch AS composer_install_requirements

COPY composer.json /

FROM scratch AS test_source

COPY src/ /src/
COPY tests/ /tests/
COPY phpunit.xml.* phpstan*.neon .php_cs.* /

FROM alpine:3.23

# alpine php package does not include default extensions, be explicit
RUN set -eu; \
    apk add --no-cache \
        php84 \
        php84-iconv \
        php84-json \
        php84-mbstring \
        php84-openssl \
        php84-phar \
        php84-xml \
        php84-dom \
        php84-pdo \
        php84-curl \
        php84-tokenizer \
        php84-simplexml \
        php84-xmlwriter \
        php84-xdebug

# Configure Xdebug
RUN echo "zend_extension=xdebug.so" > /etc/php84/conf.d/50_xdebug.ini; \
    echo "xdebug.mode=debug" >> /etc/php84/conf.d/50_xdebug.ini; \
    echo "xdebug.start_with_request=yes" >> /etc/php84/conf.d/50_xdebug.ini; \
    echo "xdebug.client_host=host.docker.internal" >> /etc/php84/conf.d/50_xdebug.ini; \
    echo "xdebug.client_port=9000" >> /etc/php84/conf.d/50_xdebug.ini; \
    echo "xdebug.log=/tmp/xdebug.log" >> /etc/php84/conf.d/50_xdebug.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# install Symfony Flex globally to speed up download of Composer packages (parallelized prefetching)
RUN set -eux; \
    composer global config --no-plugins allow-plugins.symfony/flex true; \
	composer global require "symfony/flex:^1.0" --prefer-dist --no-progress --classmap-authoritative;

WORKDIR /opt/test

COPY --from=composer_install_requirements / .

RUN php /usr/bin/composer install

COPY --from=test_source / .

RUN echo "memory_limit=1G" > /etc/php84/conf.d/99-custom.ini
ENTRYPOINT ["composer"]
