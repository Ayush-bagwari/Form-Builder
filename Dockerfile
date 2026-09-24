# ==============================================================================
# STAGE 1: Frontend Asset Compilation (Node.js)
# ==============================================================================
FROM node:20-alpine AS frontend

WORKDIR /app

# Cache NPM dependencies
COPY package.json package-lock.json ./
RUN npm ci

# Copy frontend source files & configs
COPY resources/ ./resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./

# Compile Tailwind & Vite assets to /app/public/build
RUN npm run build

# ==============================================================================
# STAGE 2: PHP 8.2 & Nginx Application Runtime
# ==============================================================================
FROM richarvey/nginx-php-fpm:latest

WORKDIR /var/www/html

# Environment settings
ENV RUN_SCRIPTS=1
ENV REAL_IP_HEADER=1
ENV DOCUMENT_ROOT=/var/www/html/public
ENV WEBROOT=/var/www/html/public
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install PHP extensions required by Laravel & PhpOffice (Node is no longer needed here!)
RUN apk add --no-cache libpng-dev libjpeg-turbo-dev freetype-dev zip libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql bcmath zip

# Cache Composer dependencies layer (only re-runs if composer files change)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-req=ext-gd

# Copy application source code
COPY . .

# Copy compiled frontend assets from Stage 1
COPY --from=frontend /app/public/build ./public/build

# Generate optimized Composer autoloader
RUN composer dump-autoload --optimize --no-dev

# Permissions for startup scripts, storage, and cache
RUN chmod +x /var/www/html/scripts/*.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Custom Nginx & Supervisor worker configurations
RUN rm -rf /etc/nginx/sites-enabled/* /etc/nginx/sites-available/* /etc/nginx/conf.d/*
COPY conf/nginx/site.conf /etc/nginx/sites-available/default
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
COPY conf/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf

EXPOSE 80

