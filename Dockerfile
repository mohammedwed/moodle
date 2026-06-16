FROM php:8.4-apache

# System deps for PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libsodium-dev \
    libpq-dev \
    default-mysql-client \
    git \
    unzip \
    curl \
    locales \
    && rm -rf /var/lib/apt/lists/* \
    && sed -i 's/# en_AU.UTF-8/en_AU.UTF-8/' /etc/locale.gen \
    && sed -i 's/# en_US.UTF-8/en_US.UTF-8/' /etc/locale.gen \
    && sed -i 's/# ar_SA.UTF-8/ar_SA.UTF-8/' /etc/locale.gen \
    && locale-gen

# PHP extensions required by Moodle
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        intl \
        mbstring \
        opcache \
        pdo \
        pdo_pgsql \
        pgsql \
        pdo_mysql \
        mysqli \
        zip \
        xml \
        xmlreader \
        ctype \
        dom \
        simplexml \
        fileinfo \
        iconv \
        sodium

# phpredis — required for Moodle Redis session handler
RUN pecl install redis && docker-php-ext-enable redis

# Enable Apache rewrite + headers
RUN a2enmod rewrite headers

# Apache vhost — document root is public/
COPY docker/apache.conf /etc/apache2/sites-available/moodle.conf
RUN a2dissite 000-default && a2ensite moodle

# PHP tuning
COPY docker/php.ini /usr/local/etc/php/conf.d/moodle.ini

WORKDIR /var/www/html

# Install Composer deps (layered before COPY . for cache efficiency)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy the rest of the source
COPY . .

# config.php must be at the repo root (for CLI tools in admin/cli/) AND in public/
# (for web entry points). Place the real config at root; public/ proxies into it.
COPY docker/config.php config.php
RUN printf '<?php\nrequire_once __DIR__ . "/../config.php";\n' > public/config.php

# Moodledata directory (override via MOODLE_DATAROOT env var or volume mount)
RUN mkdir -p /var/moodledata \
    && chown -R www-data:www-data /var/www/html /var/moodledata \
    && chmod -R 755 /var/www/html

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --retries=5 \
    CMD curl -sf http://localhost/ | grep -qi 'moodle\|login\|install' || exit 1
