# -----------------------------
# Stage 1 - Build Laravel + Vite
# -----------------------------
FROM php:8.2-cli AS builder

# Install system packages
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    zip \
    gnupg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

# Install Node.js 22
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy project
COPY . .

# Install PHP dependencies (creates vendor/)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Install Node dependencies
RUN npm install

# Build frontend
RUN npm run build


# -----------------------------
# Stage 2 - Runtime
# -----------------------------
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

WORKDIR /var/www

COPY --from=builder /var/www .

RUN php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear

CMD ["php-fpm"]