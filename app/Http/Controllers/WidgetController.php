<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\BigCommerceStore;
use App\Models\Vehicle;
use App\Services\BigCommerceService;
use Inertia\Inertia;

class WidgetController extends Controller {
    protected $bigCommerceService;

    public function __construct(BigCommerceService $bigCommerceService)
    {
        $this->bigCommerceService = $bigCommerceService;
    }

    /**
     * Create a Page Builder widget template
     */
    public function createPageBuilderWidget(Request $request, $storeHash)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)->firstOrFail();

        try {
            // Get hardcoded API URL for widget
            $apiUrl = rtrim(config('app.url'), '/');
            
            // Try to create widget template first (if we have content scope)
            $widgetTemplate = [
                'name' => 'YMM Vehicle Filter',
                'template' => $this->getWidgetTemplate($apiUrl, $storeHash),
                'schema' => $this->getWidgetSchema()
            ];

            $response = $this->bigCommerceService->createWidgetTemplate(
                $storeHash,
                $widgetTemplate
            );

            // Check if we got a script-based widget instead
            if (isset($response['data']['kind']) && $response['data']['kind'] === 'script_widget') {
                return back()->with('success', 'YMM Vehicle Filter script installed! The widget will appear on your storefront pages. Note: This is a script-based implementation since Page Builder widget creation requires additional API scopes.');
            }

            return back()->with('success', 'Widget template created! You can now drag and drop the YMM Vehicle Filter in Page Builder.');
        } catch (\Exception $e) {
            Log::error('Widget creation failed', [
                'error' => $e->getMessage(),
                'store_hash' => $storeHash
            ]);

            return back()->withErrors(['error' => 'Failed to create widget: ' . $e->getMessage()]);
        }
    }

    /**
     * Update Page Builder widget template
     */
    public function updatePageBuilderWidget(Request $request, $storeHash, $templateId)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)->firstOrFail();

        try {
            // Get hardcoded API URL for widget update
            $apiUrl = rtrim(config('app.url'), '/');
            
            $widgetTemplate = [
                'name' => 'YMM Vehicle Filter',
                'template' => $this->getWidgetTemplate($apiUrl, $storeHash),
                'schema' => $this->getWidgetSchema()
            ];

            $response = $this->bigCommerceService->makeApiCall(
                $storeHash,
                "/content/widget-templates/{$templateId}",
                'PUT',
                $widgetTemplate
            );

            return back()->with('success', 'Widget template updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update widget: ' . $e->getMessage()]);
        }
    }

    /**
     * Get list of widget templates
     */
    public function getWidgetTemplates($storeHash)
    {
        try {
            $response = $this->bigCommerceService->makeApiCall($storeHash, '/content/widget-templates');
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Render the widget for frontend use
     */
    public function renderWidget(Request $request, $storeHash)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)->firstOrFail();

        // Get widget configuration from request
        $config = [
            'title' => $request->get('title', 'Vehicle Compatibility Filter'),
            'show_images' => $request->get('show_images', true),
            'theme' => $request->get('theme', 'default'),
            'button_text' => $request->get('button_text', 'Search Compatible Products'),
            'placeholder_year' => $request->get('placeholder_year', 'Select Year'),
            'placeholder_make' => $request->get('placeholder_make', 'Select Make'),
            'placeholder_model' => $request->get('placeholder_model', 'Select Model'),
        ];

        // Get available vehicles for dropdowns
        $vehicles = Vehicle::where('store_hash', $storeHash)
            ->where('is_active', true)
            ->orderBy('make')
            ->orderBy('model')
            ->orderBy('year_start')
            ->get();

        return response()->view('components.ymm-widget-pagebuilder', [
            'storeHash' => $storeHash,
            'config' => $config,
            'vehicles' => $vehicles,
            'apiUrl' => config('app.url')
        ])->header('X-Frame-Options', 'ALLOWALL');
    }

    /**
     * Get widget configuration schema for Page Builder
     */
    private function getWidgetSchema()
    {
        return [
            [
                'type' => 'tab',
                'label' => 'Content',
                'sections' => [
                    [
                        'label' => 'Widget Settings',
                        'settings' => [
                            [
                                'type' => 'input',
                                'label' => 'Widget Title',
                                'id' => 'title',
                                'default' => 'Vehicle Compatibility Filter',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'input',
                                'label' => 'Search Button Text',
                                'id' => 'button_text',
                                'default' => 'Search Compatible Products',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'checkbox',
                                'label' => 'Show Vehicle Images',
                                'id' => 'show_images',
                                'default' => true,
                                'typeName' => 'boolean'
                            ]
                        ]
                    ],
                    [
                        'label' => 'Placeholders',
                        'settings' => [
                            [
                                'type' => 'input',
                                'label' => 'Year Placeholder',
                                'id' => 'placeholder_year',
                                'default' => 'Select Year',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'input',
                                'label' => 'Make Placeholder',
                                'id' => 'placeholder_make',
                                'default' => 'Select Make',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'input',
                                'label' => 'Model Placeholder',
                                'id' => 'placeholder_model',
                                'default' => 'Select Model',
                                'typeName' => 'string'
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
                                'typeName' => 'string',
                                'options' => [
                                    [
                                        'value' => 'default',
                                        'label' => 'Default'
                                    ],
                                    [
                                        'value' => 'modern',
                                        'label' => 'Modern'
                                    ],
                                    [
                                        'value' => 'compact',
                                        'label' => 'Compact'
                                    ]
                                ],
                                'default' => 'default'
                            ],
                            [
                                'type' => 'color',
                                'label' => 'Primary Color',
                                'id' => 'primary_color',
                                'default' => '#3B82F6',
                                'typeName' => 'string'
                            ],
                            [
                                'type' => 'color',
                                'label' => 'Button Color',
                                'id' => 'button_color',
                                'default' => '#1D4ED8',
                                'typeName' => 'string'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get the widget HTML template with hardcoded API URL and store hash
     * 
     * @param string $apiUrl The API URL to use for the widget
     * @param string $storeHash The store hash to use for the widget
     * @return string The widget HTML template
     */
    private function getWidgetTemplate($apiUrl = null, $storeHash = null)
    {
        $apiUrl = $apiUrl ?: config('app.url');
        
        if (!$storeHash) {
            throw new \InvalidArgumentException('Store hash is required for widget template generation');
        }
        return '
{{#if title}}
    <h3 class="ymm-widget-title">{{title}}</h3>
{{/if}}

<div class="ymm-filter-widget" data-theme="{{theme}}">
    <div class="ymm-filter-form">
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-year">Year</label>
                <select id="ymm-year" class="ymm-select">
                    <option value="">{{placeholder_year}}</option>
                </select>
            </div>
            <div class="ymm-filter-field">
                <label for="ymm-make">Make</label>
                <select id="ymm-make" class="ymm-select">
                    <option value="">{{placeholder_make}}</option>
                </select>
            </div>
            <div class="ymm-filter-field">
                <label for="ymm-model">Model</label>
                <select id="ymm-model" class="ymm-select">
                    <option value="">{{placeholder_model}}</option>
                </select>
            </div>
        </div>
        <div class="ymm-filter-actions">
            <button type="button" id="ymm-search-btn" class="ymm-search-button">
                {{button_text}}
            </button>
            <button type="button" id="ymm-clear-btn" class="ymm-clear-button">
                Clear
            </button>
        </div>
    </div>
</div>

<!-- Loading and Results Section -->
<div id="ymm-loading" style="display: none;">
    <div class="ymm-loading-spinner"></div>
    <p>Finding compatible products...</p>
</div>

<div id="ymm-results" style="display: none;">
    <h4>Compatible Products</h4>
    <div id="ymm-products-grid" class="ymm-products-grid">
        <!-- Products will be loaded here -->
    </div>
</div>

<style>
.ymm-filter-widget {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    background: #fff;
}

.ymm-widget-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 20px;
    text-align: center;
    color: #2d3748;
}

.ymm-filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.ymm-filter-field {
    display: flex;
    flex-direction: column;
}

.ymm-filter-field label {
    font-weight: 500;
    margin-bottom: 5px;
    color: #4a5568;
    font-size: 0.875rem;
}

.ymm-select {
    padding: 10px 12px;
    border: 1px solid #d2d6dc;
    border-radius: 6px;
    font-size: 1rem;
    background-color: #fff;
    color: #2d3748;
    transition: border-color 0.15s ease;
}

.ymm-select:focus {
    outline: none;
    border-color: {{primary_color}};
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.ymm-filter-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.ymm-search-button,
.ymm-clear-button {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ymm-search-button {
    background-color: {{button_color}};
    color: white;
}

.ymm-search-button:hover {
    background-color: #1e40af;
    transform: translateY(-1px);
}

.ymm-search-button:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

.ymm-clear-button {
    background-color: #f3f4f6;
    color: #6b7280;
    border: 1px solid #d1d5db;
}

.ymm-clear-button:hover {
    background-color: #e5e7eb;
}

/* Theme Variations */
.ymm-filter-widget[data-theme="modern"] {
    border: none;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
}

.ymm-filter-widget[data-theme="compact"] {
    padding: 15px;
}

.ymm-filter-widget[data-theme="compact"] .ymm-filter-row {
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

@media (max-width: 768px) {
    .ymm-filter-row {
        grid-template-columns: 1fr;
    }
    
    .ymm-filter-actions {
        flex-direction: column;
    }
}

/* Loading and Results Styles */
#ymm-loading {
    text-align: center;
    padding: 20px;
    color: #6b7280;
}

.ymm-loading-spinner {
    display: inline-block;
    width: 30px;
    height: 30px;
    border: 3px solid #e5e7eb;
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    animation: ymm-spin 1s linear infinite;
    margin-bottom: 10px;
}

@keyframes ymm-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

#ymm-results {
    margin-top: 20px;
    padding: 20px;
    border-top: 1px solid #e1e5e9;
}

#ymm-results h4 {
    margin: 0 0 15px 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #2d3748;
}

.ymm-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.ymm-product-card {
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    padding: 15px;
    background: #f9fafb;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.ymm-product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.ymm-product-image {
    width: 100%;
    max-width: 150px;
    height: auto;
    border-radius: 4px;
    margin-bottom: 10px;
}

.ymm-product-name {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #2d3748;
    line-height: 1.4;
}

.ymm-product-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #3b82f6;
    margin-bottom: 10px;
}

.ymm-product-link {
    display: inline-block;
    background: #3b82f6;
    color: white;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.ymm-product-link:hover {
    background: #2563eb;
    text-decoration: none;
}

.ymm-no-results {
    text-align: center;
    padding: 30px;
    color: #6b7280;
}

.ymm-no-results h5 {
    margin: 0 0 10px 0;
    font-size: 1.1rem;
    font-weight: 600;
}
</style>

<script>
(function() {
    // Hardcoded API URL and store hash - injected at widget creation time
    const apiUrl = "' . rtrim($apiUrl, '/') . '";
    const storeHash = "' . $storeHash . '";
    
    console.log("YMM Widget initialized with hardcoded values:", { apiUrl, storeHash });
    
    let yearSelect = document.getElementById("ymm-year");
    let makeSelect = document.getElementById("ymm-make");
    let modelSelect = document.getElementById("ymm-model");
    let searchBtn = document.getElementById("ymm-search-btn");
    let clearBtn = document.getElementById("ymm-clear-btn");
    
    // Initialize the widget
    loadYears();
    
    // Event listeners
    if (yearSelect) yearSelect.addEventListener("change", handleYearChange);
    if (makeSelect) makeSelect.addEventListener("change", handleMakeChange);
    if (modelSelect) modelSelect.addEventListener("change", handleModelChange);
    if (searchBtn) searchBtn.addEventListener("click", handleSearch);
    if (clearBtn) clearBtn.addEventListener("click", handleClear);
    
    function loadYears() {
        const url = apiUrl + "/api/ymm/" + storeHash + "/years";
        console.log("Loading years from:", url);
        
        fetch(url, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "ngrok-skip-browser-warning": "true"
            },
            mode: "cors"
        })
        .then(response => {
            console.log("Years response status:", response.status);
            if (!response.ok) {
                throw new Error("HTTP " + response.status + ": " + response.statusText);
            }
            return response.json();
        })
        .then(years => {
            console.log("Years loaded:", years);
            populateSelect(yearSelect, years);
        })
        .catch(error => {
            console.error("Error loading years:", error, "from URL:", url);
        });
    }
    
    function handleYearChange() {
        const year = yearSelect.value;
        if (makeSelect) makeSelect.innerHTML = "<option value=\\"\\">{{placeholder_make}}</option>";
        if (modelSelect) modelSelect.innerHTML = "<option value=\\"\\">{{placeholder_model}}</option>";
        
        if (year) {
            const url = apiUrl + "/api/ymm/" + storeHash + "/makes?year=" + encodeURIComponent(year);
            console.log("Loading makes from:", url);
            
            fetch(url, {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "ngrok-skip-browser-warning": "true"
                },
                mode: "cors"
            })
            .then(response => {
                console.log("Makes response status:", response.status);
                if (!response.ok) {
                    throw new Error("HTTP " + response.status + ": " + response.statusText);
                }
                return response.json();
            })
            .then(makes => {
                console.log("Makes loaded:", makes);
                populateSelect(makeSelect, makes);
            })
            .catch(error => {
                console.error("Error loading makes:", error, "from URL:", url);
            });
        }
    }
    
    function handleMakeChange() {
        const year = yearSelect.value;
        const make = makeSelect.value;
        if (modelSelect) modelSelect.innerHTML = "<option value=\\"\\">{{placeholder_model}}</option>";
        
        if (year && make) {
            const url = apiUrl + "/api/ymm/" + storeHash + "/models?year=" + encodeURIComponent(year) + "&make=" + encodeURIComponent(make);
            console.log("Loading models from:", url);
            
            fetch(url, {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "ngrok-skip-browser-warning": "true"
                },
                mode: "cors"
            })
            .then(response => {
                console.log("Models response status:", response.status);
                if (!response.ok) {
                    throw new Error("HTTP " + response.status + ": " + response.statusText);
                }
                return response.json();
            })
            .then(models => {
                console.log("Models loaded:", models);
                populateSelect(modelSelect, models);
            })
            .catch(error => {
                console.error("Error loading models:", error, "from URL:", url);
            });
        }
    }
    
    function handleModelChange() {
        updateSearchButton();
    }
    
    function handleSearch() {
        const year = yearSelect ? yearSelect.value : "";
        const make = makeSelect ? makeSelect.value : "";
        const model = modelSelect ? modelSelect.value : "";
        
        if (!year || !make || !model) {
            alert("Please select year, make, and model");
            return;
        }
        
        // Show loading state
        const loadingDiv = document.getElementById("ymm-loading");
        const resultsDiv = document.getElementById("ymm-results");
        
        if (loadingDiv) loadingDiv.style.display = "block";
        if (resultsDiv) resultsDiv.style.display = "none";
        
        // First, get compatible products to build the search query
        const url = apiUrl + "/api/ymm/" + storeHash + "/search?year=" + encodeURIComponent(year) + "&make=" + encodeURIComponent(make) + "&model=" + encodeURIComponent(model);
        console.log("Getting compatible products:", url);
        
        fetch(url, {
            method: "GET",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "ngrok-skip-browser-warning": "true"
            },
            mode: "cors"
        })
        .then(response => {
            console.log("Search response status:", response.status);
            if (!response.ok) {
                throw new Error("HTTP " + response.status + ": " + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            console.log("Compatible products:", data);
            if (loadingDiv) loadingDiv.style.display = "none";
            
            const products = data.products || [];
            if (products.length > 0) {
                // Redirect to BigCommerce search with compatible product names/SKUs
                redirectToBigCommerceSearch(products, year, make, model);
            } else {
                displayResults([]);
            }
        })
        .catch(error => {
            console.error("Error getting compatible products:", error, "from URL:", url);
            if (loadingDiv) loadingDiv.style.display = "none";
            
            // Fallback: redirect to BigCommerce search with vehicle info
            const searchQuery = year + " " + make + " " + model;
            const bigCommerceUrl = "/search.php?mode=1&search_query_adv=" + encodeURIComponent(searchQuery) + "&searchsubs=ON&section=product";
            console.log("Fallback: redirecting to BigCommerce search:", bigCommerceUrl);
            window.location.href = bigCommerceUrl;
        });
    }
    
    function redirectToBigCommerceSearch(products, year, make, model) {
        // Create search query from product names and SKUs
        const productTerms = [];
        const skus = [];
        
        products.forEach(function(product) {
            if (product.name) {
                // Extract key terms from product names (avoid common words)
                const nameTerms = product.name.split(/\s+/).filter(function(term) {
                    const commonWords = ["for", "the", "and", "with", "auto", "car", "truck", "vehicle"];
                    return term.length > 2 && !commonWords.includes(term.toLowerCase());
                });
                productTerms.push(...nameTerms);
            }
            if (product.sku) {
                skus.push(product.sku);
            }
        });
        
        // Build search parameters
        let searchQuery = "";
        let additionalParams = "";
        
        // Strategy 1: Use SKUs if available (most accurate)
        if (skus.length > 0) {
            searchQuery = skus.slice(0, 5).join(" OR "); // Limit SKUs to avoid URL length issues
            console.log("Using SKU-based search:", searchQuery);
        }
        // Strategy 2: Use vehicle info + key product terms
        else if (productTerms.length > 0) {
            const vehicleTerms = [year, make, model];
            const uniqueTerms = [...new Set([...vehicleTerms, ...productTerms.slice(0, 5)])]; // Remove duplicates
            searchQuery = uniqueTerms.join(" ");
            console.log("Using vehicle + product terms search:", searchQuery);
        }
        // Strategy 3: Vehicle info only (fallback)
        else {
            searchQuery = year + " " + make + " " + model;
            console.log("Using vehicle-only search:", searchQuery);
        }
        
        // Add category filter if we know the category (you can customize this)
        // Looking at your URL, category[]=27 seems to be for specific parts
        // You might want to add: additionalParams = "&category[]=27";
        
        // Build final BigCommerce search URL
        const bigCommerceUrl = "/search.php?mode=1&search_query_adv=" + encodeURIComponent(searchQuery) + 
                              "&searchsubs=ON&section=product" + additionalParams;
        
        console.log("Redirecting to BigCommerce search:", bigCommerceUrl);
        console.log("Search will show results matching:", searchQuery);
        
        // Add a small delay for user feedback, then redirect
        setTimeout(function() {
            window.location.href = bigCommerceUrl;
        }, 500);
    }
    
    function handleClear() {
        if (yearSelect) yearSelect.value = "";
        if (makeSelect) makeSelect.innerHTML = "<option value=\\"\\">{{placeholder_make}}</option>";
        if (modelSelect) modelSelect.innerHTML = "<option value=\\"\\">{{placeholder_model}}</option>";
        updateSearchButton();
    }
    
    function populateSelect(select, options) {
        if (!select || !options) return;
        
        options.forEach(function(option) {
            const optionElement = document.createElement("option");
            optionElement.value = option;
            optionElement.textContent = option;
            select.appendChild(optionElement);
        });
    }
    
    function updateSearchButton() {
        const year = yearSelect ? yearSelect.value : "";
        const make = makeSelect ? makeSelect.value : "";
        const model = modelSelect ? modelSelect.value : "";
        
        if (searchBtn) {
            searchBtn.disabled = !year || !make || !model;
        }
    }
    
    function displayResults(products) {
        const resultsDiv = document.getElementById("ymm-results");
        const productsGrid = document.getElementById("ymm-products-grid");
        
        if (!resultsDiv || !productsGrid) return;
        
        if (products && products.length > 0) {
            const html = products.map(function(product) {
                return \'<div class="ymm-product-card">\' +
                    (product.images && product.images[0] ? 
                        \'<img src="\' + product.images[0].url_thumbnail + \'" alt="\' + product.name + \'" class="ymm-product-image">\' 
                        : \'\') +
                    \'<h5 class="ymm-product-name">\' + product.name + \'</h5>\' +
                    \'<div class="ymm-product-price">$\' + (product.price || product.calculated_price || \'N/A\') + \'</div>\' +
                    \'<a href="\' + (product.custom_url && product.custom_url.url ? product.custom_url.url : \'#\') + \'" class="ymm-product-link">View Product</a>\' +
                \'</div>\';
            }).join(\'\');
            
            productsGrid.innerHTML = html;
        } else {
            productsGrid.innerHTML = \'<div class="ymm-no-results"><h5>No Compatible Products Found</h5><p>Sorry, we couldn\\\'t find any products compatible with your vehicle selection.</p></div>\';
        }
        
        resultsDiv.style.display = "block";
    }
})();
</script>
';
    }
}
