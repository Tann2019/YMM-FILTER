<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\BigCommerceStore;
use App\Models\Vehicle;
use App\Models\ProductVehicle;
use App\Services\BigCommerceService;
use Inertia\Inertia;

class YmmManagementController extends Controller
{
    protected $bigCommerceService;

    public function __construct(BigCommerceService $bigCommerceService)
    {
        $this->bigCommerceService = $bigCommerceService;
    }

    /**
     * Show vehicles management page
     */
    public function vehicles(Request $request, $storeHash)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)
            ->where('active', true)
            ->firstOrFail();

        $vehicles = Vehicle::where('store_hash', $storeHash)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('make', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('year_start', 'like', "%{$search}%")
                        ->orWhere('year_end', 'like', "%{$search}%");
                });
            })
            ->when($request->make, function ($query, $make) {
                $query->where('make', $make);
            })
            ->orderBy('make')
            ->orderBy('model')
            ->orderBy('year_start')
            ->paginate(50);

        $makes = Vehicle::getUniqueMakes($storeHash);

        return Inertia::render('App/VehicleManagement', [
            'store' => $store,
            'vehicles' => $vehicles,
            'makes' => $makes,
            'filters' => $request->only(['search', 'make'])
        ]);
    }

    /**
     * Show products management page with YMM compatibility
     */
    public function products(Request $request, $storeHash)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)
            ->where('active', true)
            ->firstOrFail();

        try {
            // Fetch products from BigCommerce API
            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 50), 250); // BigCommerce max is 250
            $search = $request->get('search');

            $apiParams = [
                'limit' => $limit,
                'page' => $page,
                'include' => 'custom_fields,images',
                'is_visible' => true
            ];

            if ($search) {
                $apiParams['keyword'] = $search;
            }

            $response = $this->bigCommerceService->makeApiCall($storeHash, "/catalog/products", 'GET', $apiParams);

            // Log for debugging
            logger('BigCommerce API Response:', [
                'meta' => $response['meta'] ?? 'No meta found',
                'apiParams' => $apiParams,
                'dataCount' => count($response['data'] ?? []),
                'fullResponse' => $response
            ]);

            // Use BigCommerce's actual pagination structure - don't override it
            $products = [
                'data' => $response['data'] ?? [],
                'meta' => $response['meta'] ?? []
            ];

            // Only add fallback pagination if BigCommerce didn't provide meta
            if (empty($products['meta'])) {
                $products['meta'] = [
                    'pagination' => [
                        'total' => count($response['data'] ?? []),
                        'count' => count($response['data'] ?? []),
                        'per_page' => $limit,
                        'current_page' => 1,
                        'total_pages' => 1,
                        'links' => []
                    ]
                ];
            }

            // Get YMM compatibility data for each product
            $productIds = collect($products['data'])->pluck('id')->toArray();
            $compatibilityData = ProductVehicle::whereIn('bigcommerce_product_id', $productIds)
                ->with('vehicle')
                ->get()
                ->groupBy('bigcommerce_product_id');

            // Enhance products with compatibility info
            foreach ($products['data'] as &$product) {
                $product['ymm_compatibility'] = $compatibilityData->get($product['id'], collect())->map(function ($pv) {
                    return [
                        'id' => $pv->id,
                        'make' => $pv->vehicle->make,
                        'model' => $pv->vehicle->model,
                        'year_start' => $pv->vehicle->year_start,
                        'year_end' => $pv->vehicle->year_end,
                        'submodel' => $pv->vehicle->submodel,
                        'engine' => $pv->vehicle->engine,
                    ];
                })->toArray();

                $product['ymm_count'] = count($product['ymm_compatibility']);
            }

            // Get available vehicles for adding compatibility
            $vehicles = Vehicle::where('store_hash', $storeHash)
                ->orderBy('make')
                ->orderBy('model')
                ->orderBy('year_start')
                ->get();

            return Inertia::render('App/ProductManagement', [
                'store' => $store,
                'products' => $products,
                'vehicles' => $vehicles,
                'filters' => [
                    'search' => $search,
                    'page' => $page,
                    'limit' => $limit
                ]
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to load products: ' . $e->getMessage()]);
        }
    }

    /**
     * Store a new vehicle
     */
    public function storeVehicle(Request $request, $storeHash)
    {
        $request->validate([
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year_start' => 'required|integer|min:1900|max:' . (date('Y') + 5),
            'year_end' => 'required|integer|min:1900|max:' . (date('Y') + 5),
            'trim' => 'nullable|string|max:100',
            'engine' => 'nullable|string|max:100',
        ]);

        // Verify store exists and is active
        BigCommerceStore::where('store_hash', $storeHash)
            ->where('active', true)
            ->firstOrFail();

        $vehicle = Vehicle::create([
            'store_hash' => $storeHash,
            'make' => $request->make,
            'model' => $request->model,
            'year_start' => $request->year_start,
            'year_end' => $request->year_end,
            'trim' => $request->trim,
            'engine' => $request->engine,
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Vehicle added successfully');
    }

    /**
     * Update vehicle
     */
    public function updateVehicle(Request $request, $storeHash, $vehicleId)
    {
        $request->validate([
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'year_start' => 'required|integer|min:1900|max:' . (date('Y') + 5),
            'year_end' => 'required|integer|min:1900|max:' . (date('Y') + 5),
            'trim' => 'nullable|string|max:100',
            'engine' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        $vehicle = Vehicle::where('store_hash', $storeHash)
            ->where('id', $vehicleId)
            ->firstOrFail();

        $vehicle->update($request->only([
            'make',
            'model',
            'year_start',
            'year_end',
            'trim',
            'engine',
            'is_active'
        ]));

        return redirect()->back()->with('success', 'Vehicle updated successfully');
    }

    /**
     * Delete vehicle
     */
    public function deleteVehicle($storeHash, $vehicleId)
    {
        $vehicle = Vehicle::where('store_hash', $storeHash)
            ->where('id', $vehicleId)
            ->firstOrFail();

        // Also delete associated product relationships
        ProductVehicle::where('vehicle_id', $vehicleId)->delete();

        $vehicle->delete();

        return redirect()->back()->with('success', 'Vehicle deleted successfully');
    }

    /**
     * Bulk import vehicles from CSV
     */
    public function importVehicles(Request $request, $storeHash)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        // Verify store exists
        BigCommerceStore::where('store_hash', $storeHash)
            ->where('active', true)
            ->firstOrFail();

        $file = $request->file('csv_file');
        $csvData = array_map('str_getcsv', file($file->path()));
        $header = array_shift($csvData);

        $imported = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            try {
                if (count($row) < 4) {
                    $errors[] = "Row " . ($index + 2) . ": Insufficient data";
                    continue;
                }

                $data = array_combine($header, $row);

                Vehicle::create([
                    'store_hash' => $storeHash,
                    'make' => $data['make'] ?? $row[0],
                    'model' => $data['model'] ?? $row[1],
                    'year_start' => (int)($data['year_start'] ?? $row[2]),
                    'year_end' => (int)($data['year_end'] ?? $row[3]),
                    'trim' => $data['trim'] ?? $row[4] ?? null,
                    'engine' => $data['engine'] ?? $row[5] ?? null,
                    'is_active' => true
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        $message = "Imported {$imported} vehicles.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " errors occurred.";
        }

        return redirect()->back()->with('success', $message)->with('import_errors', $errors);
    }

    /**
     * Export vehicles to CSV
     */
    public function exportVehicles($storeHash)
    {
        $vehicles = Vehicle::where('store_hash', $storeHash)->get();

        $csvData = "Make,Model,Year Start,Year End,Trim,Engine,Active\n";

        foreach ($vehicles as $vehicle) {
            $csvData .= implode(',', [
                $vehicle->make,
                $vehicle->model,
                $vehicle->year_start,
                $vehicle->year_end,
                $vehicle->trim ?? '',
                $vehicle->engine ?? '',
                $vehicle->is_active ? 'Yes' : 'No'
            ]) . "\n";
        }

        $filename = "vehicles_{$storeHash}_" . date('Y-m-d') . ".csv";

        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    /**
     * Get YMM data for AJAX requests
     */
    public function getYmmData(Request $request, $storeHash)
    {
        $data = [];

        if ($request->type === 'makes') {
            $data = Vehicle::getUniqueMakes($storeHash);
        } elseif ($request->type === 'models' && $request->make) {
            $data = Vehicle::getModelsForMake($request->make, $storeHash);
        } elseif ($request->type === 'years' && $request->make && $request->model) {
            $vehicles = Vehicle::where('store_hash', $storeHash)
                ->where('make', $request->make)
                ->where('model', $request->model)
                ->where('is_active', true)
                ->get();

            $years = [];
            foreach ($vehicles as $vehicle) {
                for ($year = $vehicle->year_start; $year <= $vehicle->year_end; $year++) {
                    $years[] = $year;
                }
            }
            $data = array_unique($years);
            sort($data);
        }

        return response()->json($data);
    }

    /**
     * Check vehicle compatibility for a product
     */
    public function checkCompatibility(Request $request, $storeHash)
    {
        $request->validate([
            'year' => 'required|integer',
            'make' => 'required|string',
            'model' => 'required|string',
            'product_id' => 'nullable|integer'
        ]);

        $compatible = Vehicle::findCompatible(
            $request->year,
            $request->make,
            $request->model,
            $storeHash
        );

        return response()->json([
            'compatible' => $compatible->count() > 0,
            'vehicles' => $compatible
        ]);
    }

    /**
     * Add YMM compatibility to a product
     */
    public function addProductCompatibility(Request $request, $storeHash)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'vehicle_ids' => 'required|array',
            'vehicle_ids.*' => 'exists:vehicles,id'
        ]);

        $store = BigCommerceStore::where('store_hash', $storeHash)->firstOrFail();

        // Verify vehicles belong to this store
        $vehicles = Vehicle::where('store_hash', $storeHash)
            ->whereIn('id', $request->vehicle_ids)
            ->get();

        if ($vehicles->count() !== count($request->vehicle_ids)) {
            return response()->json(['error' => 'Some vehicles not found'], 404);
        }

        // Add compatibility relationships
        foreach ($request->vehicle_ids as $vehicleId) {
            ProductVehicle::firstOrCreate([
                'bigcommerce_product_id' => $request->product_id,
                'vehicle_id' => $vehicleId
            ]);
        }

        return back()->with('success', 'Vehicle compatibility added successfully');
    }

    /**
     * Remove YMM compatibility from a product
     */
    public function removeProductCompatibility(Request $request, $storeHash)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'vehicle_id' => 'required|exists:vehicles,id'
        ]);

        ProductVehicle::where('bigcommerce_product_id', $request->product_id)
            ->where('vehicle_id', $request->vehicle_id)
            ->delete();

        return back()->with('success', 'Vehicle compatibility removed successfully');
    }

    /**
     * Export products with YMM compatibility data
     */
    public function exportProducts(Request $request, $storeHash)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)->firstOrFail();

        try {
            // Get all products from BigCommerce
            $allProducts = [];
            $page = 1;
            $limit = 250;

            do {
                $exportParams = [
                    'limit' => $limit,
                    'page' => $page,
                    'is_visible' => true
                ];

                $response = $this->bigCommerceService->makeApiCall(
                    $storeHash,
                    "/catalog/products",
                    'GET',
                    $exportParams
                );

                $allProducts = array_merge($allProducts, $response['data']);
                $page++;
            } while (count($response['data']) === $limit);

            // Get compatibility data
            $productIds = collect($allProducts)->pluck('id')->toArray();
            $compatibilityData = ProductVehicle::whereIn('bigcommerce_product_id', $productIds)
                ->with('vehicle')
                ->get()
                ->groupBy('bigcommerce_product_id');

            // Prepare CSV data
            $csvData = [];
            $csvData[] = [
                'Product ID',
                'Product Name',
                'SKU',
                'Price',
                'Category',
                'YMM Make',
                'YMM Model',
                'YMM Year Start',
                'YMM Year End',
                'YMM Submodel',
                'YMM Engine'
            ];

            foreach ($allProducts as $product) {
                $compatibility = $compatibilityData->get($product['id'], collect());

                if ($compatibility->isEmpty()) {
                    // Product with no compatibility data
                    $csvData[] = [
                        $product['id'],
                        $product['name'],
                        $product['sku'] ?? '',
                        $product['price'] ?? 0,
                        '', // We'll need to fetch categories separately if needed
                        '',
                        '',
                        '',
                        '',
                        '',
                        ''
                    ];
                } else {
                    // One row per compatibility
                    foreach ($compatibility as $comp) {
                        $csvData[] = [
                            $product['id'],
                            $product['name'],
                            $product['sku'] ?? '',
                            $product['price'] ?? 0,
                            '',
                            $comp->vehicle->make,
                            $comp->vehicle->model,
                            $comp->vehicle->year_start,
                            $comp->vehicle->year_end,
                            $comp->vehicle->submodel ?? '',
                            $comp->vehicle->engine ?? ''
                        ];
                    }
                }
            }

            // Generate CSV
            $filename = "products_ymm_export_{$storeHash}_" . date('Y-m-d_H-i-s') . ".csv";
            $handle = fopen('php://temp', 'r+');

            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }

            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Show settings page
     */
    public function settings($storeHash)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)
            ->where('active', true)
            ->firstOrFail();

        return Inertia::render('App/Settings', [
            'store' => $store
        ]);
    }

    /**
     * Update settings and apply them to existing widgets
     */
    public function updateSettings(Request $request, $storeHash)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)
            ->where('active', true)
            ->firstOrFail();

        try {
            // Validate the request
            $validated = $request->validate([
                'widget_title' => 'nullable|string|max:255',
                'widget_description' => 'nullable|string|max:500',
                'theme' => 'nullable|string|in:default,modern,compact',
                'show_images' => 'nullable|boolean',
                'results_per_page' => 'nullable|integer|min:1|max:50',
                'enable_search' => 'nullable|boolean'
            ]);

            // Update store settings (you might want to store these in a settings table)
            $store->update([
                'settings' => json_encode($validated)
            ]);

            // Try to update existing widget templates with new settings
            try {
                $templates = $this->bigCommerceService->getWidgetTemplates($storeHash);

                if (isset($templates['data']) && is_array($templates['data'])) {
                    foreach ($templates['data'] as $template) {
                        if (strpos($template['name'], 'YMM') !== false || strpos($template['name'], 'Vehicle Filter') !== false) {
                            // Update the widget template with new settings
                            $updatedTemplate = [
                                'name' => $validated['widget_title'] ?? 'YMM Vehicle Filter',
                                'template' => $this->generateUpdatedWidgetTemplate($validated),
                                'schema' => $this->generateUpdatedWidgetSchema($validated)
                            ];

                            $this->bigCommerceService->updateWidgetTemplate(
                                $storeHash,
                                $template['uuid'],
                                $updatedTemplate
                            );
                        }
                    }
                }
            } catch (\Exception $e) {
                // If widget template update fails, continue - settings are still saved
                Log::warning('Could not update widget templates with new settings', [
                    'error' => $e->getMessage(),
                    'store_hash' => $storeHash
                ]);
            }

            // Also update any script-based widgets
            $this->updateScriptWidgets($storeHash, $validated);

            return back()->with('success', 'Settings updated successfully! Changes will be reflected in your widgets.');
        } catch (\Exception $e) {
            Log::error('Settings update failed', [
                'error' => $e->getMessage(),
                'store_hash' => $storeHash,
                'request_data' => $request->all()
            ]);

            return back()->withErrors(['error' => 'Failed to update settings: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate updated widget template with new settings
     */
    private function generateUpdatedWidgetTemplate($settings)
    {
        $title = $settings['widget_title'] ?? 'Find Compatible Products';
        $description = $settings['widget_description'] ?? 'Select your vehicle to find compatible products';
        $theme = $settings['theme'] ?? 'default';

        return "
{{#if widget_title}}
    <h3 class=\"ymm-widget-title\">{{widget_title}}</h3>
{{else}}
    <h3 class=\"ymm-widget-title\">{$title}</h3>
{{/if}}

{{#if widget_description}}
    <p class=\"ymm-widget-description\">{{widget_description}}</p>
{{else}}
    <p class=\"ymm-widget-description\">{$description}</p>
{{/if}}

<div class=\"ymm-filter-widget\" data-theme=\"{{theme}}\" data-store-hash=\"{{@store.store_hash}}\">
    <div class=\"ymm-filter-form\">
        <div class=\"ymm-filter-row\">
            <div class=\"ymm-filter-field\">
                <label for=\"ymm-year\">Year</label>
                <select id=\"ymm-year\" class=\"ymm-select\">
                    <option value=\"\">{{placeholder_year}}</option>
                </select>
            </div>
        </div>
        
        <div class=\"ymm-filter-row\">
            <div class=\"ymm-filter-field\">
                <label for=\"ymm-make\">Make</label>
                <select id=\"ymm-make\" class=\"ymm-select\" disabled>
                    <option value=\"\">{{placeholder_make}}</option>
                </select>
            </div>
        </div>
        
        <div class=\"ymm-filter-row\">
            <div class=\"ymm-filter-field\">
                <label for=\"ymm-model\">Model</label>
                <select id=\"ymm-model\" class=\"ymm-select\" disabled>
                    <option value=\"\">{{placeholder_model}}</option>
                </select>
            </div>
        </div>
        
        <div class=\"ymm-filter-actions\">
            <button id=\"ymm-search-btn\" class=\"ymm-btn ymm-btn-primary\" disabled>
                {{search_button_text}}
            </button>
            <button id=\"ymm-clear-btn\" class=\"ymm-btn ymm-btn-secondary\">
                {{clear_button_text}}
            </button>
        </div>
    </div>
    
    <div id=\"ymm-results\" class=\"ymm-results\" style=\"display: none;\">
        <div class=\"ymm-loading\" style=\"display: none;\">
            <p>{{loading_text}}</p>
        </div>
        <div class=\"ymm-product-list\"></div>
    </div>
</div>

<style>
.ymm-filter-widget {
    max-width: 400px;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    font-family: Arial, sans-serif;
}

.ymm-widget-title {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.ymm-widget-description {
    margin-bottom: 20px;
    color: #666;
    font-size: 14px;
}

.ymm-filter-row {
    margin-bottom: 15px;
}

.ymm-filter-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.ymm-select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.ymm-select:disabled {
    background-color: #f5f5f5;
    color: #999;
}

.ymm-filter-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.ymm-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

.ymm-btn-primary {
    background: #007cba;
    color: white;
}

.ymm-btn-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.ymm-btn-secondary {
    background: #6c757d;
    color: white;
}

.ymm-results {
    margin-top: 20px;
    padding: 15px;
    background: white;
    border-radius: 4px;
}

.ymm-loading {
    text-align: center;
    padding: 20px;
}

.ymm-product-grid {
    display: grid;
    gap: 10px;
}

.ymm-product-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border: 1px solid #eee;
    border-radius: 4px;
}

.ymm-product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    margin-right: 10px;
    border-radius: 4px;
}

.ymm-product-info {
    flex: 1;
}

.ymm-product-name {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.ymm-product-price {
    margin: 0 0 5px 0;
    font-weight: bold;
    color: #007cba;
}

.ymm-product-link {
    color: #007cba;
    text-decoration: none;
    font-size: 14px;
}

.ymm-product-link:hover {
    text-decoration: underline;
}
</style>

<script>
(function() {
    const widget = document.querySelector(\".ymm-filter-widget\");
    const storeHash = widget.dataset.storeHash;
    const apiUrl = \"{{@store.secure_url}}\";
    
    let yearSelect = document.getElementById(\"ymm-year\");
    let makeSelect = document.getElementById(\"ymm-make\");
    let modelSelect = document.getElementById(\"ymm-model\");
    let searchBtn = document.getElementById(\"ymm-search-btn\");
    let clearBtn = document.getElementById(\"ymm-clear-btn\");
    let results = document.getElementById(\"ymm-results\");
    
    // Initialize the widget
    loadYears();
    
    // Event listeners
    yearSelect.addEventListener(\"change\", handleYearChange);
    makeSelect.addEventListener(\"change\", handleMakeChange);
    modelSelect.addEventListener(\"change\", handleModelChange);
    searchBtn.addEventListener(\"click\", handleSearch);
    clearBtn.addEventListener(\"click\", handleClear);
    
    function loadYears() {
        fetch(`" . config('app.url') . "/api/ymm/\${storeHash}/years`)
            .then(response => response.json())
            .then(years => {
                populateSelect(yearSelect, years);
            })
            .catch(error => console.error(\"Error loading years:\", error));
    }
    
    function handleYearChange() {
        const year = yearSelect.value;
        makeSelect.innerHTML = `<option value=\"\">{{placeholder_make}}</option>`;
        modelSelect.innerHTML = `<option value=\"\">{{placeholder_model}}</option>`;
        
        if (year) {
            fetch(`" . config('app.url') . "/api/ymm/\${storeHash}/makes?year=\${year}`)
                .then(response => response.json())
                .then(makes => {
                    populateSelect(makeSelect, makes);
                })
                .catch(error => console.error(\"Error loading makes:\", error));
        }
    }
    
    function handleMakeChange() {
        const year = yearSelect.value;
        const make = makeSelect.value;
        modelSelect.innerHTML = `<option value=\"\">{{placeholder_model}}</option>`;
        
        if (year && make) {
            fetch(`" . config('app.url') . "/api/ymm/\${storeHash}/models?year=\${year}&make=\${encodeURIComponent(make)}`)
                .then(response => response.json())
                .then(models => {
                    populateSelect(modelSelect, models);
                })
                .catch(error => console.error(\"Error loading models:\", error));
        }
    }
    
    function handleModelChange() {
        updateSearchButton();
    }
    
    function handleSearch() {
        const year = yearSelect.value;
        const make = makeSelect.value;
        const model = modelSelect.value;
        
        if (!year || !make || !model) {
            alert(\"Please select year, make, and model\");
            return;
        }
        
        // Show loading
        results.style.display = \"block\";
        results.querySelector(\".ymm-product-list\").innerHTML = \"\";
        results.querySelector(\".ymm-loading\").style.display = \"flex\";
        
        // Search for compatible products
        fetch(`" . config('app.url') . "/api/ymm/\${storeHash}/search?year=\${year}&make=\${encodeURIComponent(make)}&model=\${encodeURIComponent(model)}`)
            .then(response => response.json())
            .then(data => {
                results.querySelector(\".ymm-loading\").style.display = \"none\";
                displayResults(data.products || []);
            })
            .catch(error => {
                console.error(\"Error searching products:\", error);
                results.querySelector(\".ymm-loading\").style.display = \"none\";
                results.querySelector(\".ymm-product-list\").innerHTML = \"<p>Error loading products. Please try again.</p>\";
            });
    }
    
    function handleClear() {
        yearSelect.value = \"\";
        makeSelect.innerHTML = `<option value=\"\">{{placeholder_make}}</option>`;
        modelSelect.innerHTML = `<option value=\"\">{{placeholder_model}}</option>`;
        results.style.display = \"none\";
        updateSearchButton();
    }
    
    function populateSelect(select, options) {
        options.forEach(option => {
            const optionElement = document.createElement(\"option\");
            optionElement.value = option;
            optionElement.textContent = option;
            select.appendChild(optionElement);
        });
    }
    
    function updateSearchButton() {
        const year = yearSelect.value;
        const make = makeSelect.value;
        const model = modelSelect.value;
        
        searchBtn.disabled = !year || !make || !model;
    }
    
    function displayResults(products) {
        const productList = results.querySelector(\".ymm-product-list\");
        
        if (products.length === 0) {
            productList.innerHTML = \"<p>No compatible products found for your vehicle.</p>\";
            return;
        }
        
        const html = products.map(product => `
            <div class=\"ymm-product-item\">
                \${product.images && product.images[0] ? 
                    `<img src=\"\${product.images[0].url_thumbnail}\" alt=\"\${product.name}\" class=\"ymm-product-image\">` 
                    : \"\"
                }
                <div class=\"ymm-product-info\">
                    <h4 class=\"ymm-product-name\">\${product.name}</h4>
                    <p class=\"ymm-product-price\">$\${product.calculated_price}</p>
                    <a href=\"\${product.custom_url?.url || \"#\"}\" class=\"ymm-product-link\">View Product</a>
                </div>
            </div>
        `).join(\"\");
        
        productList.innerHTML = `
            <h4>Compatible Products (\${products.length})</h4>
            <div class=\"ymm-product-grid\">\${html}</div>
        `;
    }
})();
</script>
        ";
    }

    /**
     * Generate updated widget schema with new settings
     */
    private function generateUpdatedWidgetSchema($settings)
    {
        return [
            [
                'type' => 'tab',
                'label' => 'Content',
                'sections' => [
                    [
                        'label' => 'Widget Content',
                        'settings' => [
                            [
                                'type' => 'input',
                                'label' => 'Widget Title',
                                'id' => 'widget_title',
                                'default' => $settings['widget_title'] ?? 'Find Compatible Products'
                            ],
                            [
                                'type' => 'textarea',
                                'label' => 'Description',
                                'id' => 'widget_description',
                                'default' => $settings['widget_description'] ?? 'Select your vehicle to find compatible products'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'type' => 'tab',
                'label' => 'Style',
                'sections' => [
                    [
                        'label' => 'Appearance',
                        'settings' => [
                            [
                                'type' => 'select',
                                'label' => 'Theme',
                                'id' => 'theme',
                                'default' => $settings['theme'] ?? 'default',
                                'typeMeta' => [
                                    'selectOptions' => [
                                        ['value' => 'default', 'label' => 'Default'],
                                        ['value' => 'modern', 'label' => 'Modern'],
                                        ['value' => 'compact', 'label' => 'Compact']
                                    ]
                                ]
                            ],
                            [
                                'type' => 'checkbox',
                                'label' => 'Show Product Images',
                                'id' => 'show_images',
                                'default' => $settings['show_images'] ?? true
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Update script-based widgets with new settings
     */
    private function updateScriptWidgets($storeHash, $settings)
    {
        try {
            // Get existing scripts to find YMM widget scripts
            $scripts = $this->bigCommerceService->makeApiCall($storeHash, '/content/scripts');

            if (isset($scripts['data']) && is_array($scripts['data'])) {
                foreach ($scripts['data'] as $script) {
                    if (strpos($script['name'], 'YMM') !== false || strpos($script['name'], 'Vehicle Filter') !== false) {
                        // Update the script with new settings
                        $updatedScript = [
                            'name' => $settings['widget_title'] ?? 'YMM Vehicle Filter Widget',
                            'description' => $settings['widget_description'] ?? 'Drag-and-drop vehicle compatibility filter widget',
                            'html' => $this->generateUpdatedYmmWidgetScript($storeHash, $settings)
                        ];

                        $this->bigCommerceService->makeApiCall(
                            $storeHash,
                            "/content/scripts/{$script['uuid']}",
                            'PUT',
                            $updatedScript
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not update script widgets', [
                'error' => $e->getMessage(),
                'store_hash' => $storeHash
            ]);
        }
    }

    /**
     * Generate updated YMM widget script with new settings
     */
    private function generateUpdatedYmmWidgetScript($storeHash, $settings)
    {
        $apiUrl = config('app.url');
        $title = $settings['widget_title'] ?? 'Find Compatible Products';
        $description = $settings['widget_description'] ?? 'Select your vehicle to find compatible products';
        $theme = $settings['theme'] ?? 'default';
        $showImages = $settings['show_images'] ?? true;

        return "
        <!-- YMM Vehicle Filter Widget -->
        <div id='ymm-widget-container'></div>
        <script>
        (function() {
            const YMM_CONFIG = {
                storeHash: '{$storeHash}',
                apiUrl: '{$apiUrl}',
                containerId: 'ymm-widget-container',
                settings: {
                    title: '{$title}',
                    description: '{$description}',
                    theme: '{$theme}',
                    showImages: " . ($showImages ? 'true' : 'false') . "
                }
            };
            
            // Create YMM widget HTML with updated settings
            function createYmmWidget() {
                const container = document.getElementById(YMM_CONFIG.containerId);
                if (!container) return;
                
                const themeClass = 'ymm-theme-' + YMM_CONFIG.settings.theme;
                
                container.innerHTML = `
                    <div class='ymm-filter-widget \${themeClass}' style='max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;'>
                        <h3 style='margin-top: 0;'>\${YMM_CONFIG.settings.title}</h3>
                        <p style='margin-bottom: 15px; color: #666;'>\${YMM_CONFIG.settings.description}</p>
                        <div class='ymm-form'>
                            <select id='ymm-year' style='width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;'>
                                <option value=''>Select Year</option>
                            </select>
                            <select id='ymm-make' style='width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;' disabled>
                                <option value=''>Select Make</option>
                            </select>
                            <select id='ymm-model' style='width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;' disabled>
                                <option value=''>Select Model</option>
                            </select>
                            <button id='ymm-search' style='width: 100%; padding: 10px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;' disabled>
                                Search Compatible Products
                            </button>
                        </div>
                        <div id='ymm-results' style='margin-top: 20px;'></div>
                    </div>
                `;
                
                // Initialize widget functionality
                initializeYmmWidget();
            }
            
            // [Rest of the JavaScript functionality remains the same as before]
            function initializeYmmWidget() {
                loadYears();
                
                document.getElementById('ymm-year').addEventListener('change', function() {
                    if (this.value) {
                        loadMakes(this.value);
                        document.getElementById('ymm-make').disabled = false;
                    } else {
                        resetSelect('ymm-make');
                        resetSelect('ymm-model');
                    }
                });
                
                document.getElementById('ymm-make').addEventListener('change', function() {
                    if (this.value) {
                        loadModels(document.getElementById('ymm-year').value, this.value);
                        document.getElementById('ymm-model').disabled = false;
                    } else {
                        resetSelect('ymm-model');
                    }
                });
                
                document.getElementById('ymm-model').addEventListener('change', function() {
                    document.getElementById('ymm-search').disabled = !this.value;
                });
                
                document.getElementById('ymm-search').addEventListener('click', function() {
                    searchProducts();
                });
            }
            
            function loadYears() {
                fetch(`\${YMM_CONFIG.apiUrl}/api/ymm/\${YMM_CONFIG.storeHash}/years`)
                    .then(response => response.json())
                    .then(years => {
                        const select = document.getElementById('ymm-year');
                        years.forEach(year => {
                            const option = document.createElement('option');
                            option.value = year;
                            option.textContent = year;
                            select.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error loading years:', error));
            }
            
            function loadMakes(year) {
                fetch(`\${YMM_CONFIG.apiUrl}/api/ymm/\${YMM_CONFIG.storeHash}/makes?year=\${year}`)
                    .then(response => response.json())
                    .then(makes => {
                        const select = document.getElementById('ymm-make');
                        select.innerHTML = '<option value=\"\">Select Make</option>';
                        makes.forEach(make => {
                            const option = document.createElement('option');
                            option.value = make;
                            option.textContent = make;
                            select.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error loading makes:', error));
            }
            
            function loadModels(year, make) {
                fetch(`\${YMM_CONFIG.apiUrl}/api/ymm/\${YMM_CONFIG.storeHash}/models?year=\${year}&make=\${encodeURIComponent(make)}`)
                    .then(response => response.json())
                    .then(models => {
                        const select = document.getElementById('ymm-model');
                        select.innerHTML = '<option value=\"\">Select Model</option>';
                        models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model;
                            option.textContent = model;
                            select.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error loading models:', error));
            }
            
            function searchProducts() {
                const year = document.getElementById('ymm-year').value;
                const make = document.getElementById('ymm-make').value;
                const model = document.getElementById('ymm-model').value;
                
                if (!year || !make || !model) return;
                
                const resultsDiv = document.getElementById('ymm-results');
                resultsDiv.innerHTML = '<p>Searching compatible products...</p>';
                
                fetch(`\${YMM_CONFIG.apiUrl}/api/ymm/\${YMM_CONFIG.storeHash}/search?year=\${year}&make=\${encodeURIComponent(make)}&model=\${encodeURIComponent(model)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.products && data.products.length > 0) {
                            let html = '<h4>Compatible Products:</h4><div class=\"ymm-products\">';
                            data.products.forEach(product => {
                                let imageHtml = '';
                                if (YMM_CONFIG.settings.showImages && product.images && product.images.length > 0) {
                                    const thumbnail = product.images.find(img => img.is_thumbnail) || product.images[0];
                                    imageHtml = `<img src='\${thumbnail.url_thumbnail}' alt='\${product.name}' style='width: 50px; height: 50px; object-fit: cover; margin-right: 10px; border-radius: 4px;'>`;
                                }
                                html += `
                                    <div style='border-bottom: 1px solid #eee; padding: 10px 0; display: flex; align-items: center;'>
                                        \${imageHtml}
                                        <div style='flex: 1;'>
                                            <h5 style='margin: 0 0 5px 0;'><a href='\${product.custom_url?.url || \"#\"}' style='color: #007cba; text-decoration: none;'>\${product.name}</a></h5>
                                            <p style='margin: 0; color: #666; font-size: 14px;'>$\${product.calculated_price}</p>
                                        </div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                            resultsDiv.innerHTML = html;
                        } else {
                            resultsDiv.innerHTML = '<p>No compatible products found for this vehicle.</p>';
                        }
                    })
                    .catch(error => {
                        console.error('Error searching products:', error);
                        resultsDiv.innerHTML = '<p>Error searching products. Please try again.</p>';
                    });
            }
            
            function resetSelect(id) {
                const select = document.getElementById(id);
                select.innerHTML = '<option value=\"\">Select ' + id.split('-')[1].charAt(0).toUpperCase() + id.split('-')[1].slice(1) + '</option>';
                select.disabled = true;
                document.getElementById('ymm-search').disabled = true;
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', createYmmWidget);
            } else {
                createYmmWidget();
            }
        })();
        </script>
        ";
    }

    /**
     * Bulk import product compatibility from CSV
     */
    public function importProductCompatibility(Request $request, $storeHash)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);

        $store = BigCommerceStore::where('store_hash', $storeHash)->firstOrFail();

        $file = $request->file('csv_file');
        $csvData = array_map('str_getcsv', file($file->path()));
        $header = array_shift($csvData);

        $imported = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            try {
                if (count($row) < 4) {
                    $errors[] = "Row " . ($index + 2) . ": Insufficient data";
                    continue;
                }

                $productId = $row[0];
                $make = $row[1];
                $model = $row[2];
                $yearStart = $row[3];
                $yearEnd = $row[4] ?? $yearStart;

                // Find matching vehicles
                $vehicles = Vehicle::where('store_hash', $storeHash)
                    ->where('make', $make)
                    ->where('model', $model)
                    ->where(function ($query) use ($yearStart, $yearEnd) {
                        $query->where(function ($q) use ($yearStart, $yearEnd) {
                            $q->where('year_start', '<=', $yearStart)
                                ->where('year_end', '>=', $yearEnd);
                        });
                    })
                    ->get();

                if ($vehicles->isEmpty()) {
                    $errors[] = "Row " . ($index + 2) . ": No matching vehicles found for {$make} {$model} {$yearStart}-{$yearEnd}";
                    continue;
                }

                // Create compatibility relationships
                foreach ($vehicles as $vehicle) {
                    ProductVehicle::firstOrCreate([
                        'bigcommerce_product_id' => $productId,
                        'vehicle_id' => $vehicle->id
                    ]);
                }

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        $message = "Imported compatibility for {$imported} products.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " errors occurred.";
        }

        return redirect()->back()->with('success', $message)->with('import_errors', $errors);
    }

    /**
     * YMM Results page - displays products based on URL parameters
     */
    public function ymmResults(Request $request)
    {
        $year = $request->get('ymm-year');
        $make = $request->get('ymm-make');
        $model = $request->get('ymm-model');

        // For now, return a simple view with the parameters
        // Web designers can customize this page however they want
        return view('ymm-results', [
            'year' => $year,
            'make' => $make,
            'model' => $model,
            'pageTitle' => $year && $make && $model ? "Compatible Products for {$year} {$make} {$model}" : 'Vehicle Compatibility Results'
        ]);
    }
}
