<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\BigCommerceStore;

class BigCommerceService
{
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->clientId = config('bigcommerce.app.client_id');
        $this->clientSecret = config('bigcommerce.app.secret');
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken($code, $context, $scope)
    {
        $response = Http::post('https://login.bigcommerce.com/oauth2/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'scope' => $scope,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('app.url') . '/auth',
            'context' => $context
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to get access token: ' . $response->body());
    }

    /**
     * Verify BigCommerce installation payload
     */
    public function verifyInstallPayload(Request $request)
    {
        $payload = $request->all();

        // Verify the signature (BigCommerce security requirement)
        if (!$this->verifySignature($request)) {
            throw new \Exception('Invalid signature');
        }

        return $payload;
    }

    /**
     * Verify BigCommerce load payload
     */
    public function verifyLoadPayload(Request $request)
    {
        $signedPayload = $request->query('signed_payload_jwt');

        if (!$signedPayload) {
            throw new \Exception('Missing signed_payload_jwt');
        }

        // Decode the JWT (header.payload.signature format)
        $parts = explode('.', $signedPayload);
        if (count($parts) !== 3) {
            throw new \Exception('Invalid JWT format - expected 3 parts');
        }

        [$header, $payload, $signature] = $parts;

        // In local development, skip signature verification
        if (!app()->environment('local')) {
            // For production, implement proper JWT signature verification
            // This requires verifying with BigCommerce's public key
            Log::warning('JWT signature verification not implemented for production');
        }

        // Decode the payload (second part of JWT)
        $decodedPayload = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload));
        $payloadData = json_decode($decodedPayload, true);

        if (!$payloadData) {
            throw new \Exception('Could not decode JWT payload');
        }

        // Extract store hash from the 'sub' claim (format: stores/{hash})
        $subject = $payloadData['sub'] ?? '';
        preg_match('/stores\/([a-z0-9]+)/', $subject, $matches);
        $storeHash = $matches[1] ?? null;

        if (!$storeHash) {
            throw new \Exception('Could not extract store hash from JWT subject: ' . $subject);
        }

        // Add store hash to payload for easier access
        $payloadData['store_hash'] = $storeHash;
        $payloadData['context'] = $subject; // Keep for backwards compatibility

