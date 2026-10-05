FROM php:8.2-apache

# Install MySQL extensions for PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set working directory permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
