# 🚨 CRITICAL: Production Server Commands

## The Problem

Your server is showing `http://localhost:3000` because the **config is cached** and not reading your updated `.env` file.

## Immediate Fix (Run These Commands on Production Server)

SSH into your server and run:

```bash
cd /var/www/kidspare-api  # or wherever your project is

# 1. CLEAR ALL CACHES (MOST IMPORTANT!)
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 2. Verify .env is correct
cat .env | grep FRONTEND_URL
# Should output: FRONTEND_URL=https://kidspare.shop

# 3. Test configuration
php artisan tinker --execute="echo config('app.frontend_url');"
# Should output: https://kidspare.shop

# 4. If still showing localhost, restart web server
sudo systemctl restart php8.1-fpm  # or your PHP version
sudo systemctl restart nginx         # or apache2
```

## Test URLs

1. **Debug Config**: https://kidspare.api.kidspare.shop/debug/env
   - Should show `frontend_url: "https://kidspare.shop"`

2. **Test Google OAuth**: https://kidspare.api.kidspare.shop/api/auth/google
   - Should redirect to Google consent screen

3. **After Google Auth**: Should redirect to https://kidspare.shop/auth/google/callback

## If Still Not Working

### Check .env file permissions

```bash
ls -la .env
# Should be readable by web server user (www-data or similar)

# Fix permissions
sudo chown www-data:www-data .env
sudo chmod 644 .env
```

### Check if .env is actually being read

```bash
php -r "require 'vendor/autoload.php'; \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); \$dotenv->load(); echo 'FRONTEND_URL=' . getenv('FRONTEND_URL') . PHP_EOL;"
```

### Nuclear Option: Hardcode the URL

If you absolutely cannot get the env variable to work, hardcode it:

Edit `app/Http/Controllers/AuthController.php`:

Find all instances of:
```php
$frontendCallbackUrl = config('app.frontend_url').'/auth/google/callback';
```

Replace with:
```php
$frontendCallbackUrl = 'https://kidspare.shop/auth/google/callback';
```

Then clear caches again.

## Common Server Locations

Your Laravel project might be in:
- `/var/www/html/kidspare-api`
- `/var/www/kidspare-api`
- `/home/username/kidspare-api`
- `/opt/kidspare-api`

Find it with:
```bash
find /var/www /home -name "artisan" 2>/dev/null
```

## Debug Log Location

Check for errors:
```bash
tail -f storage/logs/laravel.log
```

## After Every Code Change

ALWAYS run:
```bash
php artisan config:clear
php artisan cache:clear
```

Or use the deployment script:
```bash
./deploy-oauth-fix.sh
```
