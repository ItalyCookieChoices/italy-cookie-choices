ARG PHP_VERSION=8.2
ARG WP_VERSION=6
ARG HOST_UID=1000
ARG HOST_GID=1000

FROM wordpress:${WP_VERSION}-php${PHP_VERSION}

ARG HOST_UID
ARG HOST_GID

RUN set -eux; \
    groupmod -o -g "${HOST_GID}" www-data; \
    usermod -o -u "${HOST_UID}" -g "${HOST_GID}" www-data; \
    chown -R www-data:www-data /var/www

# Install pcov for code coverage
RUN set -eux; \
    pecl install pcov; \
    docker-php-ext-enable pcov

# If you want to use xdebug, uncomment the following lines
# Install xdebug for code coverage
#RUN set -eux; \
#    pecl install xdebug-3.1.4; \
#    docker-php-ext-enable xdebug

# Set XDEBUG_MODE=coverage or xdebug.mode=coverage
#ENV XDEBUG_MODE=coverage

# Debian bullseye (base of the PHP 7.4 images) is EOL, its packages now live on archive.debian.org
RUN  set -eux; \
     sed -i \
     -e 's|deb.debian.org/debian-security|archive.debian.org/debian-security|' \
     -e 's|deb.debian.org/debian|archive.debian.org/debian|' \
     /etc/apt/sources.list; \
     apt-get update && apt-get install -y \
     git \
     nano \
     less \
     unzip \
     zip \
     default-mysql-client \
     ; \
     rm -rf /var/lib/apt/lists/*

# Git add safe directory for the working directory


# Needed for Db driver
# https://github.com/Codeception/Codeception/issues/3605
RUN docker-php-ext-install \
    pdo_mysql

RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php && \
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    rm /tmp/composer-setup.php

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x wp-cli.phar && \
    mv wp-cli.phar /usr/local/bin/wp
