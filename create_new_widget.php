<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BigCommerceService;

$service = app(BigCommerceService::class);
$storeHash = 'rgp5uxku7h';

// Create a new widget template with proper schema
$templateData = [
    'name' => 'YMM Vehicle Filter Widget',
    'template' => '
{{#if widget_title}}
    <h3 class="ymm-widget-title">{{widget_title}}</h3>
{{else}}
    <h3 class="ymm-widget-title">Find Compatible Products</h3>
{{/if}}

{{#if widget_description}}
    <p class="ymm-widget-description">{{widget_description}}</p>
{{else}}
    <p class="ymm-widget-description">Select your vehicle to find compatible products</p>
{{/if}}

<div class="ymm-filter-widget-{{_.id}}" 
     data-theme="{{theme}}" 
     data-store-hash="{{@store.store_hash}}"
     data-store-url="{{@store.secure_url}}"
     data-widget-id="{{_.id}}">
    <div class="ymm-filter-form">
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-year-{{_.id}}">Year</label>
                <select id="ymm-year-{{_.id}}" class="ymm-select">
                    <option value="">Select Year</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-make-{{_.id}}">Make</label>
                <select id="ymm-make-{{_.id}}" class="ymm-select" disabled>
                    <option value="">Select Make</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-model-{{_.id}}">Model</label>
                <select id="ymm-model-{{_.id}}" class="ymm-select" disabled>
                    <option value="">Select Model</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-actions">
            <button id="ymm-search-btn-{{_.id}}" class="ymm-btn ymm-btn-primary" disabled>
                Search Products
            </button>
            <button id="ymm-clear-btn-{{_.id}}" class="ymm-btn ymm-btn-secondary">
                Clear
            </button>
        </div>
    </div>
    
    <div id="ymm-results-{{_.id}}" class="ymm-results" style="display: none;">
        <div class="ymm-loading" style="display: none;">
            <p>Loading...</p>
        </div>
        <div class="ymm-product-list"></div>
        <div class="ymm-error-message" style="display: none; color: red; padding: 10px;"></div>
    </div>
</div>

<style>
.ymm-filter-widget-{{_.id}} {
    max-width: 400px;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    font-family: Arial, sans-serif;
}

.ymm-filter-widget-{{_.id}} .ymm-widget-title {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.ymm-filter-widget-{{_.id}} .ymm-widget-description {
    margin-bottom: 20px;
    color: #666;
    font-size: 14px;
}

.ymm-filter-widget-{{_.id}} .ymm-filter-row {
    margin-bottom: 15px;
}

.ymm-filter-widget-{{_.id}} .ymm-filter-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.ymm-filter-widget-{{_.id}} .ymm-select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

.ymm-filter-widget-{{_.id}} .ymm-select:disabled {
    background-color: #f5f5f5;
    color: #999;
}

.ymm-filter-widget-{{_.id}} .ymm-filter-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.ymm-filter-widget-{{_.id}} .ymm-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

.ymm-filter-widget-{{_.id}} .ymm-btn-primary {
    background: #007cba;
    color: white;
}

.ymm-filter-widget-{{_.id}} .ymm-btn-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.ymm-filter-widget-{{_.id}} .ymm-btn-secondary {
    background: #6c757d;
    color: white;
}

.ymm-filter-widget-{{_.id}} .ymm-results {
    margin-top: 20px;
    padding: 15px;
    background: white;
    border-radius: 4px;
}

.ymm-filter-widget-{{_.id}} .ymm-loading {
    text-align: center;
    padding: 20px;
}

.ymm-filter-widget-{{_.id}} .ymm-product-grid {
    display: grid;
    gap: 10px;
}

.ymm-filter-widget-{{_.id}} .ymm-product-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border: 1px solid #eee;
    border-radius: 4px;
}

.ymm-filter-widget-{{_.id}} .ymm-product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    margin-right: 10px;
    border-radius: 4px;
}

.ymm-filter-widget-{{_.id}} .ymm-product-info {
    flex: 1;
}

.ymm-filter-widget-{{_.id}} .ymm-product-name {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.ymm-filter-widget-{{_.id}} .ymm-product-price {
    margin: 0 0 5px 0;
    font-weight: bold;
    color: #007cba;
}

.ymm-filter-widget-{{_.id}} .ymm-product-link {
    color: #007cba;
    text-decoration: none;
    font-size: 14px;
}

.ymm-filter-widget-{{_.id}} .ymm-product-link:hover {
    text-decoration: underline;
}
</style>

<script>
(function() {
    function initializeYmmWidget_{{_.id}}() {
        const widget = document.querySelector(".ymm-filter-widget-{{_.id}}");
        if (!widget) {
            console.error("YMM Widget {{_.id}}: Widget container not found");
            return;
        }
        
        const storeHash = widget.dataset.storeHash;
        const storeUrl = widget.dataset.storeUrl;
        const widgetId = widget.dataset.widgetId || "{{_.id}}";
        
        // Use the ngrok URL for development - this should match your Laravel app URL
        const apiUrl = "' . config('app.url') . '";
        
        console.log("YMM Widget {{_.id}} Initialized:");
        console.log("Store Hash:", storeHash);
        console.log("Store URL:", storeUrl);
        console.log("API URL:", apiUrl);
        console.log("Widget ID:", widgetId);
        
        // Get elements with widget ID suffix
        const yearSelect = document.getElementById("ymm-year-" + widgetId);
        const makeSelect = document.getElementById("ymm-make-" + widgetId);
        const modelSelect = document.getElementById("ymm-model-" + widgetId);
        const searchBtn = document.getElementById("ymm-search-btn-" + widgetId);
        const clearBtn = document.getElementById("ymm-clear-btn-" + widgetId);
        const results = document.getElementById("ymm-results-" + widgetId);
        
        if (!yearSelect || !makeSelect || !modelSelect || !searchBtn || !clearBtn || !results) {
            console.error("YMM Widget {{_.id}}: Required elements not found");
            console.log("Elements found:", {
                yearSelect: !!yearSelect,
                makeSelect: !!makeSelect,
                modelSelect: !!modelSelect,
                searchBtn: !!searchBtn,
                clearBtn: !!clearBtn,
                results: !!results
            });
            return;
        }
        
        // Initialize the widget
        loadYears();
        
        // Event listeners
        yearSelect.addEventListener("change", handleYearChange);
        makeSelect.addEventListener("change", handleMakeChange);
        modelSelect.addEventListener("change", handleModelChange);
        searchBtn.addEventListener("click", handleSearch);
        clearBtn.addEventListener("click", handleClear);
        
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
                    
                    // Clear existing options except first
                    yearSelect.innerHTML = "<option value=\"\">Select Year</option>";
                    
                    if (Array.isArray(years) && years.length > 0) {
                        years.forEach(year => {
                            const option = document.createElement("option");
                            option.value = year;
                            option.textContent = year;
                            yearSelect.appendChild(option);
                        });
                        console.log("Years populated successfully");
                    } else {
                        console.warn("No years data received");
                        showError("No years available");
                    }
                })
                .catch(error => {
                    console.error("Error loading years:", error);
                    showError("Failed to load years. Please check your connection and try again.");
                });
        }
        
        function handleYearChange() {
            const year = yearSelect.value;
            makeSelect.innerHTML = "<option value=\"\">Select Make</option>";
            modelSelect.innerHTML = "<option value=\"\">Select Model</option>";
            makeSelect.disabled = !year;
            modelSelect.disabled = true;
            updateSearchButton();
            hideError();
            
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
                        if (!response.ok) {
                            throw new Error("HTTP " + response.status + ": " + response.statusText);
                        }
                        return response.json();
                    })
                    .then(makes => {
                        console.log("Makes loaded:", makes);
                        if (Array.isArray(makes) && makes.length > 0) {
                            populateSelect(makeSelect, makes);
                            makeSelect.disabled = false;
                        } else {
                            showError("No makes available for selected year");
                        }
                    })
                    .catch(error => {
                        console.error("Error loading makes:", error);
                        showError("Failed to load makes.");
                    });
            }
        }
        
        function handleMakeChange() {
            const year = yearSelect.value;
            const make = makeSelect.value;
            modelSelect.innerHTML = "<option value=\"\">Select Model</option>";
            modelSelect.disabled = !make;
            updateSearchButton();
            hideError();
            
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
                        if (!response.ok) {
                            throw new Error("HTTP " + response.status + ": " + response.statusText);
                        }
                        return response.json();
                    })
                    .then(models => {
                        console.log("Models loaded:", models);
                        if (Array.isArray(models) && models.length > 0) {
                            populateSelect(modelSelect, models);
                            modelSelect.disabled = false;
                        } else {
                            showError("No models available for selected make");
                        }
                    })
                    .catch(error => {
                        console.error("Error loading models:", error);
                        showError("Failed to load models.");
                    });
            }
        }
        
        function handleModelChange() {
            updateSearchButton();
            hideError();
        }
        
        function handleSearch() {
            const year = yearSelect.value;
            const make = makeSelect.value;
            const model = modelSelect.value;
            
            if (!year || !make || !model) {
                showError("Please select year, make, and model");
                return;
            }
            
            // Show loading
            results.style.display = "block";
            results.querySelector(".ymm-product-list").innerHTML = "";
            results.querySelector(".ymm-loading").style.display = "block";
            hideError();
            
            const url = apiUrl + "/api/ymm/" + storeHash + "/search?year=" + encodeURIComponent(year) + "&make=" + encodeURIComponent(make) + "&model=" + encodeURIComponent(model);
            console.log("Searching products from:", url);
            
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
                    if (!response.ok) {
                        throw new Error("HTTP " + response.status + ": " + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    results.querySelector(".ymm-loading").style.display = "none";
                    console.log("Search results:", data);
                    displayResults(data.products || []);
                })
                .catch(error => {
                    console.error("Error searching products:", error);
                    results.querySelector(".ymm-loading").style.display = "none";
                    showError("Error loading products. Please try again.");
                });
        }
        
        function handleClear() {
            yearSelect.value = "";
            makeSelect.innerHTML = "<option value=\"\">Select Make</option>";
            modelSelect.innerHTML = "<option value=\"\">Select Model</option>";
            makeSelect.disabled = true;
            modelSelect.disabled = true;
            results.style.display = "none";
            hideError();
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
        
        function showError(message) {
            const errorElement = results.querySelector(".ymm-error-message");
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = "block";
                results.style.display = "block";
            }
        }
        
        function hideError() {
            const errorElement = results.querySelector(".ymm-error-message");
            if (errorElement) {
                errorElement.style.display = "none";
            }
        }
        
        function displayResults(products) {
            const productList = results.querySelector(".ymm-product-list");
            
            if (!Array.isArray(products) || products.length === 0) {
                productList.innerHTML = "<p>No compatible products found for your vehicle.</p>";
                return;
            }
            
            const html = products.map(product => {
                const imageHtml = (product.images && product.images[0]) ? 
                    "<img src=\"" + product.images[0].url_thumbnail + "\" alt=\"" + (product.name || "Product") + "\" class=\"ymm-product-image\">" : "";
                
                const priceHtml = product.calculated_price ? 
                    "<p class=\"ymm-product-price\">$" + product.calculated_price + "</p>" : "";
                
                const linkUrl = (product.custom_url && product.custom_url.url) ? product.custom_url.url : "#";
                
                return "<div class=\"ymm-product-item\">" +
                    imageHtml +
                    "<div class=\"ymm-product-info\">" +
                        "<h4 class=\"ymm-product-name\">" + (product.name || "Unknown Product") + "</h4>" +
                        priceHtml +
                        "<a href=\"" + linkUrl + "\" class=\"ymm-product-link\">View Product</a>" +
                    "</div>" +
                "</div>";
            }).join("");
            
            productList.innerHTML = 
                "<h4>Compatible Products (" + products.length + ")</h4>" +
                "<div class=\"ymm-product-grid\">" + html + "</div>";
        }
    }
    
    // Initialize immediately if DOM is ready, otherwise wait
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initializeYmmWidget_{{_.id}});
    } else {
        initializeYmmWidget_{{_.id}}();
    }
})();
</script>',
    'schema' => [
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
                            'id' => 'widget_title',
                            'default' => 'Find Compatible Products',
                            'typeMeta' => [
                                'placeholder' => 'Enter widget title'
                            ]
                        ],
                        [
                            'type' => 'input',
                            'label' => 'Widget Description',
                            'id' => 'widget_description',
                            'default' => 'Select your vehicle to find compatible products',
                            'typeMeta' => [
                                'placeholder' => 'Enter widget description'
                            ]
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
                            'default' => 'default',
                            'typeMeta' => [
                                'selectOptions' => [
                                    [
                                        'value' => 'default',
                                        'label' => 'Default'
                                    ],
                                    [
                                        'value' => 'compact',
                                        'label' => 'Compact'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];

try {
    $result = $service->makeApiCall($storeHash, '/content/widget-templates', 'POST', $templateData);
    echo "New widget template created successfully!\n";
    echo "Widget Template UUID: " . $result['uuid'] . "\n";
    echo "\nThis widget includes:\n";
    echo "- Proper schema for BigCommerce Page Builder\n";
    echo "- Unique widget IDs to prevent conflicts\n";
    echo "- Better error handling and logging\n";
    echo "- CORS headers for ngrok development\n";
    echo "- Scoped CSS to prevent style conflicts\n";
    echo "\nTo use this widget:\n";
    echo "1. Go to your BigCommerce admin\n";
    echo "2. Navigate to Storefront > Page Builder\n";
    echo "3. Edit a page and look for 'YMM Vehicle Filter Widget' in the widget list\n";
    echo "4. Drag and drop it onto your page\n";
    print_r($result);
} catch (Exception $e) {
    echo "Error creating widget template: " . $e->getMessage() . "\n";
}
