FROM php:8.2-apache

# Install MariaDB client AND Git
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y mariadb-client git \
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

# Install php-mysql driver
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Give Apache ownership
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html