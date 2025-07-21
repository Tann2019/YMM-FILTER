<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\BigCommerceService;

$service = app(BigCommerceService::class);
$storeHash = 'rgp5uxku7h';

// Create the corrected template with hardcoded store hash
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

<div class="ymm-filter-widget" data-theme="{{theme}}" data-store-url="{{@store.secure_url}}">
    <div class="ymm-filter-form">
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-year">Year</label>
                <select id="ymm-year" class="ymm-select">
                    <option value="">Select Year</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-make">Make</label>
                <select id="ymm-make" class="ymm-select" disabled>
                    <option value="">Select Make</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-row">
            <div class="ymm-filter-field">
                <label for="ymm-model">Model</label>
                <select id="ymm-model" class="ymm-select" disabled>
                    <option value="">Select Model</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-actions">
            <button id="ymm-search-btn" class="ymm-btn ymm-btn-primary" disabled>
                Search Products
            </button>
            <button id="ymm-clear-btn" class="ymm-btn ymm-btn-secondary">
                Clear
            </button>
        </div>
    </div>
    
    <div id="ymm-results" class="ymm-results" style="display: none;">
        <div class="ymm-loading" style="display: none;">
            <p>Loading...</p>
        </div>
        <div class="ymm-product-list"></div>
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
    const storeUrl = widget.dataset.storeUrl;
    
    // Extract store hash from the store URL
    // BigCommerce URLs are like: https://store-abc123.mybigcommerce.com
    const storeHash = storeUrl.match(/https:\/\/([^.]+)\.mybigcommerce\.com/)?.[1] || 
                     window.location.hostname.split(' . ')[0]; // fallback to current hostname
    
    const apiUrl = "' . config('app.url') . '";
    
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
        makeSelect.innerHTML = `<option value="">Select Make</option>`;
        modelSelect.innerHTML = `<option value="">Select Model</option>`;
        
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
        modelSelect.innerHTML = `<option value="">Select Model</option>`;
        
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
        makeSelect.innerHTML = `<option value="">Select Make</option>`;
        modelSelect.innerHTML = `<option value="">Select Model</option>`;
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
</script>'
];

try {
    $result = $service->makeApiCall($storeHash, '/content/widget-templates/84192e4f-589f-4638-b4a0-c4096d447bb1', 'PUT', $templateData);
    echo "Widget template updated successfully!\n";
    echo "The widget should now work with the correct store hash.\n";
} catch (Exception $e) {
    echo "Error updating template: " . $e->getMessage() . "\n";
}
