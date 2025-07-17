# 🛠️ BigCommerce YMM Filter Setup Guide

## Choose Your Approach

### ✅ **Option 1: BigCommerce Custom Fields (RECOMMENDED)**
- ✅ Simple to implement
- ✅ Easy for clients to manage
- ✅ No database sync needed
- ✅ Bulk import via CSV

### 🔧 **Option 2: Local Database**
- 🔧 More control and features
- 🔧 Better for complex logic
- 🔧 Requires API sync

---

## 🚀 **Option 1: BigCommerce Custom Fields Setup**

### Step 1: Configure BigCommerce Products

#### **Method A: Manual Entry (Small inventory)**
1. Go to **BigCommerce Admin** → **Products** → **View**
2. Select a bumper product → **Edit**
3. Scroll to **Custom Fields** section
4. Add these 4 custom fields:

```
Field Name: ymm_make
Field Value: Ford

Field Name: ymm_model  
Field Value: F-150

Field Name: ymm_year_start
Field Value: 2015

Field Name: ymm_year_end
Field Value: 2020
```

#### **Method B: CSV Bulk Import (Large inventory)**
1. **Export your products**: Products → Export
2. **Create custom fields CSV** using the template I created:
   - File: `bigcommerce_custom_fields_template.csv`
   - Replace product IDs with your actual IDs
3. **Import custom fields**: Products → Import

### Step 2: Test Your Setup

1. **Visit your BigCommerce YMM Filter:**
   ```
   https://your-app-url.com/bc-ymm-filter
   ```

2. **Check API endpoints:**
   - `/api/bc-ymm/makes` - Should return your makes
   - `/api/bc-ymm/models?make=Ford` - Should return models for Ford
   - `/api/bc-ymm/compatible-products?year=2018&make=Ford&model=F-150`

### Step 3: Troubleshooting

If no data appears:

1. **Check custom fields exist** in BigCommerce admin
2. **Verify field names are exact:**
   - `ymm_make` (not "Make" or "ymm-make")
   - `ymm_model`
   - `ymm_year_start` 
   - `ymm_year_end`
3. **Check API permissions** in your BigCommerce app settings

---

## 🔧 **Option 2: Local Database Setup**

### Step 1: Run Migrations
```bash
php artisan migrate
php artisan db:seed
```

### Step 2: Add Vehicle Compatibility
```bash
# Use the local database filter
https://your-app-url.com/ymm-filter

# Manage vehicles
https://your-app-url.com/vehicle-management
```

### Step 3: Link Products to Vehicles
```bash
POST /api/vehicles/add-product
{
    "vehicle_id": 1,
    "bigcommerce_product_id": "123"
}
```

---

## 📊 **Sample Data for Testing**

### Custom Fields Example:
```csv
product_id,custom_field_name,custom_field_value
123,ymm_year_start,2015
123,ymm_year_end,2020
123,ymm_make,Ford
123,ymm_model,F-150
124,ymm_year_start,2016
124,ymm_year_end,2023
124,ymm_make,Toyota
124,ymm_model,Tacoma
```

### Common Vehicle Data:
- **Ford F-150**: 2015-2020, 2021-2023
- **Toyota Tacoma**: 2016-2023
- **Chevrolet Silverado 1500**: 2014-2018, 2019-2023
- **Ram 1500**: 2013-2018, 2019-2023

---

## 🎯 **Quick Start (5 Minutes)**

1. **Add custom fields to 1-2 test products** in BigCommerce
2. **Visit** `/bc-ymm-filter` in your Laravel app
3. **Test the filter** - you should see your makes/models
4. **Bulk import the rest** using CSV once confirmed working

---

## 🔍 **API Endpoints**

### BigCommerce Custom Fields:
- `GET /api/bc-ymm/makes`
- `GET /api/bc-ymm/models?make=Ford`
- `GET /api/bc-ymm/year-ranges?make=Ford&model=F-150`
- `GET /api/bc-ymm/compatible-products?year=2018&make=Ford&model=F-150`

### Local Database:
- `GET /api/ymm/makes`
- `GET /api/ymm/models?make=Ford`
- `GET /api/ymm/year-ranges?make=Ford&model=F-150`
- `GET /api/ymm/compatible-products?year=2018&make=Ford&model=F-150`

---

## ⚡ **Performance Tips**

1. **Limit BigCommerce API calls** - cache results when possible
2. **Use product pagination** for large inventories
3. **Consider rate limiting** - BigCommerce has API limits
4. **Index custom fields** in BigCommerce for faster searches

---

## 🆘 **Common Issues**

### "No makes found"
- Check custom fields exist in BigCommerce
- Verify field names are exact: `ymm_make`, `ymm_model`, etc.
- Check BigCommerce API permissions

### "API errors"
- Verify BigCommerce app credentials in `.env`
- Check store hash is correct
- Ensure API scopes include product read permissions

### "No compatible products"
- Verify year is within year_start and year_end range
- Check make/model values match exactly (case-sensitive)
- Confirm products have all 4 required custom fields

---

## 📞 **Next Steps**

1. **Choose your approach** (Custom Fields recommended)
2. **Set up test data** (1-2 products)
3. **Test the filter**
4. **Bulk import remaining products**
5. **Customize styling** to match your store theme
