FROM php:8.1-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql && a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Expose port 80
EXPOSE 80
