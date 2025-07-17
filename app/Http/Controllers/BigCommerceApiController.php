<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\BigCommerceStore;

class BigCommerceApiController extends Controller
{
    private function getApiCredentials(Request $request)
    {
        // Multi-shop support: Try to get credentials from multiple sources
        
        // Option 1: From request parameters (for widget calls)
        $storeHash = $request->input('store_hash') ?: $request->header('X-Store-Hash');
        $accessToken = $request->input('access_token') ?: $request->header('X-Access-Token');
        
        // Option 2: From session (for admin app calls)
        if (!$storeHash || !$accessToken) {
            $storeHash = $request->session()->get('store_hash');
            $accessToken = $request->session()->get('access_token');
        }
        
        // Option 3: From database lookup using store identifier
        if (!$storeHash || !$accessToken) {
            $storeId = $request->input('store_id') ?: $request->header('X-Store-Id');
            if ($storeId) {
                // Look up store credentials from database
                $storeCredentials = $this->getStoredCredentials($storeId);
                if ($storeCredentials) {
                    $storeHash = $storeCredentials['store_hash'];
                    $accessToken = $storeCredentials['access_token'];
                }
            }
        }
        
        return [
            'store_hash' => $storeHash,
            'access_token' => $accessToken,
            'api_url' => $storeHash ? "https://api.bigcommerce.com/stores/{$storeHash}/v3" : null
        ];
    }

    /**
     * Get stored credentials for a store (implement database storage)
     */
    private function getStoredCredentials($storeId)
    {
        // Look up store by hash or ID
        $store = BigCommerceStore::where('store_hash', $storeId)
            ->orWhere('id', $storeId)
            ->where('active', true)
            ->first();

        if ($store) {
            $store->updateLastAccessed();
            return [
                'store_hash' => $store->store_hash,
                'access_token' => $store->access_token
            ];
        }

        return null;
    }

