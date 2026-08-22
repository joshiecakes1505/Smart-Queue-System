FROM php:8.2-cli

# 1. Install system dependencies required for Laravel & Node
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libpng-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql mbstring zip gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install Node.js 22 (for compiling Vite assets)
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 4. Install PHP dependencies first so `composer install` is cached unless
#    composer.json/composer.lock actually change.
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-autoloader

# 5. Install Node dependencies (also cached independently of app source changes)
COPY package.json package-lock.json ./
RUN npm ci

# 6. Copy the rest of the application source
COPY . .

# 7. Finish PHP autoloading (now that app/ is present) and build Vite assets
RUN composer dump-autoload --optimize --no-dev \
    && npm run build \
    && npm prune --omit=dev

# 8. Writable dirs Laravel needs at runtime
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN chmod +x docker/entrypoint.sh

# NOTE: config:cache / route:cache / view:cache and migrations run from
# docker/entrypoint.sh at container *start*, not here at build time — the
# build has no access to real runtime env vars (DB credentials, APP_KEY,
# REVERB_*, etc.), so caching them now would freeze wrong values into the image.

EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]

# Default process: the web server. This image is also meant to back the
# queue worker and scheduler as separate services/processes pointed at the
# same image, overriding this CMD — see the block below.
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

# ---------------------------------------------------------------------------
# This app queues its two-factor login codes and account-created emails
# (App\Notifications\SendTwoFactorCode implements ShouldQueue, and
# QUEUE_CONNECTION=database) and schedules a daily account-lifecycle sweep
# plus nightly backups (routes/console.php). Nothing in this single CMD runs
# a queue worker or the scheduler, so on its own this container will queue
# 2FA emails that never get sent, and skip the scheduled jobs entirely.
#
# Run these as additional processes/services against this same image,
# overriding CMD (entrypoint.sh still migrates + caches config first):
#
#   Queue worker:  php artisan queue:work --tries=3 --max-time=3600
#   Scheduler:     php artisan schedule:work
#
# (On Railway specifically: add two more services from this repo/image and
# set each one's Custom Start Command to one of the lines above.)
# ---------------------------------------------------------------------------
