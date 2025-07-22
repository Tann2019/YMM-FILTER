<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BigCommerceService;

$service = app(BigCommerceService::class);
$storeHash = 'rgp5uxku7h';

// Create the corrected template with proper store hash extraction
$templateData = [
    'name' => 'YMM Vehicle Filter',
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

<div class="ymm-filter-widget" 
     data-theme="{{theme}}" 
     data-store-hash="{{@store.store_hash}}"
     data-store-url="{{@store.secure_url}}">
    <div class="ymm-filter-form">
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-year-{{@store.store_hash}}">Year</label>
                <select id="ymm-year-{{@store.store_hash}}" class="ymm-select">
                    <option value="">Select Year</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-make-{{@store.store_hash}}">Make</label>
                <select id="ymm-make-{{@store.store_hash}}" class="ymm-select" disabled>
                    <option value="">Select Make</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-model-{{@store.store_hash}}">Model</label>
                <select id="ymm-model-{{@store.store_hash}}" class="ymm-select" disabled>
                    <option value="">Select Model</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-actions">
            <button id="ymm-search-btn-{{@store.store_hash}}" class="ymm-btn ymm-btn-primary" disabled>
                Search Products
            </button>
            <button id="ymm-clear-btn-{{@store.store_hash}}" class="ymm-btn ymm-btn-secondary">
                Clear
            </button>
        </div>
    </div>
    
    <div id="ymm-results-{{@store.store_hash}}" class="ymm-results" style="display: none;">
        <div class="ymm-loading" style="display: none;">
            <p>Loading...</p>
        </div>
        <div class="ymm-product-list"></div>
        <div class="ymm-error-message" style="display: none; color: red; padding: 10px;"></div>
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
    const widget = document.querySelector(".ymm-filter-widget");
    if (!widget) return;
    
    const storeHash = widget.dataset.storeHash;
    const storeUrl = widget.dataset.storeUrl;
    
    // Use the ngrok URL for development
    const apiUrl = "' . config('app.url') . '";
    
    console.log("YMM Widget Initialized:");
    console.log("Store Hash:", storeHash);
    console.log("Store URL:", storeUrl);
    console.log("API URL:", apiUrl);
    console.log("API Endpoint Example:", apiUrl + "/api/ymm/" + storeHash + "/years");
    
    // Get elements with store hash suffix
    let yearSelect = document.getElementById("ymm-year-" + storeHash);
    let makeSelect = document.getElementById("ymm-make-" + storeHash);
    let modelSelect = document.getElementById("ymm-model-" + storeHash);
    let searchBtn = document.getElementById("ymm-search-btn-" + storeHash);
    let clearBtn = document.getElementById("ymm-clear-btn-" + storeHash);
    let results = document.getElementById("ymm-results-" + storeHash);
    
    if (!yearSelect || !makeSelect || !modelSelect || !searchBtn || !clearBtn || !results) {
        console.error("YMM Widget: Required elements not found");
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
                console.log("Years response headers:", response.headers);
                if (!response.ok) {
                    throw new Error("HTTP " + response.status + ": " + response.statusText);
                }
                return response.json();
            })
            .then(years => {
                console.log("Years loaded:", years);
                const select = document.getElementById("ymm-year-" + storeHash);
                years.forEach(year => {
                    const option = document.createElement("option");
                    option.value = year;
                    option.textContent = year;
                    select.appendChild(option);
                });
                makeSelect.disabled = false;
            })
            .catch(error => {
                console.error("Error loading years:", error);
                console.log("Full error details:", {
                    message: error.message,
                    stack: error.stack,
                    url: url
                });
                showError("Failed to load years. Please check your connection.");
            });
    }
    
    function handleYearChange() {
        const year = yearSelect.value;
        makeSelect.innerHTML = "<option value=\"\">Select Make</option>";
        modelSelect.innerHTML = "<option value=\"\">Select Model</option>";
        makeSelect.disabled = !year;
        modelSelect.disabled = true;
        updateSearchButton();
        
        if (year) {
            const url = apiUrl + "/api/ymm/" + storeHash + "/makes?year=" + year;
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
                    populateSelect(makeSelect, makes);
                    makeSelect.disabled = false;
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
        
        if (year && make) {
            const url = apiUrl + "/api/ymm/" + storeHash + "/models?year=" + year + "&make=" + encodeURIComponent(make);
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
                    populateSelect(modelSelect, models);
                    modelSelect.disabled = false;
                })
                .catch(error => {
                    console.error("Error loading models:", error);
                    showError("Failed to load models.");
                });
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
        results.querySelector(".ymm-loading").style.display = "block";
        hideError();
        
        // Search for compatible products
        const url = apiUrl + "/api/ymm/" + storeHash + "/search?year=" + year + "&make=" + encodeURIComponent(make) + "&model=" + encodeURIComponent(model);
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
        
        if (products.length === 0) {
            productList.innerHTML = "<p>No compatible products found for your vehicle.</p>";
            return;
        }
        
        const html = products.map(product => `
            <div class="ymm-product-item">
                ${product.images && product.images[0] ? 
                    "<img src=\"" + product.images[0].url_thumbnail + "\" alt=\"" + product.name + "\" class=\"ymm-product-image\">" 
                    : ""
                }
                <div class="ymm-product-info">
                    <h4 class="ymm-product-name">${product.name}</h4>
                    <p class="ymm-product-price">$${product.calculated_price}</p>
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
'
];

try {
    $result = $service->makeApiCall($storeHash, '/content/widget-templates/84192e4f-589f-4638-b4a0-c4096d447bb1', 'PUT', $templateData);
    echo "Widget template updated successfully!\n";
    echo "The widget should now work with the correct store hash and error handling.\n";
    echo "Changes made:\n";
    echo "- Fixed store hash extraction to use {{@store.store_hash}}\n";
    echo "- Added unique element IDs with store hash suffix\n";
    echo "- Improved error handling and logging\n";
    echo "- Added CORS headers for ngrok development\n";
    echo "- Added proper loading states and error messages\n";
} catch (Exception $e) {
    echo "Error updating template: " . $e->getMessage() . "\n";
}
