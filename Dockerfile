FROM php:8.3-cli-bullseye

# System dependencies
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libssl-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        bcmath \
        ctype \
        dom \
        intl \
        fileinfo \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy composer files first (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy package files
COPY package.json package-lock.json ./
RUN npm ci

# Copy full app
COPY . .

# Finish composer setup
RUN composer dump-autoload --optimize

# Build Vite assets
RUN npm run build

# Cache Laravel bootstrap files
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 8000

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
