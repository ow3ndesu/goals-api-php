FROM php:8.2-apache

# Install dependencies
RUN apt-get update && \
    apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite for Slim
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for caching
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --optimize-autoloader

# Copy the rest of the app
COPY . .

# Configure Apache to serve from public/
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/apache2.conf \
    && sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Expose Apache port
EXPOSE 80
