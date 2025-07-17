# How to Add the YMM Filter Widget to Your BigCommerce Website

## Overview
This guide shows you how to add the Vehicle Compatibility Filter to your BigCommerce storefront. The widget works with custom fields on your products to filter and show only compatible accessories for the selected vehicle.

## Prerequisites
1. Your BigCommerce YMM Filter app must be installed and working
2. Products must have YMM custom fields set up (see Custom Fields Setup section)
3. Access to your BigCommerce theme files

## Method 1: Simple Widget Integration (Recommended)

### Step 1: Add the Widget to Your Theme

1. **Log into your BigCommerce admin panel**
2. **Go to Storefront > Themes**
3. **Click "Advanced" > "Edit Theme Files"** on your active theme
4. **Choose where to add the widget:**

   **Option A: Category Pages (for product listing pages)**
   - Navigate to `templates/pages/category.html`
   - Add this code in the `<main class="page-content category-page">` section, right after the category description but before the product listing:
   
   ```handlebars
   <main class="page-content category-page" id="product-listing-container">
       {{{category.description}}}
       {{{snippet 'categories'}}}
       
       <!-- YMM Vehicle Filter Widget -->
       <div id="ymm-filter-container" style="margin: 20px 0;">
           <!-- Widget will be loaded here -->
       </div>
       
       {{#if category.products}}
           {{> components/category/product-listing}}
       {{else}}
           <p>{{lang 'categories.no_products'}}</p>
       {{/if}}
       {{{region name="category_below_content"}}}
   </main>
   
   <script>
   // Load the YMM filter widget with store identification
   function loadYmmWidget() {
       const container = document.getElementById('ymm-filter-container');
       if (!container) {
           console.error('YMM filter container not found. Make sure the div with id="ymm-filter-container" exists on the page.');
           return;
       }
       
       const storeHash = window.location.hostname.includes('.mybigcommerce.com') 
           ? window.location.hostname.split('.')[0] 
           : 'YOUR_STORE_HASH'; // Replace with your actual store hash
       
       fetch(`https://392488a90049.ngrok-free.app/ymm-widget?store_hash=${storeHash}`)
           .then(response => {
               if (!response.ok) {
                   throw new Error(`HTTP error! status: ${response.status}`);
               }
               return response.text();
           })
           .then(html => {
               container.innerHTML = html;
           })
           .catch(error => {
               console.error('Error loading YMM filter:', error);
               container.innerHTML = '<p style="color: red;">Failed to load vehicle filter. Please refresh the page.</p>';
           });
   }
   
   // Wait for DOM to be ready
   if (document.readyState === 'loading') {
       document.addEventListener('DOMContentLoaded', loadYmmWidget);
   } else {
       loadYmmWidget();
   }
   </script>
   ```

   **Option B: Product Pages (for individual product compatibility)**
   - Navigate to `templates/pages/product.html`
   - Add this code in the product details section:
   
   ```html
   <!-- Product Compatibility Check -->
   <div class="product-compatibility">
       <h4>Check Vehicle Compatibility</h4>
       <div id="ymm-compatibility-check">
           <!-- Compatibility widget will load here -->
       </div>
   </div>
   
   <script>
   // Load compatibility checker for this specific product
   function loadProductCompatibility() {
       const container = document.getElementById('ymm-compatibility-check');
       if (!container) {
           console.error('Product compatibility container not found');
           return;
       }
       
       const productId = '{{product.id}}';
       const storeHash = window.location.hostname.includes('.mybigcommerce.com') 
           ? window.location.hostname.split('.')[0] 
           : 'YOUR_STORE_HASH';
       
       fetch(`https://392488a90049.ngrok-free.app/product-compatibility/${productId}?store_hash=${storeHash}`)
           .then(response => {
               if (!response.ok) {
                   throw new Error(`HTTP error! status: ${response.status}`);
               }
               return response.text();
           })
           .then(html => {
               container.innerHTML = html;
           })
           .catch(error => {
               console.error('Error loading product compatibility:', error);
               container.innerHTML = '<p style="color: red;">Failed to load compatibility check.</p>';
           });
   }
   
   // Wait for DOM ready
   if (document.readyState === 'loading') {
       document.addEventListener('DOMContentLoaded', loadProductCompatibility);
   } else {
       loadProductCompatibility();
   }
   </script>
   ```

### Step 2: Update Your App URL

Replace `{{YOUR_APP_URL}}` in the code above with your actual app URL:
- If using ngrok: `https://392488a90049.ngrok-free.app`
- If deployed: Your production domain

### Step 3: Style the Widget (Optional)

Add custom CSS to match your theme. In your theme's CSS file (usually `assets/theme.css`):

```css
/* YMM Filter Widget Styling */
.ymm-filter-widget {
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.ymm-filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.ymm-btn-primary {
    background-color: var(--primary-color, #007bff);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}
```

