<!-- YMM Vehicle Filter Widget -->
<div id="ymm-filter-widget" class="ymm-filter-widget">
    <div class="ymm-filter-container">
        <h3 class="ymm-filter-title">{{ settings.widget_title }}</h3>
        <p class="ymm-filter-description">{{ settings.default_message }}</p>
        
        <div class="ymm-filter-form">
            <div class="ymm-filter-row">
                <div class="ymm-filter-field">
                    <label for="ymm-year">Year:</label>
                    <select id="ymm-year" v-model="selectedYear" @change="onYearChange">
                        <option value="">Select Year</option>
                        <option v-for="year in availableYears" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
                
                <div class="ymm-filter-field">
                    <label for="ymm-make">Make:</label>
                    <select id="ymm-make" v-model="selectedMake" @change="onMakeChange" :disabled="!selectedYear">
                        <option value="">Select Make</option>
                        <option v-for="make in availableMakes" :key="make" :value="make">
                            {{ make }}
                        </option>
                    </select>
                </div>
                
                <div class="ymm-filter-field">
                    <label for="ymm-model">Model:</label>
                    <select id="ymm-model" v-model="selectedModel" @change="onModelChange" :disabled="!selectedMake">
                        <option value="">Select Model</option>
                        <option v-for="model in availableModels" :key="model" :value="model">
                            {{ model }}
                        </option>
                    </select>
                </div>
                
                <div class="ymm-filter-actions">
                    <button type="button" @click="filterProducts" :disabled="!canFilter" class="ymm-filter-button">
                        Filter Products
                    </button>
                    <button type="button" @click="clearFilter" v-if="hasActiveFilter" class="ymm-clear-button">
                        Clear Filter
                    </button>
                </div>
            </div>
        </div>
        
        <div v-if="loading" class="ymm-filter-loading">
            <div class="ymm-spinner"></div>
            <span>Finding compatible products...</span>
        </div>
        
        <div v-if="filterResult" class="ymm-filter-result">
            <p v-if="filterResult.count > 0" class="ymm-result-message">
                Found {{ filterResult.count }} compatible product{{ filterResult.count !== 1 ? 's' : '' }} 
                for {{ selectedYear }} {{ selectedMake }} {{ selectedModel }}
            </p>
            <p v-else class="ymm-no-results">
                No compatible products found for {{ selectedYear }} {{ selectedMake }} {{ selectedModel }}
            </p>
        </div>
    </div>
</div>

