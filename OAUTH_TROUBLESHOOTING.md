# Google OAuth Redirection Troubleshooting Guide

## Problem: Still redirecting to localhost:3000 with "Invalid state" error

This means your server is NOT reading the updated `.env` file. The code is falling back to the default value `http://localhost:3000`.

## Quick Fix Steps

### Step 1: SSH into your server and navigate to your project

```bash
ssh your-user@your-server
cd /path/to/your/laravel-project
```

### Step 2: Verify your .env file has the correct values

```bash
cat .env | grep -E "(FRONTEND_URL|GOOGLE_CLIENT|SESSION)"
```

**Expected output:**
```
FRONTEND_URL=https://kidspare.shop
GOOGLE_CLIENT_ID=your_actual_client_id_here
GOOGLE_CLIENT_SECRET=your_actual_client_secret_here
GOOGLE_REDIRECT_URI=https://kidspare.api.kidspare.shop/api/auth/google/callback
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
```

### Step 3: Clear ALL caches

**This is the most important step!**

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan clear-compiled
```

### Step 4: Restart your web server

```bash
# For Apache
sudo systemctl restart apache2

# For Nginx + PHP-FPM
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm  # or your PHP version
```

### Step 5: Test the debug endpoint

Visit: `https://kidspare.api.kidspare.shop/debug/env`

**You should see:**
```json
{
  "app_url": "https://kidspare.api.kidspare.shop",
  "frontend_url": "https://kidspare.shop",
  "session_domain": null,
  "session_secure": true,
  "session_same_site": "none",
  "google_redirect": "https://kidspare.api.kidspare.shop/api/auth/google/callback",
  "google_client_id_set": true
}
```

If `frontend_url` still shows `http://localhost:3000`, your cache is NOT cleared.

---

## If Debug Shows Correct Values But Still Fails

### Problem: "Invalid state" error

This happens when the session is lost between the initial redirect and the callback. Common causes:

#### 1. Session Cookie Issues

Check your browser's developer tools:
- Open DevTools → Application/Storage → Cookies
- Look for cookies from `kidspare.api.kidspare.shop`
- Check if the session cookie has:
  - `Secure: true`
  - `SameSite: None`
  - `Domain: .kidspare.shop` or blank

#### 2. HTTPS Issues

Since your backend uses HTTPS, the session cookie MUST be secure. Check:

```bash
# In your .env
SESSION_SECURE_COOKIE=true
```

#### 3. Try Stateless OAuth (Last Resort)

If sessions are not working, you can use stateless OAuth (less secure but works):

Edit `app/Http/Controllers/AuthController.php`:

```php
public function redirectToGoogle()
{
    return Socialite::driver('google')->stateless()->redirect();
}

public function handleGoogleCallback()
{
    try {
        $googleUser = Socialite::driver('google')->stateless()->user();
        // ... rest of the code
    }
}
```

**Note:** Stateless mode disables CSRF protection. Only use if necessary.

---

## Alternative Solution: No-Session OAuth

If you cannot get sessions to work, here's an alternative approach that doesn't rely on Laravel sessions:

### 1. Create a custom OAuth flow

Instead of using Socialite's built-in redirect, generate the URL manually:

```php
public function redirectToGoogle()
{
    $clientId = config('services.google.client_id');
    $redirectUri = config('services.google.redirect');
    $state = base64_encode(random_bytes(32));
    
    // Store state in cache instead of session
    Cache::put('oauth_state_' . $state, true, now()->addMinutes(10));
    
    $url = "https://accounts.google.com/o/oauth2/v2/auth";
    $url .= "?client_id={$clientId}";
    $url .= "&redirect_uri=" . urlencode($redirectUri);
    $url .= "&response_type=code";
    $url .= "&scope=" . urlencode('openid email profile');
    $url .= "&state={$state}";
    
    return redirect()->away($url);
}
```

### 2. Update callback handler

```php
public function handleGoogleCallback(Request $request)
{
    $state = $request->get('state');
    
    // Validate state from cache instead of session
    if (!Cache::pull('oauth_state_' . $state)) {
        $frontendCallbackUrl = config('app.frontend_url').'/auth/google/callback';
        return redirect()->away("{$frontendCallbackUrl}?error=".urlencode('Invalid state'));
    }
    
    // Exchange code for token manually
    $code = $request->get('code');
    $clientId = config('services.google.client_id');
    $clientSecret = config('services.google.client_secret');
    $redirectUri = config('services.google.redirect');
    
    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
    ]);
    
    $tokens = $response->json();
    
    // Get user info
    $userResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . $tokens['access_token'],
    ])->get('https://www.googleapis.com/oauth2/v2/userinfo');
    
    $googleUser = $userResponse->json();
    
    // ... rest of user creation/login logic
}
```

---

## Common Issues and Solutions

### Issue: "InvalidStateException" persists

**Solution A: Session driver**
Change from `file` to `database` or `redis`:

```bash
# In .env
SESSION_DRIVER=database

# Then run
php artisan session:table
php artisan migrate
```

**Solution B: Domain mismatch**
Make sure these domains match:
- Google Console redirect URI
- Your `GOOGLE_REDIRECT_URI` in .env
- Your actual callback URL

### Issue: Works in Postman but not in browser

**Cause:** CORS or cookie issues

**Solution:**
Check that your CORS configuration allows credentials:

```php
// config/cors.php
'supports_credentials' => true,
```

### Issue: Redirect works but token is not passed

**Cause:** URL encoding issue

**Solution:** Check that your frontend properly decodes the URL-encoded user JSON:

```javascript
const userData = JSON.parse(decodeURIComponent(router.query.user));
```

---

## Debugging Checklist

- [ ] `.env` file updated with correct FRONTEND_URL
- [ ] All caches cleared (`config:clear`, `cache:clear`)
- [ ] Web server restarted
- [ ] `debug/env` endpoint shows correct FRONTEND_URL
- [ ] Session directory is writable (`storage/framework/sessions`)
- [ ] Google Console has correct redirect URI
- [ ] Browser cookies are enabled (test in incognito)
- [ ] HTTPS is working on both frontend and backend
- [ ] CORS allows credentials

---

## Emergency Fix: Hardcode the URL

If nothing works, hardcode your frontend URL as a last resort:

Edit `app/Http/Controllers/AuthController.php`:

```php
// Replace this:
$frontendCallbackUrl = config('app.frontend_url').'/auth/google/callback';

// With this:
$frontendCallbackUrl = 'https://kidspare.shop/auth/google/callback';
```

Then clear caches:
```bash
php artisan config:clear
php artisan cache:clear
```

---

## Need More Help?

1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode temporarily: `APP_DEBUG=true` in .env
3. Check browser console for JavaScript errors
4. Verify Google OAuth credentials are correct in Google Cloud Console
