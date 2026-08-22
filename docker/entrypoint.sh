#!/usr/bin/env bash
set -e

# Runs on every container boot (web, worker, or scheduler role — see Dockerfile
# comments). Deliberately does NOT run at image build time: build time has no
# access to the real runtime environment variables (DB credentials, APP_KEY,
# etc.), so caching config/routes then would freeze wrong values into the image.

php artisan migrate --force --isolated

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