<style>
.ymm-filter-widget {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.ymm-filter-title {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.ymm-filter-description {
    margin: 0 0 20px 0;
    color: #666;
    font-size: 14px;
}

.ymm-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: end;
}

.ymm-filter-field {
    flex: 1;
    min-width: 150px;
}

.ymm-filter-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.ymm-filter-field select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background-color: white;
    font-size: 14px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.ymm-filter-field select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.ymm-filter-field select:disabled {
    background-color: #e9ecef;
    opacity: 0.6;
    cursor: not-allowed;
}

.ymm-filter-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ymm-filter-button, .ymm-clear-button {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
    white-space: nowrap;
}

.ymm-filter-button {
    background-color: #007bff;
    color: white;
}

.ymm-filter-button:hover:not(:disabled) {
    background-color: #0056b3;
}

.ymm-filter-button:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.ymm-clear-button {
    background-color: #6c757d;
    color: white;
    font-size: 12px;
    padding: 6px 12px;
}

.ymm-clear-button:hover {
    background-color: #545b62;
}

.ymm-filter-loading {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
    color: #666;
    font-size: 14px;
}

.ymm-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.ymm-filter-result {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
}

.ymm-result-message {
    color: #155724;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    padding: 10px;
    border-radius: 4px;
    margin: 0;
}

.ymm-no-results {
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 10px;
    border-radius: 4px;
    margin: 0;
}

/* Responsive design */
@media (max-width: 768px) {
    .ymm-filter-row {
        flex-direction: column;
    }
    
    .ymm-filter-field {
        min-width: unset;
    }
    
    .ymm-filter-actions {
        flex-direction: row;
        justify-content: space-between;
    }
}
</style>

<script>
// YMM Filter Widget JavaScript
(function() {
    'use strict';
    
    const YMMFilter = {
        data() {
            return {
                selectedYear: '',
                selectedMake: '',
                selectedModel: '',
                availableYears: [],
                availableMakes: [],
                availableModels: [],
                loading: false,
                filterResult: null,
                settings: {
                    widget_title: 'Vehicle Compatibility',
                    default_message: 'Select your vehicle to view compatible products'
                }
            };
        },
        
        computed: {
            canFilter() {
                return this.selectedYear && this.selectedMake && this.selectedModel;
            },
            
            hasActiveFilter() {
                return this.filterResult !== null;
            }
        },
        
        async mounted() {
            await this.loadSettings();
            await this.loadAvailableYears();
        },
        
        methods: {
            async loadSettings() {
                try {
                    const response = await fetch('/api/settings');
                    if (response.ok) {
                        this.settings = await response.json();
                    }
                } catch (error) {
                    console.error('Error loading settings:', error);
                }
            },
            
            async loadAvailableYears() {
                try {
                    const response = await fetch('/api/vehicles');
                    if (response.ok) {
                        const vehicles = await response.json();
                        this.availableYears = [...new Set(vehicles.map(v => v.year))]
                            .sort((a, b) => b - a);
                    }
                } catch (error) {
                    console.error('Error loading years:', error);
                }
            },
            
            async onYearChange() {
                this.selectedMake = '';
                this.selectedModel = '';
                this.availableMakes = [];
                this.availableModels = [];
                this.filterResult = null;
                
                if (this.selectedYear) {
                    try {
                        const response = await fetch(`/api/vehicles/year/${this.selectedYear}/makes`);
                        if (response.ok) {
                            this.availableMakes = await response.json();
                        }
                    } catch (error) {
                        console.error('Error loading makes:', error);
                    }
                }
            },
            
            async onMakeChange() {
                this.selectedModel = '';
                this.availableModels = [];
                this.filterResult = null;
                
                if (this.selectedYear && this.selectedMake) {
                    try {
                        const response = await fetch(`/api/vehicles/year/${this.selectedYear}/make/${encodeURIComponent(this.selectedMake)}/models`);
                        if (response.ok) {
                            this.availableModels = await response.json();
                        }
                    } catch (error) {
                        console.error('Error loading models:', error);
                    }
                }
            },
            
            onModelChange() {
                this.filterResult = null;
            },
            
            async filterProducts() {
                if (!this.canFilter) return;
                
                this.loading = true;
                this.filterResult = null;
                
                try {
                    const params = new URLSearchParams({
                        year: this.selectedYear,
                        make: this.selectedMake,
                        model: this.selectedModel
                    });
                    
                    const response = await fetch(`/api/products/compatible?${params}`);
                    if (response.ok) {
                        this.filterResult = await response.json();
                        
                        // Hide products that aren't compatible
                        this.applyProductFilter(this.filterResult.product_ids);
                    }
                } catch (error) {
                    console.error('Error filtering products:', error);
                    alert('Error filtering products. Please try again.');
                } finally {
                    this.loading = false;
                }
            },
            
            clearFilter() {
                this.selectedYear = '';
                this.selectedMake = '';
                this.selectedModel = '';
                this.availableMakes = [];
                this.availableModels = [];
                this.filterResult = null;
                
                // Show all products again
                this.applyProductFilter([]);
            },
            
            applyProductFilter(compatibleProductIds) {
                // Find all product elements on the page
                const productElements = document.querySelectorAll('[data-product-id]');
                
                if (compatibleProductIds.length === 0) {
                    // Show all products
                    productElements.forEach(element => {
                        element.style.display = '';
                    });
                } else {
                    // Hide incompatible products
                    productElements.forEach(element => {
                        const productId = element.getAttribute('data-product-id');
                        if (compatibleProductIds.includes(parseInt(productId))) {
                            element.style.display = '';
                        } else {
                            element.style.display = 'none';
                        }
                    });
                }
                
                // Trigger a custom event for other scripts to listen to
                const event = new CustomEvent('ymmFilterApplied', {
                    detail: {
                        year: this.selectedYear,
                        make: this.selectedMake,
                        model: this.selectedModel,
                        compatibleProductIds: compatibleProductIds,
                        totalProducts: compatibleProductIds.length
                    }
                });
                document.dispatchEvent(event);
            }
        }
    };
    
    // Initialize the widget when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeWidget);
    } else {
        initializeWidget();
    }
    
    function initializeWidget() {
        const widgetElement = document.getElementById('ymm-filter-widget');
        if (widgetElement && typeof Vue !== 'undefined') {
            Vue.createApp(YMMFilter).mount('#ymm-filter-widget');
        }
    }
    
    // Fallback for browsers without Vue.js
    if (typeof Vue === 'undefined') {
        console.warn('Vue.js is required for YMM Filter Widget functionality');
    }
})();
</script>
