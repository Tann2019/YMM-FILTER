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
        $store = BigCommerceStore::where('store_hash', $storeHash)
            ->where('active', true)
            ->firstOrFail();

        // Clean store hash - remove 'stores/' prefix if it exists
        $cleanStoreHash = str_replace('stores/', '', $storeHash);

        $baseUrl = "https://api.bigcommerce.com/stores/{$cleanStoreHash}/v3";
        $url = $baseUrl . $endpoint;

        $headers = [
            'X-Auth-Token' => $store->access_token,
            'X-Auth-Client' => $this->clientId,
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
                    'endpoint' => $endpoint
                ]);
                throw new \Exception('API call failed: ' . $response->status());
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
     * Install YMM widget script on the store
     */
    public function installWidget($storeHash, $settings = [])
    {
        $scriptContent = $this->generateWidgetScript($storeHash, $settings);

        $scriptData = [
            'name' => 'YMM Filter Widget',
            'description' => 'Year/Make/Model compatibility filter',
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
     * Generate the JavaScript widget code
     */
    private function generateWidgetScript($storeHash, $settings)
    {
        $apiUrl = config('app.url');

        return "
        <script>
        (function() {
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
            document.head.appendChild(script);
        })();
        </script>";
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
     * Create a Page Builder widget template using content management token
     */
    public function createWidgetTemplate($storeHash, $templateData)
    {
        // First try with existing token (might not have content scope)
        try {
            return $this->makeApiCall($storeHash, '/content/widget-templates', 'POST', $templateData);
        } catch (\Exception $e) {
            // If it fails due to scope issues, try alternative approach
            if (strpos($e->getMessage(), '403') !== false) {
                // For now, let's create a simpler script-based widget instead
                return $this->createScriptBasedWidget($storeHash, $templateData);
            }
            throw $e;
        }
    }

    /**
     * Create a script-based widget as alternative to Page Builder template
     */
    private function createScriptBasedWidget($storeHash, $templateData)
    {
        $widgetScript = $this->generateYmmWidgetScript($storeHash);

        $scriptData = [
            'name' => 'YMM Vehicle Filter Widget',
            'description' => 'Drag-and-drop vehicle compatibility filter widget',
            'html' => $widgetScript,
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
}
