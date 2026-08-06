# Base image with Nginx and PHP 8.2
FROM richarvey/nginx-php-fpm:latest

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Image configuration environment variables
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install Node.js, NPM, and PHP extensions required by Laravel & PhpOffice
RUN apk add --no-cache nodejs npm libpng-dev libjpeg-turbo-dev freetype-dev zip libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql bcmath zip

# Run Composer & NPM Build
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-gd
RUN npm install && npm run build

# Storage Link & Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose HTTP port
EXPOSE 80
