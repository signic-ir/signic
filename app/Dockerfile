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
    nodejs \
    npm \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo pdo_mysql pdo_pgsql pgsql bcmath zip exif pcntl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Enable OPcache for production
RUN docker-php-ext-enable opcache

# Configure PHP-FPM to listen on port 8000
COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf

FROM php-base AS dev

# Install composer dependencies
COPY composer.json ./
COPY composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts && rm -rf /root/.composer

FROM php-base AS prod

# Copy composer-installed files
COPY --from=dev /app/vendor /app/vendor

COPY . /app

# Set proper permissions
RUN mkdir -p /app/storage /app/bootstrap/cache && \
    chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Build frontend assets
WORKDIR /app
RUN sed -i 's/:class="statusBadgeClass(attendee.status)" class="badge" :class="statusBadgeClass(attendee.status)"/:class="statusBadgeClass(attendee.status)" class="badge"/' resources/js/Pages/AttendeeQRPage.vue
RUN cat > package.json << 'EOF'
{
  "private": true,
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "watch": "vite watch"
  },
  "dependencies": {
    "vue": "^3.4.0",
    "vue-router": "^4.4.0"
  },
  "devDependencies": {
    "@inertiajs/progress": "^0.2.7",
    "@inertiajs/vue3": "^2.0.0",
    "@vitejs/plugin-vue": "^5.0.0",
    "autoprefixer": "^10.4.0",
    "laravel-vite-plugin": "^1.0.0",
    "postcss": "^8.0.0",
    "tailwindcss": "^3.4.0",
    "vite": "^5.0.0"
  }
}
EOF
RUN npm install --legacy-peer-deps --prefix /app && \
    npm run build --prefix /app

# Expose port
EXPOSE 8000

CMD ["php-fpm"]