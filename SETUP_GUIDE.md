# YMM Filter Setup Guide for BigCommerce Bumpers

## Quick Start

1. **Set up your database:**
   ```bash
   # Create .env file
   cp .env.example .env
   
   # Generate app key
   php artisan key:generate
   
   # Run migrations
   php artisan migrate
   
   # Seed sample data
   php artisan db:seed
   ```

2. **For BigCommerce Custom Fields (Recommended):**
   - Go to your BigCommerce admin → Products → Custom Fields
   - Create these fields for all bumper products:
     - `ymm_year_start` (Number)
     - `ymm_year_end` (Number) 
     - `ymm_make` (Text)
     - `ymm_model` (Text)

3. **Access the filter:**
   - Navigate to `/ymm-filter` in your app
   - Use the dropdowns to filter compatible bumpers

## BigCommerce Custom Fields Approach (Easiest)

### Step 1: Add Custom Fields to Products
For each bumper product in BigCommerce:
1. Go to Products → [Product] → Custom Fields
2. Add:
   - Name: `ymm_year_start`, Value: `2015`
   - Name: `ymm_year_end`, Value: `2020`
   - Name: `ymm_make`, Value: `Ford`
   - Name: `ymm_model`, Value: `F-150`

### Step 2: Update the YMM Filter to use Custom Fields
Modify the API endpoints to search BigCommerce products by custom fields instead of the local database.

## Local Database Approach (More Control)

### Step 1: Add Vehicle Compatibility
```bash
# Add a new vehicle compatibility
POST /api/vehicles
{
    "year_start": 2015,
    "year_end": 2020,
    "make": "Ford",
    "model": "F-150"
}
```

### Step 2: Link Products to Vehicles
```bash
# Link a BigCommerce product to a vehicle
POST /api/vehicles/add-product
{
    "vehicle_id": 1,
    "bigcommerce_product_id": "123"
}
```

## Usage Examples

### Filter API Endpoints:
- `GET /api/ymm/makes` - Get all makes
- `GET /api/ymm/models?make=Ford` - Get models for Ford
- `GET /api/ymm/compatible-products?year=2018&make=Ford&model=F-150` - Get compatible bumpers

### Sample Data Structure:

**Vehicles:**
```json
[
    {
        "id": 1,
        "year_start": 2015,
        "year_end": 2020,
        "make": "Ford",
        "model": "F-150"
    }
]
```

**Product-Vehicle Relationships:**
```json
[
    {
        "bigcommerce_product_id": "123",
        "vehicle_id": 1
    }
]
```

## File Structure Created:

### Controllers:
- `YmmFilterController.php` - Main filter logic
- `VehicleManagementController.php` - Admin management

### Models:
- `Vehicle.php` - Vehicle compatibility data
- `ProductVehicle.php` - Product-vehicle relationships

### React Components:
- `YmmFilter.jsx` - Customer-facing filter interface
- `VehicleManagement.jsx` - Admin interface (to be created)

### Routes:
- `/ymm-filter` - Customer filter page
- `/api/ymm/*` - Filter API endpoints
- `/api/vehicles/*` - Management API endpoints

## Recommended Workflow:

1. **For simple setup**: Use BigCommerce Custom Fields
2. **For advanced features**: Use local database with API sync
3. **Start with**: Custom fields, migrate to database later if needed

## Next Steps:
1. Choose your preferred approach (Custom Fields vs Database)
2. Set up your BigCommerce product data
3. Test the filter with real products
4. Customize the styling to match your store theme
