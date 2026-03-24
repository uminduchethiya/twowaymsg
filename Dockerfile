FROM php:8.3-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        curl \
        zip \
        bcmath \
        tokenizer \
        ctype \
        fileinfo \
        dom \
        intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy composer files first for layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy package files
COPY package.json package-lock.json ./
RUN npm ci

# Copy the rest of the app
COPY . .

# Run composer scripts now that full app is present
RUN composer run-script post-autoload-dump || true

# Build frontend assets
RUN npm run build

# Cache Laravel config/routes/views
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE $PORT

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
