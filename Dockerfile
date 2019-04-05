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
    ;

RUN set -eu; \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
    EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"; \
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"; \
    \
    if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then \
        >&2 echo 'ERROR: Invalid installer signature'; \
        rm composer-setup.php; \
        exit 1; \
    fi; \
    \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer; \
    rm composer-setup.php

WORKDIR /opt/test

COPY --from=composer_install_requirements / .

RUN composer install

COPY --from=test_source / .

RUN echo "memory_limit=1G" > /etc/php7/conf.d/99-custom.ini

ENTRYPOINT ["composer"]