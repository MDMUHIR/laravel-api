#!/bin/bash

# Deployment Script for Google OAuth Fix
# Run this on your production server

echo "=========================================="
echo "Google OAuth Fix Deployment Script"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Step 1: Checking environment file...${NC}"
if [ -f ".env" ]; then
    echo -e "${GREEN}✓ .env file found${NC}"
    
    # Check if FRONTEND_URL is set
    if grep -q "FRONTEND_URL=https://kidspare.shop" .env; then
        echo -e "${GREEN}✓ FRONTEND_URL is correctly set${NC}"
    else
        echo -e "${RED}✗ FRONTEND_URL is missing or incorrect${NC}"
        echo "Please add/update this line in your .env file:"
        echo "FRONTEND_URL=https://kidspare.shop"
    fi
    
    # Check if Google credentials are set
    if grep -q "GOOGLE_CLIENT_ID=your" .env || grep -q "GOOGLE_CLIENT_ID=$" .env; then
        echo -e "${RED}✗ GOOGLE_CLIENT_ID is not set${NC}"
    else
        echo -e "${GREEN}✓ GOOGLE_CLIENT_ID appears to be set${NC}"
    fi
else
    echo -e "${RED}✗ .env file not found!${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 2: Clearing all caches...${NC}"

# Clear config cache
php artisan config:clear
echo -e "${GREEN}✓ Config cache cleared${NC}"

# Clear application cache
php artisan cache:clear
echo -e "${GREEN}✓ Application cache cleared${NC}"

# Clear route cache
php artisan route:clear
echo -e "${GREEN}✓ Route cache cleared${NC}"

# Clear view cache
php artisan view:clear
echo -e "${GREEN}✓ View cache cleared${NC}"

# Clear compiled files
php artisan clear-compiled
echo -e "${GREEN}✓ Compiled files cleared${NC}"

echo ""
echo -e "${YELLOW}Step 3: Testing configuration...${NC}"

# Create a test script
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo 'FRONTEND_URL: ' . config('app.frontend_url') . PHP_EOL;
echo 'APP_URL: ' . config('app.url') . PHP_EOL;
echo 'SESSION_DOMAIN: ' . config('session.domain') . PHP_EOL;
echo 'SESSION_SECURE_COOKIE: ' . (config('session.secure') ? 'true' : 'false') . PHP_EOL;
echo 'SESSION_SAME_SITE: ' . config('session.same_site') . PHP_EOL;
echo 'GOOGLE_REDIRECT: ' . config('services.google.redirect') . PHP_EOL;
echo 'GOOGLE_CLIENT_ID_SET: ' . (config('services.google.client_id') ? 'YES' : 'NO') . PHP_EOL;
" 2>/dev/null || echo -e "${RED}Could not test configuration${NC}"

echo ""
echo -e "${YELLOW}Step 4: Setting proper permissions...${NC}"

# Set permissions for storage and bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo -e "${GREEN}✓ Permissions updated${NC}"

echo ""
echo -e "${YELLOW}Step 5: Checking session directory...${NC}"

# Check if session directory exists and is writable
if [ -d "storage/framework/sessions" ]; then
    if [ -w "storage/framework/sessions" ]; then
        echo -e "${GREEN}✓ Session directory exists and is writable${NC}"
    else
        echo -e "${RED}✗ Session directory exists but is not writable${NC}"
        chmod -R 775 storage/framework/sessions
    fi
else
    echo -e "${RED}✗ Session directory does not exist${NC}"
    mkdir -p storage/framework/sessions
    chmod -R 775 storage/framework/sessions
    echo -e "${GREEN}✓ Session directory created${NC}"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}Deployment script completed!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Test the debug endpoint: https://kidspare.api.kidspare.shop/debug/env"
echo "2. Check that FRONTEND_URL shows https://kidspare.shop"
echo "3. Try the Google login flow again"
echo "4. Check storage/logs/laravel.log for detailed errors"
echo ""
echo "If you still see 'Invalid state' error:"
echo "- Make sure SESSION_DOMAIN is set to 'null' (without quotes) in .env"
echo "- Try using a different browser or incognito mode"
echo "- Check browser console for cookie-related errors"
echo ""
