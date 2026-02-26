# Deployment Testing Runbook

Use this runbook to validate the project in a production-like state before actual deployment.

## 1) Prerequisites

- PHP 8.2+
- Composer
- Node.js + npm
- MySQL running with database `smart_queue_db` (or update your `.env` accordingly)

## 2) Environment Baseline

Set these values for deployment testing in `.env`:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=http://<your-host>
```

Keep your existing DB credentials, then run:

```
php artisan optimize:clear
php artisan key:generate
php artisan migrate --force
```

## 3) Full Deployment Test Command

Run the new Composer script:

```
composer run deploy:test
```

This will:

1. Run backend tests (`php artisan test`)
2. Build frontend assets (`npm run build`)
3. Validate Vite manifest and build files (`php artisan deploy:check-assets`)
4. Cache config/routes/views for production behavior

You can run the asset check alone with:

```
composer run deploy:check-assets
```

## 4) Start Server for Validation

For local deployment testing:

```
php -S 127.0.0.1:8000 -t public
```

## 5) Smoke Test Checklist

- Landing page loads: `/`
- Admin login and dashboard: `/admin/dashboard`
- Frontdesk queue create and print flow: `/frontdesk/queues`
- Cashier queue actions: `/cashier`
- Public queue status endpoint: `/api/queue/{queue_number}/status`
- Display board: `/display`

## 6) Post-Test Cleanup (Optional)

If you want to return to a non-cached dev state:

```
composer run deploy:clear
```
