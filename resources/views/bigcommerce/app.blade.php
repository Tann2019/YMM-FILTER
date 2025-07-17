<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YMM Filter - BigCommerce App</title>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [v-cloak] { display: none; }
    </style>
</head>
<body class="bg-gray-50">
    <div id="app" v-cloak class="min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-6 py-4">
                    <h1 class="text-2xl font-bold text-gray-900">YMM Filter Configuration</h1>
                    <p class="text-gray-600 mt-2">Manage Year, Make, Model filters for your products</p>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="mb-8">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <button @click="activeTab = 'products'" 
                            :class="['whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                   activeTab === 'products' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                        Products & Vehicles
                    </button>
                    <button @click="activeTab = 'vehicles'" 
                            :class="['whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                   activeTab === 'vehicles' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                        Vehicle Database
                    </button>
                    <button @click="activeTab = 'settings'" 
                            :class="['whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                   activeTab === 'settings' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']">
                        Widget Settings
                    </button>
                </nav>
            </div>

            <!-- Products & Vehicles Tab -->
            <div v-if="activeTab === 'products'" class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Product Vehicle Associations</h2>
                    <p class="text-sm text-gray-600 mt-1">Associate your products with compatible vehicles</p>
                </div>
                <div class="p-6">
                    <div v-if="loading" class="text-center py-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
                        <p class="mt-2 text-gray-500">Loading products...</p>
                    </div>
                    <div v-else>
                        <!-- Products List -->
                        <div class="space-y-4">
                            <div v-for="product in products" :key="product.id" 
                                 class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">@{{ product.name }}</h3>
                                        <p class="text-sm text-gray-500">SKU: @{{ product.sku || 'No SKU' }}</p>
                                        <p class="text-sm text-gray-500">ID: @{{ product.id }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <button @click="configureProduct(product)" 
                                                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                            Configure Vehicles
                                        </button>
                                    </div>
                                </div>
                                <!-- Vehicle associations for this product -->
                                <div v-if="product.vehicles && product.vehicles.length > 0" class="mt-4 pt-4 border-t border-gray-100">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Compatible Vehicles:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="vehicle in product.vehicles" :key="vehicle.id"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            @{{ vehicle.year }} @{{ vehicle.make }} @{{ vehicle.model }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Database Tab -->
            <div v-if="activeTab === 'vehicles'" class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Vehicle Database</h2>
                    <p class="text-sm text-gray-600 mt-1">Manage the vehicle database for filtering</p>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <button @click="showAddVehicle = true" 
                                class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                            Add Vehicle
                        </button>
                    </div>
                    
                    <!-- Vehicles List -->
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Make</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="vehicle in vehicles" :key="vehicle.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">@{{ vehicle.year }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">@{{ vehicle.make }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">@{{ vehicle.model }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button @click="deleteVehicle(vehicle.id)" 
                                                class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Widget Settings Tab -->
            <div v-if="activeTab === 'settings'" class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Widget Settings</h2>
                    <p class="text-sm text-gray-600 mt-1">Configure how the YMM filter appears on your storefront</p>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Widget Title</label>
                            <input v-model="settings.widgetTitle" type="text" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default Message</label>
                            <textarea v-model="settings.defaultMessage" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        
                        <div>
                            <button @click="saveSettings" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Vehicle Modal -->
        <div v-if="showAddVehicle" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Vehicle</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Year</label>
                            <input v-model="newVehicle.year" type="number" min="1900" max="2030"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Make</label>
                            <input v-model="newVehicle.make" type="text"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Model</label>
                            <input v-model="newVehicle.model" type="text"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button @click="showAddVehicle = false" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                            Cancel
                        </button>
                        <button @click="addVehicle" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Add Vehicle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    activeTab: 'products',
                    loading: true,
                    products: [],
                    vehicles: [],
                    showAddVehicle: false,
                    newVehicle: {
                        year: '',
                        make: '',
                        model: ''
                    },
                    settings: {
                        widgetTitle: 'Vehicle Compatibility',
                        defaultMessage: 'Select your vehicle to view compatible products'
                    }
                };
            },
            mounted() {
                this.loadData();
            },
            methods: {
                async loadData() {
                    this.loading = true;
                    try {
                        // Load products from BigCommerce
                        const productsResponse = await fetch('/bc-api/v3/catalog/products?limit=50');
                        const productsData = await productsResponse.json();
                        this.products = productsData.data || [];

                        // Load vehicles from our database
                        const vehiclesResponse = await fetch('/api/vehicles');
                        const vehiclesData = await vehiclesResponse.json();
                        this.vehicles = vehiclesData;

                        // Load product-vehicle associations
                        // We'll implement this next
                        
                    } catch (error) {
                        console.error('Error loading data:', error);
                        alert('Error loading data. Please try again.');
                    } finally {
                        this.loading = false;
                    }
                },
                configureProduct(product) {
                    // TODO: Open modal to configure vehicle associations for this product
                    alert(`Configure vehicles for: ${product.name}`);
                },
                async addVehicle() {
                    if (!this.newVehicle.year || !this.newVehicle.make || !this.newVehicle.model) {
                        alert('Please fill in all fields');
                        return;
                    }

                    try {
                        const response = await fetch('/api/vehicles', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.newVehicle)
                        });

                        if (response.ok) {
                            const newVehicle = await response.json();
                            this.vehicles.push(newVehicle);
                            this.newVehicle = { year: '', make: '', model: '' };
                            this.showAddVehicle = false;
                        } else {
                            alert('Error adding vehicle');
                        }
                    } catch (error) {
                        console.error('Error adding vehicle:', error);
                        alert('Error adding vehicle. Please try again.');
                    }
                },
                async deleteVehicle(vehicleId) {
                    if (!confirm('Are you sure you want to delete this vehicle?')) {
                        return;
                    }

                    try {
                        const response = await fetch(`/api/vehicles/${vehicleId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        if (response.ok) {
                            this.vehicles = this.vehicles.filter(v => v.id !== vehicleId);
                        } else {
                            alert('Error deleting vehicle');
                        }
                    } catch (error) {
                        console.error('Error deleting vehicle:', error);
                        alert('Error deleting vehicle. Please try again.');
                    }
                },
                async saveSettings() {
                    try {
                        const response = await fetch('/api/settings', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.settings)
                        });

                        if (response.ok) {
                            alert('Settings saved successfully!');
                        } else {
                            alert('Error saving settings');
                        }
                    } catch (error) {
                        console.error('Error saving settings:', error);
                        alert('Error saving settings. Please try again.');
                    }
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
