# YMM Widget Scope Solution

## Problem
The BigCommerce Page Builder widget templates API requires specific `content` scopes that may not be available with the current API credentials. When attempting to create widget templates, a 403 error occurs: "You don't have a required scope to access the endpoint".

## Solution Implemented
We've implemented a fallback solution that creates a script-based widget when Page Builder widget creation fails due to scope limitations.

### How It Works

1. **Primary Attempt**: Try to create a Page Builder widget template using `/content/widget-templates`
2. **Fallback Method**: If the API call fails with a 403 error, automatically create a script-based widget using `/content/scripts`

### Script-Based Widget Features

The script-based widget provides the same functionality as a Page Builder widget:

- **Year/Make/Model Selection**: Progressive dropdown menus
- **API Integration**: Connects to your YMM API endpoints
- **Product Search**: Finds compatible products based on vehicle selection
- **Responsive Design**: Works on all device sizes
- **Easy Integration**: Automatically appears on storefront pages

### Widget Code Structure

The script creates a self-contained widget with:

```javascript
// Widget container with styled form
<div id='ymm-widget-container'>
  // Year/Make/Model dropdowns
  // Search button
  // Results display area
</div>

// JavaScript functionality
- loadYears() - Fetches available years
- loadMakes(year) - Fetches makes for selected year
- loadModels(year, make) - Fetches models for selection
- searchProducts() - Finds compatible products
```

### API Endpoints Used

The widget connects to these endpoints:
- `GET /api/ymm/{storeHash}/years` - Available years
- `GET /api/ymm/{storeHash}/makes?year={year}` - Makes for year
- `GET /api/ymm/{storeHash}/models?year={year}&make={make}` - Models
- `GET /api/ymm/{storeHash}/search?year={year}&make={make}&model={model}` - Compatible products

### Installation Process

1. Navigate to your app dashboard
2. Click "Create Page Builder Widget"
3. If Page Builder creation fails (due to scopes), the system automatically creates a script-based widget
4. Success message indicates which type was created

### Upgrading to Page Builder Widget

To use the full Page Builder widget template functionality:

1. **Update API Scopes**: Add `content` scope to your BigCommerce API credentials
2. **Regenerate Token**: Create new API token with content management permissions
3. **Retry Creation**: The system will automatically use Page Builder templates when scopes are available

### Required Scopes for Page Builder

For full Page Builder widget functionality, ensure your BigCommerce app has these scopes:
- `store_content_write` - Required for widget template creation
- `store_content_read` - Required for widget template management

### Testing the Widget

1. **Admin Panel**: Check that the script appears in Content > Scripts
2. **Storefront**: Visit any page to see the YMM filter widget
3. **Functionality**: Test year/make/model selection and product search

### Troubleshooting

**Widget not appearing:**
- Check that the script was created successfully in BigCommerce admin
- Verify your storefront theme supports script injection
- Check browser console for JavaScript errors

**API errors:**
- Ensure YMM API endpoints are accessible
- Check CORS settings for your domain
- Verify vehicle data exists in your database

**Search not working:**
- Confirm products have YMM compatibility data
- Check that vehicle records exist for the selected combination
- Verify BigCommerce product API access

### Future Enhancements

When proper scopes are available, the Page Builder widget offers additional benefits:
- **Drag-and-drop placement** on any page
- **Visual customization** through Page Builder interface
- **Theme configuration** options
- **Better integration** with store themes

This solution ensures YMM functionality is available regardless of current API scope limitations while providing a clear upgrade path for enhanced features.
