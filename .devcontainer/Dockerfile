FROM php:8.2-apache

# Install system dependencies (ADD zip + gd deps here)
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y \
        mariadb-client \
        git \
        libzip-dev \
        libpng-dev \
        zip \
        unzip \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (ADD zip + gd)
RUN docker-php-ext-install mysqli pdo pdo_mysql zip gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html