    /**
     * Get all products with their custom fields for YMM filtering
     */
    public function getProductsWithYmmFields(Request $request): JsonResponse
    {
        $credentials = $this->getApiCredentials($request);
        
        if (!$credentials['store_hash'] || !$credentials['access_token']) {
            return response()->json(['error' => 'Store credentials not found'], 401);
        }

        try {
            // Get all products
            $productsResponse = Http::withHeaders([
                'X-Auth-Token' => $credentials['access_token'],
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->get($credentials['api_url'] . '/catalog/products', [
                'limit' => 250,
                'include_fields' => 'id,name,sku,price,inventory_level'
            ]);

            if (!$productsResponse->successful()) {
                return response()->json(['error' => 'Failed to fetch products'], 500);
            }

            $products = $productsResponse->json()['data'];
            $productsWithYmm = [];

            // For each product, get its custom fields
            foreach ($products as $product) {
                $customFieldsResponse = Http::withHeaders([
                    'X-Auth-Token' => $credentials['access_token'],
                    'Accept' => 'application/json'
                ])->get($credentials['api_url'] . "/catalog/products/{$product['id']}/custom-fields");

                if ($customFieldsResponse->successful()) {
                    $customFields = $customFieldsResponse->json()['data'];
                    
                    // Extract YMM fields
                    $ymmData = [];
                    foreach ($customFields as $field) {
                        if (str_starts_with($field['name'], 'ymm_')) {
                            $ymmData[$field['name']] = $field['value'];
                        }
                    }

                    // Only include products that have YMM data
                    if (!empty($ymmData)) {
                        $product['ymm_data'] = $ymmData;
                        $productsWithYmm[] = $product;
                    }
                }
            }

            return response()->json($productsWithYmm);

        } catch (\Exception $e) {
            \Log::error('Error fetching products with YMM fields: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch product data'], 500);
        }
    }

    /**
     * Get compatible products based on YMM selection using custom fields
     */
    public function getCompatibleProductsByCustomFields(Request $request): JsonResponse
    {
        $year = $request->input('year');
        $make = $request->input('make');
        $model = $request->input('model');

        if (!$year || !$make || !$model) {
            return response()->json(['error' => 'Year, make, and model are required'], 400);
        }

        $credentials = $this->getApiCredentials($request);
        
        if (!$credentials['store_hash'] || !$credentials['access_token']) {
            return response()->json(['error' => 'Store credentials not found'], 401);
        }

        try {
            // Cache key for this search
            $cacheKey = "compatible_products_{$credentials['store_hash']}_{$year}_{$make}_{$model}";
            
            $compatibleProducts = Cache::remember($cacheKey, 300, function () use ($credentials, $year, $make, $model) {
                // Get all products
                $productsResponse = Http::withHeaders([
                    'X-Auth-Token' => $credentials['access_token'],
                    'Accept' => 'application/json'
                ])->get($credentials['api_url'] . '/catalog/products', [
                    'limit' => 250,
                    'include_fields' => 'id,name,sku,price,inventory_level,images'
                ]);

                if (!$productsResponse->successful()) {
                    return [];
                }

                $products = $productsResponse->json()['data'];
                $compatibleProducts = [];

                foreach ($products as $product) {
                    // Get custom fields for this product
                    $customFieldsResponse = Http::withHeaders([
                        'X-Auth-Token' => $credentials['access_token'],
                        'Accept' => 'application/json'
                    ])->get($credentials['api_url'] . "/catalog/products/{$product['id']}/custom-fields");

                    if ($customFieldsResponse->successful()) {
                        $customFields = $customFieldsResponse->json()['data'];
                        
                        $ymmData = [];
                        foreach ($customFields as $field) {
                            if (str_starts_with($field['name'], 'ymm_')) {
                                $ymmData[$field['name']] = $field['value'];
                            }
                        }

                        // Check compatibility
                        if ($this->isProductCompatible($ymmData, $year, $make, $model)) {
                            $compatibleProducts[] = $product;
                        }
                    }
                }

                return $compatibleProducts;
            });

            return response()->json([
                'products' => $compatibleProducts,
                'count' => count($compatibleProducts),
                'filters' => [
                    'year' => $year,
                    'make' => $make,
                    'model' => $model
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting compatible products: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch compatible products'], 500);
        }
    }

    /**
     * Check if a product is compatible with the selected vehicle
     */
    private function isProductCompatible($ymmData, $selectedYear, $selectedMake, $selectedModel): bool
    {
        // Check make (case-insensitive)
        if (isset($ymmData['ymm_make'])) {
            if (strtolower($ymmData['ymm_make']) !== strtolower($selectedMake)) {
                return false;
            }
        }

        // Check model (case-insensitive)
        if (isset($ymmData['ymm_model'])) {
            if (strtolower($ymmData['ymm_model']) !== strtolower($selectedModel)) {
                return false;
            }
        }

        // Check year range
        $yearStart = isset($ymmData['ymm_year_start']) ? (int)$ymmData['ymm_year_start'] : null;
        $yearEnd = isset($ymmData['ymm_year_end']) ? (int)$ymmData['ymm_year_end'] : null;
        $selectedYearInt = (int)$selectedYear;

        if ($yearStart && $selectedYearInt < $yearStart) {
            return false;
        }

        if ($yearEnd && $selectedYearInt > $yearEnd) {
            return false;
        }

        return true;
    }

    /**
     * Get unique makes from all products with YMM data
     */
    public function getAvailableMakes(Request $request): JsonResponse
    {
        $credentials = $this->getApiCredentials($request);
        
        if (!$credentials['store_hash'] || !$credentials['access_token']) {
            return response()->json(['error' => 'Store credentials not found'], 401);
        }
        
        $cacheKey = "available_makes_{$credentials['store_hash']}";
        
        try {
            $makes = Cache::remember($cacheKey, 3600, function () use ($credentials) {
                $products = $this->getAllProductsWithYmmData($credentials);
                $makes = [];

                foreach ($products as $product) {
                    if (isset($product['ymm_data']['ymm_make'])) {
                        $make = $product['ymm_data']['ymm_make'];
                        if (!in_array($make, $makes)) {
                            $makes[] = $make;
                        }
                    }
                }

                sort($makes);
                return $makes;
            });

            return response()->json($makes);
        } catch (\Exception $e) {
            \Log::error('Error getting available makes: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch makes'], 500);
        }
    }

    /**
     * Get unique models for a specific make
     */
    public function getAvailableModels(Request $request): JsonResponse
    {
        $make = $request->input('make');
        
        if (!$make) {
            return response()->json(['error' => 'Make is required'], 400);
        }

        $credentials = $this->getApiCredentials($request);
        
        if (!$credentials['store_hash'] || !$credentials['access_token']) {
            return response()->json(['error' => 'Store credentials not found'], 401);
        }
        
        $cacheKey = "available_models_{$credentials['store_hash']}_" . strtolower($make);
        
        try {
            $models = Cache::remember($cacheKey, 3600, function () use ($credentials, $make) {
                $products = $this->getAllProductsWithYmmData($credentials);
                $models = [];

                foreach ($products as $product) {
                    if (isset($product['ymm_data']['ymm_make']) && 
                        strtolower($product['ymm_data']['ymm_make']) === strtolower($make) &&
                        isset($product['ymm_data']['ymm_model'])) {
                        
                        $model = $product['ymm_data']['ymm_model'];
                        if (!in_array($model, $models)) {
                            $models[] = $model;
                        }
                    }
                }

                sort($models);
                return $models;
            });

            return response()->json($models);
        } catch (\Exception $e) {
            \Log::error('Error getting available models: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch models'], 500);
        }
    }

    /**
     * Get available years for a specific make and model
     */
    public function getAvailableYears(Request $request): JsonResponse
    {
        $make = $request->input('make');
        $model = $request->input('model');
        
        if (!$make || !$model) {
            return response()->json(['error' => 'Make and model are required'], 400);
        }

        $credentials = $this->getApiCredentials($request);
        
        $cacheKey = "available_years_{$credentials['store_hash']}_" . strtolower($make) . "_" . strtolower($model);
        
        $years = Cache::remember($cacheKey, 3600, function () use ($credentials, $make, $model) {
            $products = $this->getAllProductsWithYmmData($credentials);
            $years = [];

            foreach ($products as $product) {
                if (isset($product['ymm_data']['ymm_make']) && 
                    strtolower($product['ymm_data']['ymm_make']) === strtolower($make) &&
                    isset($product['ymm_data']['ymm_model']) &&
                    strtolower($product['ymm_data']['ymm_model']) === strtolower($model)) {
                    
                    $yearStart = isset($product['ymm_data']['ymm_year_start']) ? (int)$product['ymm_data']['ymm_year_start'] : null;
                    $yearEnd = isset($product['ymm_data']['ymm_year_end']) ? (int)$product['ymm_data']['ymm_year_end'] : null;

                    if ($yearStart && $yearEnd) {
                        for ($year = $yearStart; $year <= $yearEnd; $year++) {
                            if (!in_array($year, $years)) {
                                $years[] = $year;
                            }
                        }
                    }
                }
            }

            sort($years);
            return $years;
        });

        return response()->json($years);
    }

    /**
     * Helper method to get all products with YMM data
     */
    private function getAllProductsWithYmmData($credentials): array
    {
        $productsResponse = Http::withHeaders([
            'X-Auth-Token' => $credentials['access_token'],
            'Accept' => 'application/json'
        ])->get($credentials['api_url'] . '/catalog/products', [
            'limit' => 250
        ]);

        if (!$productsResponse->successful()) {
            return [];
        }

        $products = $productsResponse->json()['data'];
        $productsWithYmm = [];

        foreach ($products as $product) {
            $customFieldsResponse = Http::withHeaders([
                'X-Auth-Token' => $credentials['access_token'],
                'Accept' => 'application/json'
            ])->get($credentials['api_url'] . "/catalog/products/{$product['id']}/custom-fields");

            if ($customFieldsResponse->successful()) {
                $customFields = $customFieldsResponse->json()['data'];
                
                $ymmData = [];
                foreach ($customFields as $field) {
                    if (str_starts_with($field['name'], 'ymm_')) {
                        $ymmData[$field['name']] = $field['value'];
                    }
                }

                if (!empty($ymmData)) {
                    $product['ymm_data'] = $ymmData;
                    $productsWithYmm[] = $product;
                }
            }
        }

        return $productsWithYmm;
    }

    /**
     * Get compatibility information for a specific product
     */
    public function getProductCompatibilityInfo($productId): JsonResponse
    {
        $request = request();
        $credentials = $this->getApiCredentials($request);
        
        if (!$credentials['store_hash'] || !$credentials['access_token']) {
            return response()->json(['error' => 'Store credentials not found'], 401);
        }

        try {
            // Get custom fields for this product
            $customFieldsResponse = Http::withHeaders([
                'X-Auth-Token' => $credentials['access_token'],
                'Accept' => 'application/json'
            ])->get($credentials['api_url'] . "/catalog/products/{$productId}/custom-fields");

            if (!$customFieldsResponse->successful()) {
                return response()->json(['error' => 'Failed to fetch product custom fields'], 500);
            }

            $customFields = $customFieldsResponse->json()['data'];
            
            $ymmData = [];
            foreach ($customFields as $field) {
                if (str_starts_with($field['name'], 'ymm_')) {
                    $ymmData[$field['name']] = $field['value'];
                }
            }

            if (empty($ymmData)) {
                return response()->json(['compatibility' => null]);
            }

            // Format compatibility string
            $compatibility = '';
            
            if (isset($ymmData['ymm_year_start']) && isset($ymmData['ymm_year_end'])) {
                if ($ymmData['ymm_year_start'] === $ymmData['ymm_year_end']) {
                    $compatibility .= $ymmData['ymm_year_start'];
                } else {
                    $compatibility .= $ymmData['ymm_year_start'] . '-' . $ymmData['ymm_year_end'];
                }
            } elseif (isset($ymmData['ymm_year_start'])) {
                $compatibility .= $ymmData['ymm_year_start'] . '+';
            }

            if (isset($ymmData['ymm_make'])) {
                $compatibility .= ' ' . $ymmData['ymm_make'];
            }

            if (isset($ymmData['ymm_model'])) {
                $compatibility .= ' ' . $ymmData['ymm_model'];
            }

            return response()->json([
                'compatibility' => trim($compatibility),
                'ymm_data' => $ymmData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting product compatibility info: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch compatibility info'], 500);
        }
    }
}
