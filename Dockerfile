# Base image with Nginx and PHP 8.2
FROM richarvey/nginx-php-fpm:latest

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Image configuration environment variables
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV DOCUMENT_ROOT=/var/www/html/public
ENV WEBROOT=/var/www/html/public
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
RUN chmod +x /var/www/html/scripts/00-migrate.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Remove default Nginx configs and install custom Laravel site config
RUN rm -rf /etc/nginx/sites-enabled/* /etc/nginx/sites-available/* /etc/nginx/conf.d/*
COPY conf/nginx/site.conf /etc/nginx/sites-available/default
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Expose HTTP port
EXPOSE 80
