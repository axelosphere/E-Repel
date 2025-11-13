# Use the official PHP + Apache image
FROM php:8.2-apache

# Install PostgreSQL extension for PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql

# Copy project files to the container
COPY . /var/www/html/

EXPOSE 80
