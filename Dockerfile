# Use the official PHP + Apache image
FROM php:8.2-apache

# Install PostgreSQL extension for PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy project files to the web root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose port 80
EXPOSE 80

# Start Apache in the foreground (Render default)
CMD ["apache2-foreground"]