## Method 2: Advanced Integration via Page Builder

### For Themes with Page Builder Support:

1. **Go to Storefront > Web Pages**
2. **Create a new page or edit existing category pages**
3. **Use the HTML widget** in the page builder
4. **Add this HTML code:**

```html
<div id="ymm-vehicle-filter"></div>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script>
// Load the YMM filter widget from your app with error handling
function loadYmmWidget() {
    const container = document.getElementById('ymm-vehicle-filter');
    if (!container) {
        console.error('YMM vehicle filter container not found');
        return;
    }
    
    const storeHash = window.location.hostname.includes('.mybigcommerce.com') 
        ? window.location.hostname.split('.')[0] 
        : 'YOUR_STORE_HASH'; // Replace with your actual store hash
    
    fetch(`https://392488a90049.ngrok-free.app/ymm-widget?store_hash=${storeHash}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading YMM filter:', error);
            container.innerHTML = '<p style="color: red;">Failed to load vehicle filter.</p>';
        });
}

// Wait for DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadYmmWidget);
} else {
    loadYmmWidget();
}
</script>
```

## Method 3: Global Header/Footer Integration

### To add the widget site-wide:

1. **Navigate to `templates/layout/base.html`**
2. **Add the widget container in the header or before the main content:**

```html
<!-- Add this in the <head> section -->
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>

<!-- Add this where you want the filter to appear -->
<div id="global-ymm-filter" style="display:none;">
    <!-- Filter widget goes here -->
</div>

<script>
function initGlobalYmmFilter() {
    // Only show the filter on product listing pages
    if (window.location.pathname.includes('/category/') || 
        window.location.pathname.includes('/products/')) {
        
        const filterContainer = document.getElementById('global-ymm-filter');
        if (!filterContainer) {
            console.error('Global YMM filter container not found');
            return;
        }
        
        filterContainer.style.display = 'block';
        
        const storeHash = window.location.hostname.includes('.mybigcommerce.com') 
            ? window.location.hostname.split('.')[0] 
            : 'YOUR_STORE_HASH'; // Replace with your actual store hash
        
        // Load the widget
        fetch(`https://392488a90049.ngrok-free.app/ymm-widget?store_hash=${storeHash}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(html => {
                filterContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading YMM filter:', error);
                filterContainer.innerHTML = '<p style="color: red;">Failed to load vehicle filter.</p>';
            });
    }
}

// Wait for DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGlobalYmmFilter);
} else {
    initGlobalYmmFilter();
}
</script>
```

## Custom Fields Setup for Products

### Setting Up Product Custom Fields

For each product that should be filtered, add these custom fields in BigCommerce:

1. **Go to Products > View/Edit a Product**
2. **Scroll to "Custom Fields" section**
3. **Add these fields:**

| Field Name | Field Value | Example |
|------------|-------------|---------|
| `ymm_year_start` | Starting year | `2015` |
| `ymm_year_end` | Ending year | `2020` |
| `ymm_make` | Vehicle make | `Ford` |
| `ymm_model` | Vehicle model | `F-150` |

### Bulk Import Custom Fields

You can bulk import custom fields using the CSV template provided:

1. **Download the CSV template:** `bigcommerce_custom_fields_template.csv`
2. **Fill in your product data:**
   ```csv
   product_id,custom_field_name,custom_field_value
   123,ymm_year_start,2015
   123,ymm_year_end,2020
   123,ymm_make,Ford
   123,ymm_model,F-150
   ```
3. **Use BigCommerce's bulk import tool** or the API to import

## Widget Configuration Options

### Customizing the Widget Appearance

You can customize the widget by modifying the CSS classes:

```css
/* Widget container */
.ymm-filter-widget { }

/* Form layout */
.ymm-filter-form { }

/* Individual form groups */
.ymm-form-group { }

/* Dropdown selects */
.ymm-form-group select { }

/* Buttons */
.ymm-btn { }
.ymm-btn-primary { }
.ymm-btn-secondary { }

/* Results display */
.ymm-results { }
.ymm-results-count { }
```

### JavaScript Configuration

You can configure the widget behavior:

```javascript
// Configure API endpoint
window.ymmConfig = {
    apiBaseUrl: 'https://392488a90049.ngrok-free.app/api',
    autoFilter: true,  // Automatically filter products on page
    showProductCount: true,  // Show count of compatible products
    hideIncompatible: true   // Hide incompatible products
};
```

## Testing the Integration

### Test Steps:

1. **Add custom fields to a few test products**
2. **Visit a category page with the widget**
3. **Select a vehicle (Year/Make/Model)**
4. **Click "Find Compatible Parts"**
5. **Verify that only compatible products are shown**

### Common Issues and Solutions:

**"Cannot set properties of null (setting 'innerHTML')" Error:**
- This means the widget container div doesn't exist when the script runs
- Make sure you have `<div id="ymm-filter-container"></div>` in your template
- Ensure the script runs after the DOM is loaded (the updated examples include DOM ready checks)
- Check that the div ID matches exactly what the JavaScript is looking for

**Widget doesn't load:**
- Check the app URL in your integration code
- Verify your app is running and accessible
- Check browser console for JavaScript errors
- Make sure ngrok tunnel is active if using the development URL

**Products don't filter:**
- Ensure products have the correct custom fields
- Check that custom field names match exactly: `ymm_year_start`, `ymm_year_end`, `ymm_make`, `ymm_model`
- Verify the API endpoints are working

**Styling issues:**
- Check for CSS conflicts with your theme
- Add `!important` to critical styles if needed
- Test on different screen sizes

**Network/CORS issues:**
- If using ngrok, make sure to include the `ngrok-skip-browser-warning` header
- Check that your BigCommerce store allows external script loading
- Verify HTTPS is being used (BigCommerce requires HTTPS)

## Advanced Features

### Product-Specific Compatibility Display

Show compatibility on individual product pages:

```html
<!-- On product.html template -->
<div class="product-compatibility-info">
    <h4>Vehicle Compatibility</h4>
    <div id="product-ymm-info" data-product-id="{{product.id}}">
        <!-- Will show: "Fits: 2015-2020 Ford F-150" -->
    </div>
</div>

<script>
// Load compatibility info for this product
function loadProductYmmInfo() {
    const container = document.getElementById('product-ymm-info');
    if (!container) {
        console.error('Product YMM info container not found');
        return;
    }
    
    const productId = '{{product.id}}';
    const storeHash = window.location.hostname.includes('.mybigcommerce.com') 
        ? window.location.hostname.split('.')[0] 
        : 'YOUR_STORE_HASH';
    
    fetch(`https://392488a90049.ngrok-free.app/product-compatibility/${productId}?store_hash=${storeHash}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.compatibility) {
                container.innerHTML = `<strong>Fits:</strong> ${data.compatibility}`;
            } else {
                container.innerHTML = '<em>Compatibility information not available</em>';
            }
        })
        .catch(error => {
            console.error('Error loading product compatibility info:', error);
            container.innerHTML = '<em>Failed to load compatibility information</em>';
        });
}

