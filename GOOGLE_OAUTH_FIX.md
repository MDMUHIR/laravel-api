# Google OAuth Redirection Fix - Backend Changes Summary

## Issues Fixed

### 1. Session Cookie Configuration
**File: `config/session.php`**
- Changed `same_site` from `lax` to `none` (required for OAuth cross-site requests)
- Changed `secure` default to `true` (cookies must be secure when SameSite=none)

### 2. Trust Proxies Configuration
**File: `app/Http/Middleware/TrustProxies.php`**
- Changed `$proxies` from `null` to `'*'` (trusts all proxies, needed for HTTPS behind load balancers)

### 3. Web Routes for OAuth
**File: `routes/web.php`**
- Added explicit Google OAuth routes to web routes with proper web middleware

---

## Required .env Variables (PRODUCTION)

Add these to your production `.env` file:

```env
# Application
APP_URL=https://kidspare.api.kidspare.shop
APP_ENV=production

# Google OAuth - MUST BE FILLED WITH REAL VALUES
GOOGLE_CLIENT_ID=your_actual_google_client_id_here
GOOGLE_CLIENT_SECRET=your_actual_google_client_secret_here
GOOGLE_REDIRECT_URI=https://kidspare.api.kidspare.shop/api/auth/google/callback

# Frontend URL
FRONTEND_URL=https://kidspare.shop

# Session Configuration
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=none
SESSION_LIFETIME=120

# CORS
CORS_ALLOWED_ORIGINS=https://kidspare.shop,https://www.kidspare.shop,https://kidspare.api.kidspare.shop
```

---

## How to Get Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Navigate to **APIs & Services** > **Credentials**
4. Click **Create Credentials** > **OAuth 2.0 Client ID**
5. Configure the OAuth consent screen (External user type)
6. Add these **Authorized redirect URIs**:
   - `https://kidspare.api.kidspare.shop/api/auth/google/callback`
7. Copy the Client ID and Client Secret to your `.env` file

---

## After Deployment - Clear Caches

Run these commands after deploying:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## Frontend Implementation Instructions

After backend is fixed, ensure your frontend handles the OAuth callback properly:

### Frontend Route
Create a page at: `/auth/google/callback`

### Sample Implementation

```javascript
// /pages/auth/google/callback.js or similar
import { useEffect } from 'react';
import { useRouter } from 'next/router';

const GoogleCallback = () => {
  const router = useRouter();

  useEffect(() => {
    const { token, user, error } = router.query;
    
    if (error) {
      // Handle error
      console.error('Google login error:', error);
      router.push('/login?error=google_login_failed');
      return;
    }
    
    if (token && user) {
      // Store token and user data
      localStorage.setItem('token', token);
      localStorage.setItem('user', user);
      
      // Redirect to dashboard or home
      router.push('/dashboard');
    }
  }, [router.query]);

  return <div>Processing Google login...</div>;
};

export default GoogleCallback;
```

### Login Button

```javascript
const handleGoogleLogin = () => {
  // Redirect to backend Google OAuth endpoint
  window.location.href = 'https://kidspare.api.kidspare.shop/api/auth/google';
};
```

---

## Test the Flow

1. Visit `https://kidspare.api.kidspare.shop/api/auth/google`
2. You should be redirected to Google's consent screen
3. After consent, you should be redirected back to:
   `https://kidspare.shop/auth/google/callback?token=xxx&user=xxx`

---

## Important Notes

1. **GOOGLE_REDIRECT_URI** in your Google Cloud Console MUST exactly match:
   `https://kidspare.api.kidspare.shop/api/auth/google/callback`

2. **FRONTEND_URL** must be exactly `https://kidspare.shop` (no trailing slash)

3. **SESSION_DOMAIN=null** allows cookies to work across all subdomains

4. If you still get "InvalidStateException" errors, check that:
   - Cookies are being set properly (check browser DevTools)
   - The SESSION_SECURE_COOKIE is working on your HTTPS domain
   - The SameSite=None attribute is being applied to cookies
