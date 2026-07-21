FROM php:8.2-cli

# 1. Install system dependencies required for Laravel & Node
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install Node.js 22 (For compiling Vite assets)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# 3. Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 4. Copy application source code
COPY . .

# 5. Install production PHP and Node dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm install

# 6. Compile Vite assets (Creates public/build)
RUN npm run build

# 7. Optimize Laravel cache
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# 8. Expose Laravel via HTTP server bound to Railway's dynamic port
CMD php artisan serve --host=0.0.0.0 --port=$PORT