        return $payloadData;
    }
    /**
     * Verify BigCommerce uninstall payload
     */
    public function verifyUninstallPayload(Request $request)
    {
        $payload = $request->all();

        if (!$this->verifySignature($request)) {
            throw new \Exception('Invalid signature');
        }

        return $payload;
    }

    /**
     * Verify the BigCommerce signature
     */
    private function verifySignature(Request $request)
    {
        $data = $request->getContent();
        $webhook_signature = $request->header('X-Auth-Client');

        // For development, you might want to skip signature verification
        if (app()->environment('local')) {
            return true;
        }

        // Implement proper signature verification for production
        $calculated_signature = base64_encode(hash_hmac('sha256', $data, $this->clientSecret, true));

        return hash_equals($calculated_signature, $webhook_signature);
    }

    /**
     * Make API call to BigCommerce
     */
    public function makeApiCall($storeHash, $endpoint, $method = 'GET', $data = [])
    {
        // Try to get store from database first
        $store = BigCommerceStore::where('store_hash', $storeHash)
            ->where('active', true)
            ->first();

        // If store not found in database, try using local config
        if (!$store && config('bigcommerce.local.store_hash') === $storeHash) {
            $accessToken = config('bigcommerce.local.access_token');
            $clientId = config('bigcommerce.local.client_id');
        } else if ($store) {
            $accessToken = $store->access_token;
            $clientId = $this->clientId;
        } else {
            throw new \Exception("Store not found: {$storeHash}");
        }

        // Clean store hash - remove 'stores/' prefix if it exists
        $cleanStoreHash = str_replace('stores/', '', $storeHash);

        $baseUrl = "https://api.bigcommerce.com/stores/{$cleanStoreHash}/v3";
        $url = $baseUrl . $endpoint;

        $headers = [
            'X-Auth-Token' => $accessToken,
            'X-Auth-Client' => $clientId ?? $this->clientId,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        try {
            $response = Http::withHeaders($headers);

            switch (strtoupper($method)) {
                case 'GET':
                    $response = $response->get($url, $data);
                    break;
                case 'POST':
                    $response = $response->post($url, $data);
                    break;
                case 'PUT':
                    $response = $response->put($url, $data);
                    break;
                case 'DELETE':
                    $response = $response->delete($url);
                    break;
                default:
                    throw new \Exception('Unsupported HTTP method');
            }

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('BigCommerce API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'endpoint' => $endpoint,
                    'headers' => $headers
                ]);
                throw new \Exception('API call failed: ' . $response->status() . ' - ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('BigCommerce API Exception', [
                'message' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);
            throw $e;
        }
    }

    /**
     * Get all products from BigCommerce store
     */
    public function getProducts($storeHash, $page = 1, $limit = 50)
    {
        return $this->makeApiCall($storeHash, "/catalog/products?page={$page}&limit={$limit}");
    }

    /**
     * Get a specific product
     */
    public function getProduct($storeHash, $productId)
    {
        return $this->makeApiCall($storeHash, "/catalog/products/{$productId}");
    }

    /**
     * Update product custom fields (for YMM data)
     */
    public function updateProductCustomFields($storeHash, $productId, $customFields)
    {
        return $this->makeApiCall($storeHash, "/catalog/products/{$productId}/custom-fields", 'POST', $customFields);
    }

    /**
     * Get product custom fields
     */
    public function getProductCustomFields($storeHash, $productId)
    {
        return $this->makeApiCall($storeHash, "/catalog/products/{$productId}/custom-fields");
    }

    /**
     * Create or update YMM custom fields for a product
     */
    public function setProductYmmData($storeHash, $productId, $ymmData)
    {
        // Custom field structure for YMM data
        $customFields = [
            [
                'name' => 'ymm_compatibility',
                'value' => json_encode($ymmData)
            ]
        ];

        return $this->updateProductCustomFields($storeHash, $productId, $customFields);
    }

    /**
     * Install YMM widget template for Page Builder
     */
    public function installWidget($storeHash, $settings = [])
    {
        // Generate a unique widget name with timestamp - using safe characters only
        $timestamp = date('Y-m-d_H-i-s');
        $widgetNumber = $this->getNextWidgetNumber($storeHash);
        
        $templateData = [
            'name' => "YMM_Filter_Widget_{$widgetNumber}_{$timestamp}",
            'template' => $this->generateWidgetTemplate($storeHash),
            'schema' => $this->generateWidgetSchema(),
            'kind' => 'custom'
        ];

        return $this->makeApiCall($storeHash, '/content/widget-templates', 'POST', $templateData);
    }

    /**
     * Install YMM widget as script tag (alternative method)
     */
    public function installWidgetAsScript($storeHash, $settings = [])
    {
        // Generate a unique widget name with timestamp - using safe characters only
        $timestamp = date('Y-m-d_H-i-s');
        $widgetNumber = $this->getNextWidgetNumber($storeHash);
        
        $scriptContent = $this->generateWidgetScript($storeHash, $settings);

        $scriptData = [
            'name' => "YMM_Filter_Script_{$widgetNumber}_{$timestamp}",
            'description' => "Year/Make/Model compatibility filter script - Created: {$timestamp}",
            'html' => $scriptContent,
            'src' => null,
            'auto_uninstall' => true,
            'load_method' => 'default',
            'location' => 'footer',
            'visibility' => 'storefront',
            'kind' => 'script_tag'
        ];

        return $this->makeApiCall($storeHash, '/content/scripts', 'POST', $scriptData);
    }

    /**
     * Generate the widget template for Page Builder
     */
    private function generateWidgetTemplate($storeHash)
    {
        $apiUrl = rtrim(config('app.url'), '/');
        
        return '
        <div class="ymm-filter-widget-{{_.id}}" style="max-width: {{widget_width}}px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: {{background_color}};">
            <h3 style="margin-top: 0; color: {{text_color}};">{{widget_title}}</h3>
            <div class="ymm-form">
                <select id="ymm-year-{{_.id}}" style="width: 100%; margin-bottom: 10px; padding: 8px;">
                    <option value="">Select Year</option>
                </select>
                <select id="ymm-make-{{_.id}}" style="width: 100%; margin-bottom: 10px; padding: 8px;" disabled>
                    <option value="">Select Make</option>
                </select>
                <select id="ymm-model-{{_.id}}" style="width: 100%; margin-bottom: 10px; padding: 8px;" disabled>
                    <option value="">Select Model</option>
                </select>
                <button id="ymm-search-{{_.id}}" style="width: 100%; padding: 10px; background: {{button_color}}; color: white; border: none; border-radius: 4px; cursor: pointer;" disabled>
                    {{button_text}}
                </button>
            </div>
            <div id="ymm-results-{{_.id}}" style="margin-top: 20px;"></div>
        </div>

        <script>
        (function() {
            const widgetId = "{{_.id}}";
            const apiUrl = "' . $apiUrl . '";
            const storeHash = window.location.hostname.split(".")[0];
            
            console.log("YMM Widget Debug Info:");
            console.log("Widget ID:", widgetId);
            console.log("API URL:", apiUrl);
            console.log("Store Hash:", storeHash);
            console.log("Full API endpoint example:", apiUrl + "/api/ymm/" + storeHash + "/years");
            
            function initializeYmmWidget() {
                console.log("Initializing YMM Widget", widgetId);
                loadYears();
                
                const yearSelect = document.getElementById("ymm-year-" + widgetId);
                const makeSelect = document.getElementById("ymm-make-" + widgetId);
                const modelSelect = document.getElementById("ymm-model-" + widgetId);
                const searchButton = document.getElementById("ymm-search-" + widgetId);
                
                if (!yearSelect || !makeSelect || !modelSelect || !searchButton) {
                    console.error("YMM Widget elements not found");
                    return;
                }
                
                yearSelect.addEventListener("change", function() {
                    console.log("Year changed to:", this.value);
                    if (this.value) {
                        loadMakes(this.value);
                        makeSelect.disabled = false;
                    } else {
                        resetSelect(makeSelect, "Select Make");
                        resetSelect(modelSelect, "Select Model");
                        searchButton.disabled = true;
                    }
                });
                
                makeSelect.addEventListener("change", function() {
                    console.log("Make changed to:", this.value);
                    if (this.value) {
                        loadModels(yearSelect.value, this.value);
                        modelSelect.disabled = false;
                    } else {
                        resetSelect(modelSelect, "Select Model");
                        searchButton.disabled = true;
                    }
                });
                
                modelSelect.addEventListener("change", function() {
                    console.log("Model changed to:", this.value);
                    searchButton.disabled = !this.value;
                });
                
                searchButton.addEventListener("click", function() {
                    console.log("Search button clicked");
                    searchProducts();
                });
            }
            
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
                        const select = document.getElementById("ymm-year-" + widgetId);
                        if (select) {
                            select.innerHTML = "<option value=\\"\\">Select Year</option>";
                            years.forEach(year => {
                                const option = document.createElement("option");
                                option.value = year;
                                option.textContent = year;
                                select.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error("Error loading years:", error);
                        console.error("Full error details:", {
                            message: error.message,
                            stack: error.stack,
                            url: url
                        });
                        showError("Failed to load years. Please check console for details.");
                    });
            }
            
            function loadMakes(year) {
                const url = apiUrl + "/api/ymm/" + storeHash + "/makes?year=" + year;
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
                        const select = document.getElementById("ymm-make-" + widgetId);
                        if (select) {
                            select.innerHTML = "<option value=\\"\\">Select Make</option>";
                            makes.forEach(make => {
                                const option = document.createElement("option");
                                option.value = make;
                                option.textContent = make;
                                select.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error("Error loading makes:", error);
                        showError("Failed to load makes for " + year);
                    });
            }
            
            function loadModels(year, make) {
                const url = apiUrl + "/api/ymm/" + storeHash + "/models?year=" + year + "&make=" + encodeURIComponent(make);
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
                        const select = document.getElementById("ymm-model-" + widgetId);
                        if (select) {
                            select.innerHTML = "<option value=\\"\\">Select Model</option>";
                            models.forEach(model => {
                                const option = document.createElement("option");
                                option.value = model;
                                option.textContent = model;
                                select.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error("Error loading models:", error);
                        showError("Failed to load models for " + year + " " + make);
                    });
            }
            
            function searchProducts() {
                const yearSelect = document.getElementById("ymm-year-" + widgetId);
                const makeSelect = document.getElementById("ymm-make-" + widgetId);
                const modelSelect = document.getElementById("ymm-model-" + widgetId);
                
                if (!yearSelect || !makeSelect || !modelSelect) {
                    console.error("Widget elements not found for search");
                    return;
                }
                
                const year = yearSelect.value;
                const make = makeSelect.value;
                const model = modelSelect.value;
                
                console.log("Searching for products:", { year, make, model });
                
                if (!year || !make || !model) {
                    showError("Please select year, make, and model");
                    return;
                }
                
                const resultsDiv = document.getElementById("ymm-results-" + widgetId);
                if (resultsDiv) {
                    resultsDiv.innerHTML = "<p>Searching compatible products...</p>";
                }
                
                const url = apiUrl + "/api/ymm/" + storeHash + "/search?year=" + year + "&make=" + encodeURIComponent(make) + "&model=" + encodeURIComponent(model);
                console.log("Search URL:", url);
                
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
                        console.log("Search results:", data);
                        displayResults(data);
                    })
                    .catch(error => {
                        console.error("Error searching products:", error);
                        showError("Error searching products. Please try again.");
                    });
            }
            
            function displayResults(data) {
                const resultsDiv = document.getElementById("ymm-results-" + widgetId);
                if (!resultsDiv) return;
                
                if (data.products && data.products.length > 0) {
                    let html = "<h4>Compatible Products (" + data.products.length + " found):</h4><div class=\\"ymm-products\\">";
                    data.products.forEach(product => {
                        const productUrl = product.custom_url?.url || "#";
                        const productPrice = product.calculated_price || product.price || "N/A";
                        html += `
                            <div style="border-bottom: 1px solid #eee; padding: 10px 0;">
                                <h5 style="margin: 0 0 5px 0;"><a href="${productUrl}" style="color: #007cba; text-decoration: none;">${product.name}</a></h5>
                                <p style="margin: 0; color: #666; font-size: 14px;">Price: $${productPrice}</p>
                            </div>
                        `;
                    });
                    html += "</div>";
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = "<p>No compatible products found for this vehicle combination.</p>";
                }
            }
            
            function resetSelect(selectElement, placeholder) {
                if (selectElement) {
                    selectElement.innerHTML = "<option value=\\"\\\">" + placeholder + "</option>";
                    selectElement.disabled = true;
                }
            }
            
            function showError(message) {
                const resultsDiv = document.getElementById("ymm-results-" + widgetId);
                if (resultsDiv) {
                    resultsDiv.innerHTML = "<p style=\\"color: red;\\">" + message + "</p>";
                }
            }
            
            // Initialize when DOM is ready
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initializeYmmWidget);
            } else {
                initializeYmmWidget();
            }
        })();
        </script>';
    }

    /**
     * Generate the widget schema for Page Builder configuration
     */
    private function generateWidgetSchema()
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
                                'id' => 'widget_title',
                                'default' => 'Find Compatible Products',
                                'typeMeta' => (object) [
                                    'placeholder' => 'Enter widget title'
                                ]
                            ],
                            [
                                'type' => 'input',
                                'label' => 'Search Button Text',
                                'id' => 'button_text',
                                'default' => 'Search Compatible Products',
                                'typeMeta' => (object) [
                                    'placeholder' => 'Enter button text'
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
                                'type' => 'color',
                                'label' => 'Background Color',
                                'id' => 'background_color',
                                'default' => '#f9f9f9'
                            ],
                            [
                                'type' => 'color',
                                'label' => 'Text Color',
                                'id' => 'text_color',
                                'default' => '#333333'
                            ],
                            [
                                'type' => 'color',
                                'label' => 'Button Color',
                                'id' => 'button_color',
                                'default' => '#007cba'
                            ],
                            [
                                'type' => 'range',
                                'label' => 'Widget Width',
                                'id' => 'widget_width',
                                'default' => 400,
                                'typeMeta' => (object) [
                                    'min' => 200,
                                    'max' => 800,
                                    'step' => 10
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    private function getNextWidgetNumber($storeHash)
    {
        try {
            $scripts = $this->getScripts($storeHash);
            $templates = $this->getWidgetTemplates($storeHash);
            $ymmWidgets = 0;
            
            // Count scripts
            if (isset($scripts['data'])) {
                foreach ($scripts['data'] as $script) {
                    if (stripos($script['name'], 'YMM_Filter_Widget') !== false || 
                        stripos($script['name'], 'YMM Filter Widget') !== false ||
                        stripos($script['name'], 'YMM_Filter_Script') !== false) {
                        $ymmWidgets++;
                    }
                }
            }
            
            // Count widget templates
            if (isset($templates['data'])) {
                foreach ($templates['data'] as $template) {
                    if (stripos($template['name'], 'YMM_Filter_Widget') !== false || 
                        stripos($template['name'], 'YMM Filter Widget') !== false) {
                        $ymmWidgets++;
                    }
                }
            }
            
            return $ymmWidgets + 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Generate the JavaScript widget code
     */
    private function generateWidgetScript($storeHash, $settings)
    {
        $apiUrl = rtrim(config('app.url'), '/'); // Remove trailing slash to prevent double slashes

        return "
        <script>
        (function() {
            // Debug logging
            console.log('YMM Widget initialized with:', {
                storeHash: '{$storeHash}',
                apiUrl: '{$apiUrl}',
                settings: " . json_encode($settings) . "
            });
            
            // YMM Filter Widget
            const YMM_CONFIG = {
                storeHash: '{$storeHash}',
                apiUrl: '{$apiUrl}',
                settings: " . json_encode($settings) . "
            };
            
            // Load YMM widget
            const script = document.createElement('script');
            script.src = '{$apiUrl}/js/ymm-widget.js';
            script.async = true;
            script.onload = function() {
                console.log('YMM Widget script loaded successfully');
            };
            script.onerror = function() {
                console.error('Failed to load YMM Widget script');
                // Fallback: create widget inline
                window.YMM_CONFIG = YMM_CONFIG;
                createYmmWidgetInline();
            };
            document.head.appendChild(script);
            
            // Inline widget creation as fallback
            function createYmmWidgetInline() {
                " . $this->getInlineWidgetCode($storeHash, $apiUrl) . "
            }
        })();
        </script>";
    }

    /**
     * Get inline widget code with hardcoded store hash
     */
    private function getInlineWidgetCode($storeHash, $apiUrl)
    {
        return "
            // Create YMM widget HTML
            function createYmmWidget() {
                const container = document.getElementById('ymm-widget-container') || 
                                document.querySelector('.ymm-widget-container') ||
                                createWidgetContainer();
                
                if (!container) return;
                
                container.innerHTML = `
                    <div class='ymm-filter-widget' style='max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;'>
                        <h3 style='margin-top: 0;'>Find Compatible Products</h3>
                        <div class='ymm-form'>
                            <select id='ymm-year' style='width: 100%; margin-bottom: 10px; padding: 8px;'>
                                <option value=''>Select Year</option>
                            </select>
                            <select id='ymm-make' style='width: 100%; margin-bottom: 10px; padding: 8px;' disabled>
                                <option value=''>Select Make</option>
                            </select>
                            <select id='ymm-model' style='width: 100%; margin-bottom: 10px; padding: 8px;' disabled>
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
            
            // Create container if it doesn't exist
            function createWidgetContainer() {
                const container = document.createElement('div');
                container.id = 'ymm-widget-container';
                // Try to find a good place to insert it
                const target = document.querySelector('.page-content') || 
                              document.querySelector('main') || 
                              document.body;
                target.appendChild(container);
                return container;
            }
            
            // Initialize widget functionality
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
            
            // Load years - HARDCODED STORE HASH
            function loadYears() {
                console.log('Loading years from:', '{$apiUrl}/api/ymm/{$storeHash}/years');
                fetch('{$apiUrl}/api/ymm/{$storeHash}/years', {
                    headers: {
                        'ngrok-skip-browser-warning': 'true'
                    }
                })
                    .then(response => {
                        console.log('Years response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(years => {
                        console.log('Years loaded:', years);
                        const select = document.getElementById('ymm-year');
                        years.forEach(year => {
                            const option = document.createElement('option');
                            option.value = year;
                            option.textContent = year;
                            select.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading years:', error);
                        // Show error to user
                        const select = document.getElementById('ymm-year');
                        select.innerHTML = '<option value=\"\">Error loading years</option>';
                    });
            }
            
            // Load makes - HARDCODED STORE HASH
            function loadMakes(year) {
                console.log('Loading makes from:', '{$apiUrl}/api/ymm/{$storeHash}/makes?year=' + year);
                fetch('{$apiUrl}/api/ymm/{$storeHash}/makes?year=' + year, {
                    headers: {
                        'ngrok-skip-browser-warning': 'true'
                    }
                })
                    .then(response => {
                        console.log('Makes response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(makes => {
                        console.log('Makes loaded:', makes);
                        const select = document.getElementById('ymm-make');
                        select.innerHTML = '<option value=\"\">Select Make</option>';
                        makes.forEach(make => {
                            const option = document.createElement('option');
                            option.value = make;
                            option.textContent = make;
                            select.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading makes:', error);
                        const select = document.getElementById('ymm-make');
                        select.innerHTML = '<option value=\"\">Error loading makes</option>';
                    });
            }
            
            // Load models - HARDCODED STORE HASH
            function loadModels(year, make) {
                console.log('Loading models from:', '{$apiUrl}/api/ymm/{$storeHash}/models?year=' + year + '&make=' + encodeURIComponent(make));
                fetch('{$apiUrl}/api/ymm/{$storeHash}/models?year=' + year + '&make=' + encodeURIComponent(make), {
                    headers: {
                        'ngrok-skip-browser-warning': 'true'
                    }
                })
                    .then(response => {
                        console.log('Models response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(models => {
                        console.log('Models loaded:', models);
                        const select = document.getElementById('ymm-model');
                        select.innerHTML = '<option value=\"\">Select Model</option>';
                        models.forEach(model => {
                            const option = document.createElement('option');
                            option.value = model;
                            option.textContent = model;
                            select.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading models:', error);
                        const select = document.getElementById('ymm-model');
                        select.innerHTML = '<option value=\"\">Error loading models</option>';
                    });
            }
            
            // Search products - HARDCODED STORE HASH
            function searchProducts() {
                const year = document.getElementById('ymm-year').value;
                const make = document.getElementById('ymm-make').value;
                const model = document.getElementById('ymm-model').value;
                
                if (!year || !make || !model) return;
                
                const resultsDiv = document.getElementById('ymm-results');
                resultsDiv.innerHTML = '<p>Searching compatible products...</p>';
                
                const searchUrl = '{$apiUrl}/api/ymm/{$storeHash}/search?year=' + year + '&make=' + encodeURIComponent(make) + '&model=' + encodeURIComponent(model);
                console.log('Searching products from:', searchUrl);
                
                fetch(searchUrl, {
                    headers: {
                        'ngrok-skip-browser-warning': 'true'
                    }
                })
                    .then(response => {
                        console.log('Search response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Search results:', data);
                        if (data.products && data.products.length > 0) {
                            let html = '<h4>Compatible Products:</h4><div class=\"ymm-products\">';
                            data.products.forEach(product => {
                                html += `
                                    <div style='border-bottom: 1px solid #eee; padding: 10px 0;'>
                                        <h5 style='margin: 0 0 5px 0;'><a href='\${product.custom_url?.url || \"#\"}' style='color: #007cba; text-decoration: none;'>\${product.name}</a></h5>
                                        <p style='margin: 0; color: #666; font-size: 14px;'>$\${product.calculated_price}</p>
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
            
            // Reset select element
            function resetSelect(id) {
                const select = document.getElementById(id);
                select.innerHTML = '<option value=\"\">Select ' + id.split('-')[1].charAt(0).toUpperCase() + id.split('-')[1].slice(1) + '</option>';
                select.disabled = true;
                document.getElementById('ymm-search').disabled = true;
            }
            
            // Initialize when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', createYmmWidget);
            } else {
                createYmmWidget();
            }
        ";
    }

    /**
     * Create a storefront API token with content management scopes
     */
    public function createStorefrontApiToken($storeHash, $allowedCorsOrigins = [])
    {
        $tokenData = [
            'allowed_cors_origins' => $allowedCorsOrigins ?: [config('app.url')],
            'expires_at' => now()->addYears(1)->timestamp, // Token expires in 1 year
            'channels' => [] // Empty array means all channels
        ];

        return $this->makeApiCall($storeHash, '/storefront/api-token', 'POST', $tokenData);
    }

    /**
     * Create a Page Builder widget template with hardcoded API URL and store hash
     */
    public function createWidgetTemplate($storeHash, $templateData)
    {
        // Get the app URL for hardcoding into the widget
        $apiUrl = rtrim(config('app.url'), '/');
        
        // If we have a WidgetController template method, get the template with hardcoded values
        if (method_exists('App\Http\Controllers\WidgetController', 'getWidgetTemplate')) {
            $widgetController = new \App\Http\Controllers\WidgetController($this);
            $reflection = new \ReflectionClass($widgetController);
            $method = $reflection->getMethod('getWidgetTemplate');
            $method->setAccessible(true);
            $templateData['template'] = $method->invoke($widgetController, $apiUrl, $storeHash);
        }
        
        // First try with existing token (might not have content scope)
        try {
            $response = $this->makeApiCall($storeHash, '/content/widget-templates', 'POST', $templateData);
            Log::info('Successfully created Page Builder widget template', [
                'store_hash' => $storeHash,
                'template_name' => $templateData['name']
            ]);
            return $response;
        } catch (\Exception $e) {
            // If it fails due to scope issues, try alternative approach
            if (strpos($e->getMessage(), '403') !== false || strpos($e->getMessage(), 'scope') !== false) {
                Log::info('Page Builder widget creation failed due to scope, falling back to script widget', [
                    'store_hash' => $storeHash,
                    'error' => $e->getMessage()
                ]);
                // Create a script-based widget instead with hardcoded values
                return $this->createScriptBasedWidget($storeHash, $templateData, $apiUrl);
            }
            throw $e;
        }
    }

    /**
     * Create a script-based widget as alternative to Page Builder template
     */
    private function createScriptBasedWidget($storeHash, $templateData, $apiUrl = null)
    {
        $apiUrl = $apiUrl ?: rtrim(config('app.url'), '/');
        
        // Use the template from templateData if available, otherwise generate one
        $widgetHtml = isset($templateData['template']) ? $templateData['template'] : $this->generateYmmWidgetScript($storeHash);

        $scriptData = [
            'name' => $templateData['name'] ?? 'YMM Vehicle Filter Widget',
            'description' => 'Drag-and-drop vehicle compatibility filter widget with hardcoded API endpoints',
            'html' => $widgetHtml,
            'src' => null,
            'auto_uninstall' => true,
            'load_method' => 'default',
            'location' => 'footer',
            'visibility' => 'storefront',
            'kind' => 'script_tag'
        ];

        $result = $this->makeApiCall($storeHash, '/content/scripts', 'POST', $scriptData);

        // Return format similar to widget template for consistency
        return [
            'data' => [
                'uuid' => $result['data']['uuid'] ?? uniqid(),
                'name' => $scriptData['name'],
                'template' => $widgetScript,
                'schema' => [],
                'kind' => 'script_widget'
            ]
        ];
    }

    /**
     * Generate YMM widget script with embedded functionality
     */
    private function generateYmmWidgetScript($storeHash)
    {
        $apiUrl = config('app.url');

        return "
        <!-- YMM Vehicle Filter Widget -->
        <div id='ymm-widget-container'></div>
        <script>
        (function() {
            const YMM_CONFIG = {
                storeHash: '{$storeHash}',
                apiUrl: '{$apiUrl}',
                containerId: 'ymm-widget-container'
            };
            
            // Create YMM widget HTML
            function createYmmWidget() {
                const container = document.getElementById(YMM_CONFIG.containerId);
                if (!container) return;
                
                container.innerHTML = `
                    <div class='ymm-filter-widget' style='max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9;'>
                        <h3 style='margin-top: 0;'>Find Compatible Products</h3>
                        <div class='ymm-form'>
                            <select id='ymm-year' style='width: 100%; margin-bottom: 10px; padding: 8px;'>
                                <option value=''>Select Year</option>
                            </select>
                            <select id='ymm-make' style='width: 100%; margin-bottom: 10px; padding: 8px;' disabled>
                                <option value=''>Select Make</option>
                            </select>
                            <select id='ymm-model' style='width: 100%; margin-bottom: 10px; padding: 8px;' disabled>
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
            
            // Initialize widget functionality
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
            
            // Load years
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
            
            // Load makes
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
            
            // Load models
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
            
            // Search products
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
                                html += `
                                    <div style='border-bottom: 1px solid #eee; padding: 10px 0;'>
                                        <h5 style='margin: 0 0 5px 0;'><a href='\${product.custom_url?.url || \"#\"}' style='color: #007cba; text-decoration: none;'>\${product.name}</a></h5>
                                        <p style='margin: 0; color: #666; font-size: 14px;'>$\${product.calculated_price}</p>
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
            
            // Reset select element
            function resetSelect(id) {
                const select = document.getElementById(id);
                select.innerHTML = '<option value=\"\">Select ' + id.split('-')[1].charAt(0).toUpperCase() + id.split('-')[1].slice(1) + '</option>';
                select.disabled = true;
                document.getElementById('ymm-search').disabled = true;
            }
            
            // Initialize when DOM is ready
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
     * Update a Page Builder widget template
     */
    public function updateWidgetTemplate($storeHash, $templateId, $templateData)
    {
        return $this->makeApiCall($storeHash, "/content/widget-templates/{$templateId}", 'PUT', $templateData);
    }

    /**
     * Get Page Builder widget templates
     */
    public function getWidgetTemplates($storeHash)
    {
        return $this->makeApiCall($storeHash, '/content/widget-templates');
    }

    /**
     * Delete a Page Builder widget template
     */
    public function deleteWidgetTemplate($storeHash, $templateId)
    {
        return $this->makeApiCall($storeHash, "/content/widget-templates/{$templateId}", 'DELETE');
    }

    /**
     * Get all scripts installed on the store
     */
    public function getScripts($storeHash)
    {
        return $this->makeApiCall($storeHash, '/content/scripts');
    }

    /**
     * Delete a script from the store
     */
    public function deleteScript($storeHash, $scriptId)
    {
        return $this->makeApiCall($storeHash, "/content/scripts/{$scriptId}", 'DELETE');
    }

    /**
     * Remove all YMM widgets (scripts and templates)
     */
    public function removeAllYmmWidgets($storeHash)
    {
        $results = ['scripts' => [], 'templates' => []];

        // Remove scripts
        try {
            $scripts = $this->getScripts($storeHash);
            if (isset($scripts['data'])) {
                foreach ($scripts['data'] as $script) {
                    if (stripos($script['name'], 'YMM') !== false || 
                        stripos($script['description'], 'YMM') !== false ||
                        stripos($script['name'], 'Vehicle Filter') !== false) {
                        $deleteResult = $this->deleteScript($storeHash, $script['uuid']);
                        $results['scripts'][] = [
                            'id' => $script['uuid'],
                            'name' => $script['name'],
                            'deleted' => $deleteResult !== false
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove YMM scripts: ' . $e->getMessage());
        }

        // Remove widget templates
        try {
            $templates = $this->getWidgetTemplates($storeHash);
            if (isset($templates['data'])) {
                foreach ($templates['data'] as $template) {
                    if (stripos($template['name'], 'YMM') !== false || 
                        stripos($template['name'], 'Vehicle Filter') !== false) {
                        $deleteResult = $this->deleteWidgetTemplate($storeHash, $template['uuid']);
                        $results['templates'][] = [
                            'id' => $template['uuid'],
                            'name' => $template['name'],
                            'deleted' => $deleteResult !== false
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove YMM widget templates: ' . $e->getMessage());
        }

        return $results;
    }
}
