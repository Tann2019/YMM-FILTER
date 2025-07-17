# 🎯 Quick BigCommerce App Setup (Step-by-Step)

## 📋 Prerequisites
- [ ] BigCommerce store (trial or paid)
- [ ] Laravel app running locally
- [ ] ngrok installed (for local development)

## 🚀 Step-by-Step Setup

### Step 1: Create BigCommerce API Account (For Local Testing)

1. **Go to your BigCommerce store admin**
2. **Navigate to:** Settings → API → API Accounts
3. **Click:** "Create API Account"
4. **Fill in:**
   ```
   Name: YMM Filter Local Dev
   OAuth Scopes:
   ✅ Products: Read-only
   ✅ Store Information: Read-only
   ✅ Store Inventory: Read-only
   ```
5. **Save and copy the credentials:**
   - Client ID
   - Client Secret
   - Access Token
   - Store Hash (from the URL: store-XXXXXXX.mybigcommerce.com)

### Step 2: Update Your .env File

```env
# Update these with your actual credentials
BC_LOCAL_CLIENT_ID=your_client_id_here
BC_LOCAL_SECRET=your_client_secret_here
BC_LOCAL_ACCESS_TOKEN=your_access_token_here
BC_LOCAL_STORE_HASH=your_store_hash_here
```

### Step 3: Test API Connection

```bash
# Start your Laravel app
php artisan serve --port=8000

# Test the API endpoint
curl "http://localhost:8000/api/bc-ymm/makes"
```

### Step 4: Add Test Products with Custom Fields

1. **Go to:** Products → View → [Select a product] → Edit
2. **Scroll to:** Custom Fields section
3. **Add these fields:**
   ```
   Name: ymm_make          Value: Ford
   Name: ymm_model         Value: F-150
   Name: ymm_year_start    Value: 2015
   Name: ymm_year_end      Value: 2020
   ```
4. **Save the product**

### Step 5: Test the YMM Filter

1. **Visit:** `http://localhost:8000/bc-ymm-filter`
2. **You should see:** Ford in the Makes dropdown
3. **Select Ford:** Should show F-150 in Models
4. **Select F-150:** Should show years 2015-2020
5. **Select a year:** Should show your test product

## 🔧 If You Want to Create a Full BigCommerce App

### Step 1: Create App in Developer Portal

1. **Visit:** https://devtools.bigcommerce.com/
2. **Click:** "Create an app" → "Build an app"
3. **Fill in:**
   ```
   App Name: YMM Bumper Filter
   Description: Vehicle compatibility filter for bumpers
   ```

### Step 2: Set Up ngrok (for callbacks)

```bash
# Start ngrok
ngrok http 8000

# Copy the https URL (e.g., https://abc123.ngrok.io)
```

### Step 3: Configure App URLs

In BigCommerce Developer Portal:
```
Auth Callback URL: https://abc123.ngrok.io/auth/install
Load Callback URL: https://abc123.ngrok.io/auth/load
Uninstall Callback URL: https://abc123.ngrok.io/auth/uninstall
```

### Step 4: Update .env with App Credentials

```env
BC_APP_CLIENT_ID=your_app_client_id
BC_APP_SECRET=your_app_secret
APP_URL=https://abc123.ngrok.io
```

### Step 5: Install App in Store

1. **Go to:** BigCommerce store → Apps → My Apps
2. **Click:** "Unpublished Apps" tab  
3. **Install your app** using the app link from Developer Portal

## 🎯 Quick Start (5 Minutes)

**For immediate testing, just do Steps 1-5 above.** This will get your YMM filter working with BigCommerce products without creating a full app.

## 🆘 Troubleshooting

### "No makes found"
- Check that your BigCommerce API credentials are correct in .env
- Verify you have products with custom fields: ymm_make, ymm_model, etc.
- Check the Laravel logs for API errors

### "API Connection Failed"
- Verify your store hash doesn't include "stores/" prefix
- Check that API scopes include Products: Read-only
- Ensure your IP isn't blocked by BigCommerce

### "Custom fields not showing"
- Make sure field names are exact: ymm_make (not "make" or "ymm-make")
- Verify the products are published and visible
- Check that custom fields have values

## ✅ Success Checklist

- [ ] API credentials added to .env
- [ ] Test product created with custom fields
- [ ] `/api/bc-ymm/makes` returns data
- [ ] YMM filter interface shows makes/models
- [ ] Compatible products display correctly

---

**Need help?** Check the Laravel logs and BigCommerce API responses for specific error messages.
