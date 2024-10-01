# Use the official PHP 7.2 image as the base
FROM php:7.2-apache

# Copy php source code
WORKDIR /var/www/html
COPY ./src .

# Install PHP composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install zip extension and unzip command
RUN apt-get update && apt-get install -y zip unzip libzip-dev
RUN docker-php-ext-install zip

# Install SwiftMailer
RUN composer install

# Install Xdebug
RUN pecl channel-update pecl.php.net    \
    && pecl install xdebug-2.9.8        \
    && docker-php-ext-enable xdebug

# Configure Xdebug
COPY docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/

EXPOSE 80
CMD ["apache2-foreground"]