# Stage 1 - Build Frontend (Vite)
FROM node:22 AS frontend

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy Composer files and install PHP dependencies (creates vendor/)
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts

# Copy package files and install Node dependencies
COPY package*.json ./
RUN npm install

# Copy application source
COPY . .

# Build frontend
RUN npm run build

# Stage 2 - Backend (Laravel + PHP + Composer)
FROM php:8.2-fpm AS backend

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip libpq-dev libonig-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy built frontend
COPY --from=frontend /app/public/dist ./public/dist

# Laravel setup
RUN php artisan config:clear && \
    php artisan route:clear && \
    php artisan view:clear

CMD ["php-fpm"]