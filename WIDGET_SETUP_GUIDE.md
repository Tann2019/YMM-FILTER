# YMM Filter Drag-and-Drop Widget Setup Guide

## Overview

The YMM Filter now includes a **drag-and-drop widget** that can be easily added to any page using BigCommerce's Page Builder. This widget allows customers to search for products based on their vehicle's Year, Make, and Model.

## Widget Features

✅ **Drag-and-Drop Interface** - Add to any page via BigCommerce Page Builder
✅ **Responsive Design** - Works on all device sizes  
✅ **Customizable Appearance** - Multiple themes and color options
✅ **Real-time Search** - Instant product filtering
✅ **Configurable Settings** - Customize labels, colors, and behavior
✅ **SEO Friendly** - Improves product discoverability

## Setup Instructions

### Step 1: Create the Widget Template

1. Go to your YMM Filter Dashboard
2. Click the **"Create Page Builder Widget"** button
3. Wait for the success message confirming widget creation

### Step 2: Add Widget to Your Store

1. In your BigCommerce admin, go to **Storefront** > **Web Pages**
2. Edit any page OR create a new page
3. Click **"Edit in Page Builder"**
4. In the Page Builder, look for **"YMM Vehicle Filter"** in the widgets panel
5. **Drag and drop** the widget onto your page
6. Configure the widget settings (see configuration options below)
7. **Save** the page

### Step 3: Configure Widget Settings

When you add the widget to a page, you can customize:

#### Content Settings
- **Widget Title** - The heading displayed above the filter
- **Search Button Text** - Text on the search button (default: "Search Compatible Products")
- **Show Vehicle Images** - Display product images in results
- **Placeholder Text** - Customize dropdown placeholders

#### Style Settings
- **Theme** - Choose from Default, Modern, or Compact layouts
- **Primary Color** - Main accent color for the widget
- **Button Color** - Color of the search button

## Widget Themes

### Default Theme
- Clean, professional appearance
- Standard spacing and typography
- Suitable for most store designs

### Modern Theme
- Enhanced visual styling with shadows
- Rounded corners and modern aesthetics
- Perfect for contemporary store designs

### Compact Theme
- Minimal spacing for tighter layouts
- Smaller form elements
- Ideal for sidebar placements

## Best Practices

### Page Placement
- **Product Category Pages** - Help customers find compatible products
- **Homepage** - Primary search tool for vehicle-specific products  
- **Landing Pages** - Dedicated vehicle compatibility pages
- **Blog Posts** - Technical articles about specific vehicles

### Configuration Tips
- Use clear, descriptive widget titles
- Keep button text action-oriented ("Find My Parts", "Search Products")
- Choose colors that match your store's branding
- Test on mobile devices to ensure good user experience

## Troubleshooting

### Widget Not Appearing
1. Ensure vehicles are added to your YMM database
2. Verify products have compatibility assignments
3. Check that the widget template was created successfully

### Search Returns No Results
1. Confirm products are assigned to the searched vehicle
2. Verify vehicle data is correctly formatted
3. Check that products are visible in your store

### Styling Issues
1. Try different themes in widget settings
2. Adjust colors to match your store design
3. Test responsive behavior on different screen sizes

## Technical Information

### API Endpoints
The widget uses these public API endpoints:
- `/api/ymm/{storeHash}/years` - Available years
- `/api/ymm/{storeHash}/makes` - Available makes for a year
- `/api/ymm/{storeHash}/models` - Available models for year/make
- `/api/ymm/{storeHash}/search` - Compatible products search

### Widget Template
The widget is registered as a BigCommerce Page Builder template that includes:
- Handlebars template for dynamic content
- CSS styling with theme support
- JavaScript functionality for API interactions
- Configuration schema for settings panel

## Support

If you encounter issues:
1. Check the [Troubleshooting](#troubleshooting) section above
2. Verify your YMM data is properly configured
3. Test with known compatible vehicles and products
4. Contact support with specific error messages or behavior

## Advanced Customization

For developers who want to customize the widget further:
- Widget template source is in `app/Http/Controllers/WidgetController.php`
- API endpoints are in `app/Http/Controllers/Api/YmmApiController.php`
- Frontend template uses Handlebars syntax with BigCommerce variables
- Styling can be modified through theme options or custom CSS

---

*Last updated: January 2025*
