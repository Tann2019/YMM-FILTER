<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Compatibility Filter</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <style>
        .ymm-filter-widget {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: Arial, sans-serif;
        }
        
        .ymm-filter-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        
        .ymm-filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .ymm-form-group {
            display: flex;
            flex-direction: column;
        }
        
        .ymm-form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        
        .ymm-form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            background-color: white;
        }
        
        .ymm-form-group select:disabled {
            background-color: #f5f5f5;
            color: #999;
        }
        
        .ymm-filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .ymm-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .ymm-btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .ymm-btn-primary:hover {
            background-color: #0056b3;
        }
        
        .ymm-btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .ymm-btn-secondary:hover {
            background-color: #545b62;
        }
        
        .ymm-btn:disabled {
            background-color: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
        }
        
        .ymm-results {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .ymm-results-count {
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .ymm-loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        
        .ymm-error {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .ymm-product-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .ymm-product-item:last-child {
            border-bottom: none;
        }
        
        .ymm-product-name {
            font-weight: bold;
            color: #333;
        }
        
        .ymm-product-sku {
            color: #666;
            font-size: 0.9em;
        }
        
        .ymm-product-price {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div id="ymm-filter-app" class="ymm-filter-widget">
        <div class="ymm-filter-title">Find Parts for Your Vehicle</div>
        
        <div class="ymm-filter-form">
            <div class="ymm-form-group">
                <label for="make-select">Make</label>
                <select id="make-select" v-model="selectedMake" @change="onMakeChange" :disabled="loading">
                    <option value="">Select Make</option>
                    <option v-for="make in makes" :key="make" :value="make">@{{ make }}</option>
                </select>
            </div>
            
            <div class="ymm-form-group">
                <label for="model-select">Model</label>
                <select id="model-select" v-model="selectedModel" @change="onModelChange" :disabled="!selectedMake || loading">
                    <option value="">Select Model</option>
                    <option v-for="model in models" :key="model" :value="model">@{{ model }}</option>
                </select>
            </div>
            
            <div class="ymm-form-group">
                <label for="year-select">Year</label>
                <select id="year-select" v-model="selectedYear" :disabled="!selectedModel || loading">
                    <option value="">Select Year</option>
                    <option v-for="year in years" :key="year" :value="year">@{{ year }}</option>
                </select>
            </div>
        </div>
        
        <div class="ymm-filter-buttons">
            <button class="ymm-btn ymm-btn-primary" @click="searchProducts" :disabled="!canSearch || loading">
                @{{ loading ? 'Searching...' : 'Find Compatible Parts' }}
            </button>
            <button class="ymm-btn ymm-btn-secondary" @click="clearFilter">
                Show All Products
            </button>
        </div>
        
        <div v-if="error" class="ymm-error">
            @{{ error }}
        </div>
        
        @verbatim
        <div v-if="searchPerformed" class="ymm-results">
            <div v-if="loading" class="ymm-loading">
                Searching for compatible products...
            </div>
            
            <div v-else-if="compatibleProducts.length > 0">
                <div class="ymm-results-count">
                    Found {{ compatibleProducts.length }} compatible products for {{ selectedYear }} {{ selectedMake }} {{ selectedModel }}
                </div>
                
                <div class="ymm-products-list">
                    <div v-for="product in compatibleProducts" :key="product.id" class="ymm-product-item">
                        <div class="ymm-product-name">{{ product.name }}</div>
                        <div class="ymm-product-sku">SKU: {{ product.sku }}</div>
                        <div class="ymm-product-price">${{ product.price }}</div>
                    </div>
                </div>
            </div>
            
            <div v-else>
                <div class="ymm-results-count">
                    No compatible products found for {{ selectedYear }} {{ selectedMake }} {{ selectedModel }}
                </div>
                <p>Try selecting a different vehicle or <button class="ymm-btn ymm-btn-secondary" @click="clearFilter">view all products</button>.</p>
            </div>
        </div>
        @endverbatim
    </div>

    <script>
        const { createApp } = Vue;
        
        createApp({
            data() {
                return {
                    makes: [],
                    models: [],
                    years: [],
                    selectedMake: '',
                    selectedModel: '',
                    selectedYear: '',
                    compatibleProducts: [],
                    loading: false,
                    error: null,
                    searchPerformed: false,
                    // Store identification for multi-shop support
                    storeHash: null,
                    accessToken: null,
                    // App URL - will be set from URL parameters or detected
                    apiBaseUrl: null
                }
            },
            
            computed: {
                canSearch() {
                    return this.selectedMake && this.selectedModel && this.selectedYear;
                }
            },
            
            mounted() {
                this.initializeWidget();
            },
            
            methods: {
                initializeWidget() {
                    // Get store credentials from URL parameters
                    const urlParams = new URLSearchParams(window.location.search);
                    this.storeHash = urlParams.get('store_hash') || this.getStoreHashFromDomain();
                    
                    // Set API base URL
                    this.apiBaseUrl = '{{ config("app.url") }}/api';
                    
                    // Load makes
                    this.loadMakes();
                },
                
                getStoreHashFromDomain() {
                    // Try to extract store hash from referrer or current domain
                    const referrer = document.referrer;
                    if (referrer && referrer.includes('.mybigcommerce.com')) {
                        const match = referrer.match(/https?:\/\/([^.]+)\.mybigcommerce\.com/);
                        return match ? match[1] : null;
                    }
                    return null;
                },
                async loadMakes() {
                    try {
                        this.loading = true;
                        this.error = null;
                        
                        const params = new URLSearchParams();
                        if (this.storeHash) {
                            params.append('store_hash', this.storeHash);
                        }
                        
                        const response = await fetch(`${this.apiBaseUrl}/bigcommerce/makes?${params}`);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        
                        this.makes = await response.json();
                    } catch (error) {
                        this.error = 'Failed to load vehicle makes. Please try again.';
                        console.error('Error loading makes:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                
                async onMakeChange() {
                    if (!this.selectedMake) {
                        this.models = [];
                        this.years = [];
                        this.selectedModel = '';
                        this.selectedYear = '';
                        return;
                    }
                    
                    try {
                        this.loading = true;
                        this.error = null;
                        this.models = [];
                        this.years = [];
                        this.selectedModel = '';
                        this.selectedYear = '';
                        
                        const params = new URLSearchParams({
                            make: this.selectedMake
                        });
                        if (this.storeHash) {
                            params.append('store_hash', this.storeHash);
                        }
                        
                        const response = await fetch(`${this.apiBaseUrl}/bigcommerce/models?${params}`);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        
                        this.models = await response.json();
                    } catch (error) {
                        this.error = 'Failed to load vehicle models. Please try again.';
                        console.error('Error loading models:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                
                async onModelChange() {
                    if (!this.selectedModel) {
                        this.years = [];
                        this.selectedYear = '';
                        return;
                    }
                    
                    try {
                        this.loading = true;
                        this.error = null;
                        this.years = [];
                        this.selectedYear = '';
                        
                        const params = new URLSearchParams({
                            make: this.selectedMake,
                            model: this.selectedModel
                        });
                        if (this.storeHash) {
                            params.append('store_hash', this.storeHash);
                        }
                        
                        const response = await fetch(`${this.apiBaseUrl}/bigcommerce/years?${params}`);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        
                        this.years = await response.json();
                    } catch (error) {
                        this.error = 'Failed to load vehicle years. Please try again.';
                        console.error('Error loading years:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                
                async searchProducts() {
                    if (!this.canSearch) return;
                    
                    try {
                        this.loading = true;
                        this.error = null;
                        this.searchPerformed = true;
                        
                        const params = new URLSearchParams({
                            year: this.selectedYear,
                            make: this.selectedMake,
                            model: this.selectedModel
                        });
                        if (this.storeHash) {
                            params.append('store_hash', this.storeHash);
                        }
                        
                        const response = await fetch(`${this.apiBaseUrl}/bigcommerce/compatible-products?${params}`);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        
                        const data = await response.json();
                        this.compatibleProducts = data.data || data.products || [];
                        
                        // Hide non-compatible products on the page
                        this.filterProductsOnPage();
                        
                    } catch (error) {
                        this.error = 'Failed to search for compatible products. Please try again.';
                        console.error('Error searching products:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                
                clearFilter() {
                    this.selectedMake = '';
                    this.selectedModel = '';
                    this.selectedYear = '';
                    this.models = [];
                    this.years = [];
                    this.compatibleProducts = [];
                    this.searchPerformed = false;
                    this.error = null;
                    
                    // Show all products on the page
                    this.showAllProductsOnPage();
                },
                
                filterProductsOnPage() {
                    // This function will hide/show products on the BigCommerce storefront
                    // based on the compatible product IDs returned from the API
                    
                    const compatibleIds = this.compatibleProducts.map(p => p.id.toString());
                    
                    // Find all product elements on the page (adjust selectors based on your theme)
                    const productElements = document.querySelectorAll('[data-product-id], .product-item, .card-product');
                    
                    productElements.forEach(element => {
                        // Try to extract product ID from various possible attributes/classes
                        let productId = element.getAttribute('data-product-id') || 
                                       element.getAttribute('data-entity-id') ||
                                       this.extractProductIdFromElement(element);
                        
                        if (productId) {
                            if (compatibleIds.includes(productId.toString())) {
                                element.style.display = '';
                                element.classList.remove('ymm-hidden');
                            } else {
                                element.style.display = 'none';
                                element.classList.add('ymm-hidden');
                            }
                        }
                    });
                    
                    // Update product count if there's a count element
                    this.updateProductCount(compatibleIds.length);
                },
                
                showAllProductsOnPage() {
                    // Show all products that were hidden by the filter
                    const hiddenProducts = document.querySelectorAll('.ymm-hidden');
                    hiddenProducts.forEach(element => {
                        element.style.display = '';
                        element.classList.remove('ymm-hidden');
                    });
                    
                    // Reset product count
                    this.updateProductCount(null);
                },
                
                extractProductIdFromElement(element) {
                    // Try various methods to extract product ID from the element
                    // Adjust these selectors based on your BigCommerce theme
                    
                    // Check for data attributes
                    const dataAttributes = ['data-product-id', 'data-entity-id', 'data-product', 'data-id'];
                    for (const attr of dataAttributes) {
                        const value = element.getAttribute(attr);
                        if (value) return value;
                    }
                    
                    // Check for ID in href attributes
                    const linkElement = element.querySelector('a[href*="/products/"]');
                    if (linkElement) {
                        const href = linkElement.getAttribute('href');
                        const match = href.match(/\/products\/[^\/]*\/(\d+)/);
                        if (match) return match[1];
                    }
                    
                    // Check for ID in form action
                    const formElement = element.querySelector('form[action*="/cart.php"]');
                    if (formElement) {
                        const action = formElement.getAttribute('action');
                        const match = action.match(/product_id=(\d+)/);
                        if (match) return match[1];
                    }
                    
                    return null;
                },
                
                updateProductCount(count) {
                    // Update product count display if it exists on the page
                    const countElements = document.querySelectorAll('.product-count, .item-count, .result-count');
                    if (count !== null) {
                        countElements.forEach(element => {
                            element.textContent = `${count} item${count !== 1 ? 's' : ''}`;
                        });
                    } else {
                        // Reset to original count if available
                        countElements.forEach(element => {
                            const originalCount = element.getAttribute('data-original-count');
                            if (originalCount) {
                                element.textContent = originalCount;
                            }
                        });
                    }
                }
            }
        }).mount('#ymm-filter-app');
    </script>
</body>
</html>
