# Server Configuration Fix - Laravel 12 on Windows/XAMPP

## Problem

Laravel 12.52.0 missing `server.php` file in vendor directory, causing `artisan serve` to fail with:

```
PHP Fatal error: Failed opening required '...vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
```

## Solution Applied

### Step 1: Created Missing server.php

Created file at: `vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`

- This file is required by Laravel's development server
- Contains routing logic for the built-in PHP webserver

### Step 2: Switched to PHP Built-in Webserver

Instead of `php artisan serve`, now using:

```bash
php -S 127.0.0.1:8000 -t public
```

**Why this works:**

- Directly uses PHP's built-in webserver
- Points root to `public/` directory (correct for Laravel)
- No dependency on artisan serve infrastructure
- More stable on Windows/XAMPP

**How to use:**

```bash
cd c:\xampp\htdocs\smart-queue-system
php -S 127.0.0.1:8000 -t public
```

Then access: `http://127.0.0.1:8000`

### Files Modified

- Created: `vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`

## Server Status

✅ Server now running successfully on `http://127.0.0.1:8000`

## Alternative: Use XAMPP Apache Directly

If you want to use XAMPP's Apache:

1. Configure httpd.conf to point to `c:\xampp\htdocs\smart-queue-system\public`
2. Or access via `http://localhost/smart-queue-system/public`

---

**Note**: This fix is specific to Laravel 12.52.0. If you update Laravel in the future, re-run:

```bash
composer install
composer dump-autoload -o
```
