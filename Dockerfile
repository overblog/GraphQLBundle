FROM scratch AS composer_install_requirements

COPY composer.json /

FROM scratch AS test_source

COPY benchmarks/ benchmarks/
COPY lib/ /lib/
COPY src/ /src/
COPY tests/ /tests/
COPY phpunit.xml.* phpstan.neon.* .php_cs.* phpbench.json /

FROM alpine:3.9

# alpine php package does not include default extensions, be explicit
RUN set -eu; \
    apk add --no-cache \
        php7 \
        php7-iconv \
        php7-json \
        php7-mbstring \
        php7-openssl \
        php7-phar \
        php7-tokenizer \
        php7-xml \
        php7-xmlwriter \
        php7-dom \
        php7-pdo \
    ;

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /opt/test

COPY --from=composer_install_requirements / .

RUN composer install

COPY --from=test_source / .

RUN echo "memory_limit=1G" > /etc/php7/conf.d/99-custom.ini

ENTRYPOINT ["composer"]
