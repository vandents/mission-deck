FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev libzip-dev unzip git \
    && docker-php-ext-install pdo_mysql intl zip opcache \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY vhost.conf /etc/apache2/sites-available/000-default.conf

# Composer available inside the container for installs/updates
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
