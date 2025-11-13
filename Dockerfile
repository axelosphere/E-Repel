# Use the official PHP-Apache image
FROM php:8.2-apache

# Enable PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files to the web root
COPY . /var/www/html/

# Expose port 80
EXPOSE 80