// Wait for DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadProductYmmInfo);
} else {
    loadProductYmmInfo();
}
</script>
```

### Search Results Integration

Filter search results by vehicle:

```html
<!-- On search.html template -->
<div class="search-ymm-filter">
    <!-- YMM filter widget -->
</div>
```

## Quick Troubleshooting

### Debug the Widget Integration

If you're having issues, follow these steps:

1. **Test the widget directly:**
   - Visit: `https://392488a90049.ngrok-free.app/widget-test`
   - This page will show you if the widget loads correctly

2. **Check if the widget endpoint works:**
   - Visit: `https://392488a90049.ngrok-free.app/ymm-widget`
   - You should see the widget HTML code

3. **Verify your integration:**
   - Open browser developer tools (F12)
   - Go to Console tab
   - Look for error messages when the widget loads

### Common Error Messages and Fixes:

**"Cannot set properties of null (setting 'innerHTML')"**
- **Cause:** The div container doesn't exist when JavaScript runs
- **Fix:** Make sure your HTML includes `<div id="ymm-filter-container"></div>`
- **Fix:** Use the updated JavaScript code that waits for DOM ready

**"Failed to fetch" or "Network Error" or "HTTP error! status: 404"**
- **Cause:** CORS issues, app not accessible, or wrong endpoint URL
- **Fix:** Check if https://392488a90049.ngrok-free.app is accessible
- **Fix:** Make sure ngrok tunnel is running
- **Fix:** Verify you're using `/ymm-widget` endpoint, not `/api/widget/ymm-filter`
- **Fix:** Make sure the URL doesn't have `{{YOUR_APP_URL}}` placeholders

**Widget shows but doesn't function**
- **Cause:** Vue.js not loaded or JavaScript errors
- **Fix:** Include Vue.js script: `<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>`
- **Fix:** Check browser console for JavaScript errors

## Support and Troubleshooting

If you need help with the integration:

1. **Check the browser console** for JavaScript errors
2. **Verify API endpoints** are responding correctly
3. **Test with sample data** first
4. **Contact support** with specific error messages

### Important: Correct Endpoints to Use

Make sure you're using these **correct** endpoints:

- **Widget embed:** `https://392488a90049.ngrok-free.app/ymm-widget`
- **Product compatibility:** `https://392488a90049.ngrok-free.app/product-compatibility/{productId}`
- **Widget test page:** `https://392488a90049.ngrok-free.app/widget-test`

**DO NOT USE:** 
- ❌ `/api/widget/ymm-filter` (this endpoint doesn't exist)
- ❌ `{{YOUR_APP_URL}}` (replace this with the actual URL)

Remember to replace the ngrok URL with your production domain when you deploy the app!
