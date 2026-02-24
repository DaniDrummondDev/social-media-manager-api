###############################################################################
# Stage 1: Composer dependencies
###############################################################################
FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev

###############################################################################
# Stage 2: PHP 8.4-FPM runtime
###############################################################################
FROM php:8.4-fpm-alpine AS app

ARG UID=1000
ARG GID=1000

# System dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache \
        sockets

# Redis extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# Cleanup build dependencies
RUN apk del $PHPIZE_DEPS linux-headers \
    && rm -rf /tmp/pear /var/cache/apk/*

# Create user with host UID/GID to avoid permission issues
RUN deluser --remove-home www-data 2>/dev/null || true \
    && addgroup -g ${GID} appuser \
    && adduser -u ${UID} -G appuser -s /bin/sh -D appuser

# PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /var/www/html

# Copy application
COPY --from=composer /app/vendor ./vendor
COPY . .

# Set permissions
RUN chown -R appuser:appuser /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
