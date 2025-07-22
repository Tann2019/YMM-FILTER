# BigCommerce YMM Widget Management

This guide explains how to manage YMM (Year/Make/Model) widgets in your BigCommerce stores using the built-in Laravel artisan command.

## Overview

The `bigcommerce:widgets` command allows you to:
- **List** all widgets and scripts in a specific store
- **Remove** YMM widgets from a specific store  
- **Install** new YMM widgets with updated URLs

This is particularly useful when you need to update your ngrok URL or deploy to production with a new domain.

## Command Syntax

```bash
php artisan bigcommerce:widgets {action} {store} [options]
```

### Arguments
- `action` - What to do: `list`, `remove`, or `install`
- `store` - Store hash (e.g., `rgp5uxku7h`, `store-rgp5uxku7h-1`)

### Options
- `--url=URL` - Your ngrok or app URL (required for install)
- `--force` - Skip confirmation prompts (useful for scripts)

## Usage Examples

### 1. List All Widgets in a Store

See what widgets and scripts are currently installed:

```bash
php artisan bigcommerce:widgets list rgp5uxku7h
```

**Output Example:**
```
Working with store: rgp5uxku7h

🔍 Scripts:
+--------------------------------------+-------------------+------------------------+----------+
| ID                                   | Name              | Description            | Location |
+--------------------------------------+-------------------+------------------------+----------+
| abc123-def456-ghi789                 | YMM Filter Widget | Vehicle compatibility  | footer   |
+--------------------------------------+-------------------+------------------------+----------+

🧩 Widget Templates:
+--------------------------------------+-------------------+--------+
| ID                                   | Name              | Kind   |
+--------------------------------------+-------------------+--------+
| def456-ghi789-jkl012                 | YMM Vehicle Filter| custom |
+--------------------------------------+-------------------+--------+
```

### 2. Remove YMM Widgets from a Store

Remove all YMM-related widgets and scripts:

```bash
php artisan bigcommerce:widgets remove rgp5uxku7h
```

**Interactive Mode (default):**
- Shows what will be removed
- Asks for confirmation
- Provides detailed results

**Force Mode (skip confirmations):**
```bash
php artisan bigcommerce:widgets remove rgp5uxku7h --force
```

**Output Example:**
```
🗑️  Removing YMM widgets from store: rgp5uxku7h

Scanning for YMM widgets to remove...
Found YMM widgets to remove:
🧩 Widget Templates:
  - YMM Vehicle Filter (ID: 84192e4f-589f-4638-b4a0-c4096d447bb1)
  - YMM Vehicle Filter (ID: d1ef8b44-2e92-407a-b866-7ba7174d1504)

 Are you sure you want to remove ALL YMM widgets from store 'rgp5uxku7h'? (yes/no) [no]:
 > yes

🗑️  Removal Results:

No YMM scripts found to remove.

Widget templates removed:
✅ YMM Vehicle Filter (ID: 84192e4f-589f-4638-b4a0-c4096d447bb1)
✅ YMM Vehicle Filter (ID: d1ef8b44-2e92-407a-b866-7ba7174d1504)

✅ Widget removal completed for store: rgp5uxku7h!
```

### 3. Install New Widget with Updated URL

Install a new YMM widget with your current ngrok URL:

```bash
php artisan bigcommerce:widgets install rgp5uxku7h --url=https://abc123.ngrok-free.app
```

**Output Example:**
```
🚀 Installing YMM widget for store: rgp5uxku7h
Using URL: https://abc123.ngrok-free.app

✅ Widget installed successfully!
+----------+--------------------------------------+
| Property | Value                                |
+----------+--------------------------------------+
| Store    | rgp5uxku7h                          |
| Widget ID| def456-ghi789-jkl012                |
| Name     | YMM Filter Widget                    |
| Location | footer                               |
| API URL  | https://abc123.ngrok-free.app        |
| Status   | Active                               |
+----------+--------------------------------------+

📋 Next Steps:
1. The widget script has been installed in your store
2. Add the widget container to your theme where you want it to appear:
   <div id="ymm-filter-container"></div>
3. Test the widget on your storefront
4. Ensure your ngrok tunnel is running and accessible
```

## Common Workflows

### Updating Your ngrok URL

When your ngrok URL changes:

1. **Remove old widgets:**
   ```bash
   php artisan bigcommerce:widgets remove rgp5uxku7h
   ```

2. **Install with new URL:**
   ```bash
   php artisan bigcommerce:widgets install rgp5uxku7h --url=https://your-new-url.ngrok-free.app
   ```

### Deploying to Production

When moving from development to production:

1. **Remove development widgets:**
   ```bash
   php artisan bigcommerce:widgets remove rgp5uxku7h --force
   ```

2. **Install with production URL:**
   ```bash
   php artisan bigcommerce:widgets install rgp5uxku7h --url=https://your-production-domain.com
   ```

### Managing Multiple Stores

If you have multiple stores, run commands for each store individually:

```bash
# Store 1
php artisan bigcommerce:widgets list store1hash
php artisan bigcommerce:widgets remove store1hash --force
php artisan bigcommerce:widgets install store1hash --url=https://your-url.com

# Store 2  
php artisan bigcommerce:widgets list store2hash
php artisan bigcommerce:widgets remove store2hash --force
php artisan bigcommerce:widgets install store2hash --url=https://your-url.com
```

## Store Hash Formats

The command automatically handles different store hash formats:

✅ **Supported formats:**
- `rgp5uxku7h`
- `store-rgp5uxku7h-1` 
- `stores/rgp5uxku7h`

All will be cleaned to: `rgp5uxku7h`

## Troubleshooting

### Authentication Errors

If you get authentication errors:

1. **Check your .env file** has correct BigCommerce credentials:
   ```env
   BC_LOCAL_CLIENT_ID=your_client_id
   BC_LOCAL_SECRET=your_secret  
   BC_LOCAL_ACCESS_TOKEN=your_access_token
   BC_LOCAL_STORE_HASH=rgp5uxku7h
   ```

2. **Verify store hash** matches your BigCommerce store

### Widget Not Appearing

If the widget installs but doesn't appear:

1. **Add container to theme:**
   ```html
   <div id="ymm-filter-container"></div>
   ```

2. **Check browser console** for JavaScript errors

3. **Verify ngrok tunnel** is running and accessible

4. **Test API endpoints** manually:
   ```
   https://your-ngrok-url.ngrok-free.app/api/ymm/rgp5uxku7h/years
   ```

### CORS Issues

If you get CORS errors:

1. **Ensure CORS middleware** is applied to YMM routes (should be automatic)

2. **Check if ngrok URL changed** and reinstall widget

3. **Verify API endpoints** return proper CORS headers

## API Endpoints

The widget uses these API endpoints:

- `GET /api/ymm/{storeHash}/years` - Get available years
- `GET /api/ymm/{storeHash}/makes?year={year}` - Get makes for year  
- `GET /api/ymm/{storeHash}/models?year={year}&make={make}` - Get models
- `GET /api/ymm/{storeHash}/search?year={year}&make={make}&model={model}` - Search products

## Security Notes

- **Development only:** Use ngrok URLs only for development
- **Production:** Use proper HTTPS domains for production
- **Token security:** Keep your BigCommerce access tokens secure
- **Force mode:** Use `--force` carefully in production scripts

## Need Help?

Common issues:
- **Store not found:** Check your store hash and credentials
- **Widget not working:** Verify ngrok tunnel and API endpoints  
- **CORS errors:** Reinstall widget with correct URL
- **Multiple widgets:** Remove old ones before installing new ones

For more help, check the main project documentation or create an issue.
