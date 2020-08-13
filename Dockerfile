FROM scratch AS composer_install_requirements

COPY composer.json /

FROM scratch AS test_source

COPY benchmarks/ benchmarks/
COPY src/ /src/
COPY tests/ /tests/
COPY phpunit.xml.* phpstan.neon.* .php_cs.* phpbench.json /

FROM alpine:3.9

ADD https://dl.bintray.com/php-alpine/key/php-alpine.rsa.pub /etc/apk/keys/php-alpine.rsa.pub

RUN apk --update add ca-certificates && \
    echo "https://dl.bintray.com/php-alpine/v3.9/php-7.4" >> /etc/apk/repositories

# alpine php package does not include default extensions, be explicit
RUN set -eu; \
    apk add --no-cache \
        php \
        php-iconv \
        php-json \
        php-mbstring \
        php-openssl \
        php-phar \
        php-xml \
        php-dom \
        php-pdo \
    ; ln -s /usr/bin/php7 /usr/bin/php

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /opt/test

COPY --from=composer_install_requirements / .

RUN php7 /usr/bin/composer install

COPY --from=test_source / .

RUN echo "memory_limit=1G" > /etc/php7/conf.d/99-custom.ini

ENTRYPOINT ["composer"]
