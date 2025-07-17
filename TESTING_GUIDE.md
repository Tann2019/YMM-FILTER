# Testing Your YMM Filter with Custom Fields

## Quick Test Setup

### Step 1: Add Custom Fields to Test Products

In your BigCommerce admin panel, edit a few products and add these custom fields:

**Product 1 - Front Bumper:**
- `ymm_year_start`: `2005`
- `ymm_year_end`: `2007`
- `ymm_make`: `Dodge`
- `ymm_model`: `Ram`

**Product 2 - Grille Guard:**
- `ymm_year_start`: `2005`
- `ymm_year_end`: `2010`
- `ymm_make`: `Dodge`
- `ymm_model`: `Ram`

**Product 3 - Ford F-150 Headlights:**
- `ymm_year_start`: `2015`
- `ymm_year_end`: `2020`
- `ymm_make`: `Ford`
- `ymm_model`: `F-150`

### Step 2: Test the API Endpoints

Test these URLs in your browser (replace with your ngrok URL):

**Get all available makes:**
```
https://392488a90049.ngrok-free.app/api/bigcommerce/makes
```

**Get models for Dodge:**
```
https://392488a90049.ngrok-free.app/api/bigcommerce/models?make=Dodge
```

**Get years for Dodge Ram:**
```
https://392488a90049.ngrok-free.app/api/bigcommerce/years?make=Dodge&model=Ram
```

**Find compatible products for 2005 Dodge Ram:**
```
https://392488a90049.ngrok-free.app/api/bigcommerce/compatible-products?year=2005&make=Dodge&model=Ram
```

### Step 3: Test the Widget

Visit the widget directly:
```
https://392488a90049.ngrok-free.app/ymm-widget
```

You should see:
1. A dropdown with "Dodge" and "Ford" (and any other makes you've added)
2. When you select "Dodge", the models dropdown should populate with "Ram"
3. When you select "Ram", the years dropdown should show 2005-2010
4. When you select 2005 and click "Find Compatible Parts", you should see the products that match

### Step 4: Add to Your BigCommerce Theme

1. **Go to Storefront > Themes > Advanced > Edit Theme Files**
2. **Open `templates/pages/category.html`**
3. **Add this code above your product grid:**

```html
<!-- YMM Vehicle Filter Widget -->
<div id="ymm-filter-container">
    <iframe 
        src="https://392488a90049.ngrok-free.app/ymm-widget" 
        width="100%" 
        height="400" 
        frameborder="0"
        style="border: none; margin: 20px 0;">
    </iframe>
</div>
```

## Expected Behavior

### For 2005 Dodge Ram Search:
- ✅ Shows: Front Bumper (fits 2005-2007)
- ✅ Shows: Grille Guard (fits 2005-2010) 
- ❌ Hides: Ford F-150 Headlights (different vehicle)

### For 2008 Dodge Ram Search:
- ❌ Hides: Front Bumper (only fits up to 2007)
- ✅ Shows: Grille Guard (fits 2005-2010)
- ❌ Hides: Ford F-150 Headlights (different vehicle)

### For 2017 Ford F-150 Search:
- ❌ Hides: Front Bumper (Dodge only)
- ❌ Hides: Grille Guard (Dodge only)
- ✅ Shows: Ford F-150 Headlights (fits 2015-2020)

## Custom Fields Format

Your products need these exact custom field names:

| Field Name | Purpose | Example Value |
|------------|---------|---------------|
| `ymm_year_start` | First year of compatibility | `2005` |
| `ymm_year_end` | Last year of compatibility | `2007` |
| `ymm_make` | Vehicle manufacturer | `Dodge` |
| `ymm_model` | Vehicle model | `Ram` |

## Troubleshooting

**No makes showing up?**
- Check that products have `ymm_make` custom fields
- Verify your BigCommerce app has API access

**Products not filtering?**
- Ensure exact spelling: "Dodge" not "DODGE" or "dodge"
- Check year ranges: 2005 must be between year_start and year_end
- Verify custom field names are exactly: `ymm_year_start`, `ymm_year_end`, `ymm_make`, `ymm_model`

**Widget not loading?**
- Check your ngrok URL is correct and running
- Verify the route is accessible: `/ymm-widget`
- Check browser console for JavaScript errors

This setup gives you a complete vehicle accessories filtering system using BigCommerce custom fields!
