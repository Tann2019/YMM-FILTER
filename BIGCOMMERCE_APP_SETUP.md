# BigCommerce App Development Setup

## Step 1: Install ngrok (for local development)

### Windows (using chocolatey):
```powershell
choco install ngrok
```

### Or download from: https://ngrok.com/download

## Step 2: Start your Laravel app
```bash
php artisan serve --port=8000
```

## Step 3: Start ngrok tunnel
```bash
ngrok http 8000
```

You'll get a URL like: `https://abc123.ngrok.io`

## Step 4: Update BigCommerce App URLs

In your BigCommerce Developer Portal (https://devtools.bigcommerce.com/), set:

```
Auth Callback URL: https://abc123.ngrok.io/auth/install
Load Callback URL: https://abc123.ngrok.io/auth/load
Uninstall Callback URL: https://abc123.ngrok.io/auth/uninstall
```

## Step 5: Update .env APP_URL
```
APP_URL=https://abc123.ngrok.io
```

## Step 6: Test Installation

1. Go to your BigCommerce store admin
2. Apps > My Apps > Unpublished Apps
3. Install your app using the app URL from Developer Portal

## Alternative: Deploy to Production

### Option A: Heroku
```bash
# Install Heroku CLI
# Create new app
heroku create your-ymm-filter-app
git push heroku main
```

### Option B: DigitalOcean, AWS, etc.
Deploy your Laravel app and use the production URL in BigCommerce Developer Portal.

## Environment Variables Needed:

```env
# Get from BigCommerce Developer Portal
BC_APP_CLIENT_ID=your_client_id
BC_APP_SECRET=your_client_secret

# Get from BigCommerce Store > Settings > API Accounts (for local testing)
BC_LOCAL_CLIENT_ID=your_local_client_id
BC_LOCAL_SECRET=your_local_secret  
BC_LOCAL_ACCESS_TOKEN=your_local_access_token
BC_LOCAL_STORE_HASH=your_store_hash
```
