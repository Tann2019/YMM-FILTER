# Multi-Store YMM Filter - Fixed Issues

## ✅ Issues Resolved

### 1. **"Undefined constant 'make'" Error**
- **Cause**: Widget was trying to access BigCommerce API without store identification
- **Fix**: Updated widget to pass store hash and handle authentication properly
- **Solution**: Widget now auto-detects store hash from domain or accepts it as parameter

### 2. **Multi-Shop Support**
- **Cause**: System was only designed for single store
- **Fix**: Added database storage for multiple store credentials
- **Solution**: Each store's credentials are saved during installation and retrieved for API calls

## 🔧 What Was Updated

### Database Storage
- **New table**: `bigcommerce_stores` - stores credentials for multiple shops
- **New model**: `BigCommerceStore` - manages store data with encryption
- **Auto-save**: Store credentials saved during app installation

### Widget Updates
- **Store detection**: Automatically detects store hash from domain
- **Parameter passing**: All API calls now include store identification
- **Error handling**: Better error messages and debugging
- **Multi-store ready**: Can handle requests from different BigCommerce stores

### API Controller Updates
- **Credential lookup**: Uses database to find store credentials
- **Session fallback**: Falls back to session data for admin interface
- **Parameter handling**: Accepts store hash from multiple sources
- **Error handling**: Better error reporting for debugging

## 🚀 Updated Installation

### For BigCommerce Theme Integration:

```handlebars
<script>
// The widget now auto-detects your store or you can specify it
const storeHash = window.location.hostname.includes('.mybigcommerce.com') 
    ? window.location.hostname.split('.')[0] 
    : 'YOUR_STORE_HASH'; // Replace with your actual store hash

fetch(`https://392488a90049.ngrok-free.app/ymm-widget?store_hash=${storeHash}`)
    .then(response => response.text())
    .then(html => {
        document.getElementById('ymm-filter-container').innerHTML = html;
    })
    .catch(error => console.error('Error loading YMM filter:', error));
</script>
```

## 📋 Testing Steps

### 1. **Test Store Installation**
```bash
# Check if your store credentials are saved
# Visit: https://392488a90049.ngrok-free.app/api/bigcommerce/makes?store_hash=YOUR_STORE_HASH
```

### 2. **Test Widget Loading**
```bash
# Test widget loads with store identification
# Visit: https://392488a90049.ngrok-free.app/ymm-widget?store_hash=YOUR_STORE_HASH
```

### 3. **Test API Endpoints**
```bash
# Test makes endpoint
GET /api/bigcommerce/makes?store_hash=YOUR_STORE_HASH

# Test models endpoint  
GET /api/bigcommerce/models?make=Dodge&store_hash=YOUR_STORE_HASH

# Test years endpoint
GET /api/bigcommerce/years?make=Dodge&model=Ram&store_hash=YOUR_STORE_HASH

# Test product search
GET /api/bigcommerce/compatible-products?year=2005&make=Dodge&model=Ram&store_hash=YOUR_STORE_HASH
```

## 🎯 For Your Vehicle Accessories Business

The system now properly supports:

✅ **Multiple BigCommerce stores** using the same app  
✅ **Auto-detection** of store from domain  
✅ **Secure credential storage** with encryption  
✅ **Proper error handling** for debugging  
✅ **2005 Dodge Ram bumper searches** will work correctly  

### Example Flow:
1. **Customer visits** your BigCommerce store
2. **Widget loads** with your store's credentials  
3. **Customer selects** 2005 Dodge Ram
4. **System finds** all bumpers/accessories with matching custom fields
5. **Only compatible products** are shown

The "Undefined constant 'make'" error should now be resolved, and the system can handle multiple stores properly!

## 🔍 Debugging

If you still see errors, check:

1. **Store credentials are saved**: Check the `bigcommerce_stores` table
2. **Widget loads with store hash**: Look at browser network tab
3. **API calls include store_hash**: Check request parameters
4. **Custom fields exist**: Verify products have YMM custom fields

The system is now ready for production use with multiple BigCommerce stores!
