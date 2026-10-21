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
# Built from the GitHub source, pecl.php.net refuses the downloads from the CI runners
ARG PCOV_VERSION=1.0.12
RUN set -eux; \
    curl -fsSL "https://github.com/krakjoe/pcov/archive/refs/tags/v${PCOV_VERSION}.tar.gz" | tar -xz -C /tmp; \
    cd "/tmp/pcov-${PCOV_VERSION}"; \
    phpize; \
    ./configure --enable-pcov; \
    make -j"$(nproc)"; \
    make install; \
    cd /; \
    rm -rf "/tmp/pcov-${PCOV_VERSION}"; \
    docker-php-ext-enable pcov

# If you want to use xdebug, uncomment the following lines
# Install xdebug for code coverage
#RUN set -eux; \
#    pecl install xdebug-3.1.4; \
#    docker-php-ext-enable xdebug

# Set XDEBUG_MODE=coverage or xdebug.mode=coverage
#ENV XDEBUG_MODE=coverage

RUN  set -eux; \
     apt-get update && apt-get install -y \
     git \
     nano \
     less \
     unzip \
     zip \
     default-mysql-client \
     ; \
     rm -rf /var/lib/apt/lists/*

# The MariaDB client verifies the server certificate by default, the MySQL test server has a self-signed one
RUN printf '[client]\nssl-verify-server-cert=off\n' > /etc/mysql/conf.d/test-ssl.cnf

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

# Optional, replace the image core with another WordPress version,
# the entrypoint copies /usr/src/wordpress into the web root
ARG WP_CORE_VERSION
RUN set -eux; \
    if [ -n "${WP_CORE_VERSION}" ]; then \
        wp core download --version="${WP_CORE_VERSION}" --path=/tmp/wordpress --skip-content --allow-root; \
        rm -rf /usr/src/wordpress/wp-admin /usr/src/wordpress/wp-includes; \
        cp -a /tmp/wordpress/. /usr/src/wordpress/; \
        rm -rf /tmp/wordpress; \
        chown -R www-data:www-data /usr/src/wordpress; \
    fi

# Classic theme used by the e2e dump, the plugin filters the_content and the legacy widgets
ARG CLASSIC_THEME=twentytwentyone
RUN set -eux; \
    curl -fsSL "https://downloads.wordpress.org/theme/${CLASSIC_THEME}.zip" -o /tmp/theme.zip; \
    unzip -q /tmp/theme.zip -d /usr/src/wordpress/wp-content/themes; \
    rm /tmp/theme.zip; \
    chown -R www-data:www-data /usr/src/wordpress/wp-content/themes
