FROM php:8.3-fpm-bookworm AS php-base

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    bash \
    git \
    curl \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libwebp-dev \
    npm \
    yarn \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql pdo_pgsql pgsql bcmath zip exif pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Enable OPcache for production
RUN docker-php-ext-enable opcache

FROM php-base AS dev

# Install composer dependencies
COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts && rm -rf /root/.composer

FROM php-base AS prod

# Copy composer-installed files
COPY --from=dev /app/vendor /app/vendor

COPY . .

# Set proper permissions
RUN mkdir -p /app/storage /app/bootstrap/cache && \
    chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Expose port
EXPOSE 8000

CMD ["php-fpm"]
