# ==============================================================================
# Stage 1: Build Frontend Assets (Vite + Tailwind CSS 4)
# ==============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm install --ignore-scripts

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

# ==============================================================================
# Stage 2: Production Application (PHP 8.4-FPM + Nginx)
# ==============================================================================
FROM php:8.4-fpm-alpine

# Install system dependencies, Nginx, and PHP extension build libraries
RUN apk add --no-cache \
    nginx \
    gettext \
    curl \
    git \
    unzip \
    libpq-dev \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        bcmath \
        zip \
        opcache \
        mbstring

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first (leverages Docker layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Copy application source
COPY . .

# Copy compiled Vite assets from Stage 1
COPY --from=frontend /app/public/build ./public/build

# Finalize Composer autoloader
RUN composer dump-autoload --optimize --no-dev

# Configure Nginx & Entrypoint (strip Windows CRLF line endings if present)
COPY docker/nginx.conf.template /etc/nginx/http.d/default.conf.template
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
