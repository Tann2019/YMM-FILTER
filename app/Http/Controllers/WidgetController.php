<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\BigCommerceStore;
use App\Models\Vehicle;
use App\Services\BigCommerceService;
use Inertia\Inertia;

class WidgetController extends Controller
{
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
            // Try to create widget template first (if we have content scope)
            $widgetTemplate = [
                'name' => 'YMM Vehicle Filter',
                'template' => $this->getWidgetTemplate(),
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
            $widgetTemplate = [
                'name' => 'YMM Vehicle Filter',
                'template' => $this->getWidgetTemplate(),
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
     * Get the widget HTML template
     */
    private function getWidgetTemplate()
    {
        return '
{{#if title}}
    <h3 class="ymm-widget-title">{{title}}</h3>
{{/if}}

<div class="ymm-filter-widget" data-theme="{{theme}}" data-store-hash="{{@store.hash}}">
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
    <div id="ymm-results" class="ymm-results" style="display: none;">
        <div class="ymm-loading">
            <div class="ymm-spinner"></div>
            <span>Searching compatible products...</span>
        </div>
        <div class="ymm-product-list"></div>
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

.ymm-results {
    margin-top: 20px;
}

.ymm-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #6b7280;
}

.ymm-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid #e5e7eb;
    border-top: 2px solid {{primary_color}};
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
</style>

<script>
(function() {
    const widget = document.querySelector(".ymm-filter-widget");
    const storeHash = widget.dataset.storeHash;
    const apiUrl = "{{@store.url}}";
    
    let yearSelect = document.getElementById("ymm-year");
    let makeSelect = document.getElementById("ymm-make");
    let modelSelect = document.getElementById("ymm-model");
    let searchBtn = document.getElementById("ymm-search-btn");
    let clearBtn = document.getElementById("ymm-clear-btn");
    let results = document.getElementById("ymm-results");
    
    // Initialize the widget
    loadYears();
    
    // Event listeners
    yearSelect.addEventListener("change", handleYearChange);
    makeSelect.addEventListener("change", handleMakeChange);
    modelSelect.addEventListener("change", handleModelChange);
    searchBtn.addEventListener("click", handleSearch);
    clearBtn.addEventListener("click", handleClear);
    
    function loadYears() {
        fetch(`${apiUrl}/api/ymm/${storeHash}/years`)
            .then(response => response.json())
            .then(years => {
                populateSelect(yearSelect, years);
            })
            .catch(error => console.error("Error loading years:", error));
    }
    
    function handleYearChange() {
        const year = yearSelect.value;
        makeSelect.innerHTML = `<option value="">{{placeholder_make}}</option>`;
        modelSelect.innerHTML = `<option value="">{{placeholder_model}}</option>`;
        
        if (year) {
            fetch(`${apiUrl}/api/ymm/${storeHash}/makes?year=${year}`)
                .then(response => response.json())
                .then(makes => {
                    populateSelect(makeSelect, makes);
                })
                .catch(error => console.error("Error loading makes:", error));
        }
    }
    
    function handleMakeChange() {
        const year = yearSelect.value;
        const make = makeSelect.value;
        modelSelect.innerHTML = `<option value="">{{placeholder_model}}</option>`;
        
        if (year && make) {
            fetch(`${apiUrl}/api/ymm/${storeHash}/models?year=${year}&make=${encodeURIComponent(make)}`)
                .then(response => response.json())
                .then(models => {
                    populateSelect(modelSelect, models);
                })
                .catch(error => console.error("Error loading models:", error));
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
            alert("Please select year, make, and model");
            return;
        }
        
        // Show loading
        results.style.display = "block";
        results.querySelector(".ymm-product-list").innerHTML = "";
        results.querySelector(".ymm-loading").style.display = "flex";
        
        // Search for compatible products
        fetch(`${apiUrl}/api/ymm/${storeHash}/search?year=${year}&make=${encodeURIComponent(make)}&model=${encodeURIComponent(model)}`)
            .then(response => response.json())
            .then(data => {
                results.querySelector(".ymm-loading").style.display = "none";
                displayResults(data.products || []);
            })
            .catch(error => {
                console.error("Error searching products:", error);
                results.querySelector(".ymm-loading").style.display = "none";
                results.querySelector(".ymm-product-list").innerHTML = "<p>Error loading products. Please try again.</p>";
            });
    }
    
    function handleClear() {
        yearSelect.value = "";
        makeSelect.innerHTML = `<option value="">{{placeholder_make}}</option>`;
        modelSelect.innerHTML = `<option value="">{{placeholder_model}}</option>`;
        results.style.display = "none";
        updateSearchButton();
    }
    
    function populateSelect(select, options) {
        options.forEach(option => {
            const optionElement = document.createElement("option");
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
        const productList = results.querySelector(".ymm-product-list");
        
        if (products.length === 0) {
            productList.innerHTML = "<p>No compatible products found for your vehicle.</p>";
            return;
        }
        
        const html = products.map(product => `
            <div class="ymm-product-item">
                ${product.images && product.images[0] ? 
                    `<img src="${product.images[0].url_thumbnail}" alt="${product.name}" class="ymm-product-image">` 
                    : ""
                }
                <div class="ymm-product-info">
                    <h4 class="ymm-product-name">${product.name}</h4>
                    <p class="ymm-product-price">$${product.price}</p>
                    <a href="${product.custom_url?.url || "#"}" class="ymm-product-link">View Product</a>
                </div>
            </div>
        `).join("");
        
        productList.innerHTML = `
            <h4>Compatible Products (${products.length})</h4>
            <div class="ymm-product-grid">${html}</div>
        `;
    }
})();
</script>

<style>
.ymm-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.ymm-product-item {
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    background: #fff;
    transition: transform 0.2s ease;
}

.ymm-product-item:hover {
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
    font-weight: 500;
    margin-bottom: 5px;
    color: #2d3748;
}

.ymm-product-price {
    font-size: 1.125rem;
    font-weight: 600;
    color: {{primary_color}};
    margin-bottom: 10px;
}

.ymm-product-link {
    display: inline-block;
    padding: 8px 16px;
    background-color: {{button_color}};
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 0.875rem;
    transition: background-color 0.15s ease;
}

.ymm-product-link:hover {
    background-color: #1e40af;
    text-decoration: none;
}
</style>
';
    }
}
