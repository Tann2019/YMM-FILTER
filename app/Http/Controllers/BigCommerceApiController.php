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
        
        // Option 2: From session (for admin app calls) - with safety check
        if (!$storeHash || !$accessToken) {
            try {
                if ($request->hasSession() && $request->session()->isStarted()) {
                    $storeHash = $request->session()->get('store_hash');
                    $accessToken = $request->session()->get('access_token');
                }
            } catch (\Exception $e) {
                // Session not available, continue with other options
                \Log::info('Session not available for API request: ' . $e->getMessage());
            }
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
        
        // Option 4: Try to find from database using store hash
        if ($storeHash && !$accessToken) {
            $store = BigCommerceStore::where('store_hash', $storeHash)->first();
            if ($store) {
                $accessToken = $store->access_token;
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
     * Get all products with their custom fields for YMM filtering (with pagination)
     */
    public function getProductsWithYmmFields(Request $request): JsonResponse
    {
        $credentials = $this->getApiCredentials($request);
        
        if (!$credentials['store_hash'] || !$credentials['access_token']) {
            return response()->json(['error' => 'Store credentials not found'], 401);
        }

        $page = (int) $request->input('page', 1);
        $limit = min((int) $request->input('limit', 50), 250); // Cap at 250
        
        try {
            // Cache key for this page
            $cacheKey = "products_with_ymm_{$credentials['store_hash']}_page_{$page}_limit_{$limit}";
            
            $result = Cache::remember($cacheKey, 300, function () use ($credentials, $page, $limit) {
                // Get products with pagination
                $productsResponse = Http::withHeaders([
                    'X-Auth-Token' => $credentials['access_token'],
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])->get($credentials['api_url'] . '/catalog/products', [
                    'limit' => $limit,
                    'page' => $page,
                    'include_fields' => 'id,name,sku,price,inventory_level,images'
                ]);

                if (!$productsResponse->successful()) {
                    return ['error' => 'Failed to fetch products', 'status' => $productsResponse->status()];
                }

                $responseData = $productsResponse->json();
                $products = $responseData['data'];
                $meta = $responseData['meta']['pagination'] ?? [];
                
                $productsWithYmm = [];

                // Batch process products for better performance
                $productIds = array_column($products, 'id');
                $customFieldsBatch = $this->getCustomFieldsBatch($credentials, $productIds);

                foreach ($products as $product) {
                    $productId = $product['id'];
                    
                    // Get YMM fields for this product
                    $ymmData = [];
                    if (isset($customFieldsBatch[$productId])) {
                        foreach ($customFieldsBatch[$productId] as $field) {
                            if (str_starts_with($field['name'], 'ymm_')) {
                                $ymmData[$field['name']] = $field['value'];
                            }
                        }
                    }

                    // Only include products that have YMM data
                    if (!empty($ymmData)) {
                        $product['ymm_data'] = $ymmData;
                        $productsWithYmm[] = $product;
                    }
                }

                return [
                    'data' => $productsWithYmm,
                    'pagination' => $meta,
                    'total_products' => count($products),
                    'ymm_products' => count($productsWithYmm)
                ];
            });

            if (isset($result['error'])) {
                return response()->json(['error' => $result['error']], $result['status'] ?? 500);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Error fetching products with YMM fields: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch product data'], 500);
        }
    }

    /**
     * Batch fetch custom fields for multiple products (more efficient)
     */
    private function getCustomFieldsBatch($credentials, $productIds): array
    {
        $customFieldsBatch = [];
        
        // Process in smaller batches to avoid rate limits
        $batches = array_chunk($productIds, 10);
        
        foreach ($batches as $batch) {
            foreach ($batch as $productId) {
                try {
                    $customFieldsResponse = Http::timeout(10)->withHeaders([
                        'X-Auth-Token' => $credentials['access_token'],
                        'Accept' => 'application/json'
                    ])->get($credentials['api_url'] . "/catalog/products/{$productId}/custom-fields");

                    if ($customFieldsResponse->successful()) {
                        $customFieldsBatch[$productId] = $customFieldsResponse->json()['data'];
                    }
                    
                    // Small delay to respect rate limits
                    usleep(100000); // 0.1 second delay
                    
                } catch (\Exception $e) {
                    \Log::warning("Failed to fetch custom fields for product {$productId}: " . $e->getMessage());
                    $customFieldsBatch[$productId] = [];
                }
            }
        }
        
        return $customFieldsBatch;
    }

    /**
     * Get compatible products based on YMM selection using custom fields (with pagination)
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

        $page = (int) $request->input('page', 1);
        $limit = min((int) $request->input('limit', 50), 250);

        try {
            // Cache key for this search
            $cacheKey = "compatible_products_{$credentials['store_hash']}_{$year}_{$make}_{$model}_page_{$page}_limit_{$limit}";
            
            $result = Cache::remember($cacheKey, 300, function () use ($credentials, $year, $make, $model, $page, $limit) {
                // Get products with pagination
                $productsResponse = Http::timeout(30)->withHeaders([
                    'X-Auth-Token' => $credentials['access_token'],
                    'Accept' => 'application/json'
                ])->get($credentials['api_url'] . '/catalog/products', [
                    'limit' => $limit,
                    'page' => $page,
                    'include_fields' => 'id,name,sku,price,inventory_level,images,custom_url'
                ]);

                if (!$productsResponse->successful()) {
                    return ['error' => 'Failed to fetch products', 'status' => $productsResponse->status()];
                }

                $responseData = $productsResponse->json();
                $products = $responseData['data'];
                $meta = $responseData['meta']['pagination'] ?? [];

                $compatibleProducts = [];
                $productIds = array_column($products, 'id');
                $customFieldsBatch = $this->getCustomFieldsBatch($credentials, $productIds);

                foreach ($products as $product) {
                    $productId = $product['id'];
                    
                    // Get YMM fields for this product
                    $ymmData = [];
                    if (isset($customFieldsBatch[$productId])) {
                        foreach ($customFieldsBatch[$productId] as $field) {
                            if (str_starts_with($field['name'], 'ymm_')) {
                                $ymmData[$field['name']] = $field['value'];
                            }
                        }
                    }

                    // Check compatibility
                    if (!empty($ymmData) && $this->isProductCompatible($ymmData, $year, $make, $model)) {
                        // Add YMM data to product for reference
                        $product['ymm_data'] = $ymmData;
                        $compatibleProducts[] = $product;
                    }
                }

                return [
                    'products' => $compatibleProducts,
                    'pagination' => $meta,
                    'count' => count($compatibleProducts),
                    'total_checked' => count($products)
                ];
            });

            if (isset($result['error'])) {
                return response()->json(['error' => $result['error']], $result['status'] ?? 500);
            }

            return response()->json([
                'data' => $result['products'],
                'pagination' => $result['pagination'],
                'count' => $result['count'],
                'total_checked' => $result['total_checked'],
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
     * Helper method to get all products with YMM data (with pagination support)
     */
    private function getAllProductsWithYmmData($credentials): array
    {
        $allProductsWithYmm = [];
        $page = 1;
        $limit = 50; // Smaller batch size for better performance
        
        do {
            try {
                $productsResponse = Http::timeout(30)->withHeaders([
                    'X-Auth-Token' => $credentials['access_token'],
                    'Accept' => 'application/json'
                ])->get($credentials['api_url'] . '/catalog/products', [
                    'limit' => $limit,
                    'page' => $page
                ]);

                if (!$productsResponse->successful()) {
                    break;
                }

                $responseData = $productsResponse->json();
                $products = $responseData['data'];
                $meta = $responseData['meta']['pagination'] ?? [];
                
                if (empty($products)) {
                    break;
                }

                // Get custom fields for this batch
                $productIds = array_column($products, 'id');
                $customFieldsBatch = $this->getCustomFieldsBatch($credentials, $productIds);

                foreach ($products as $product) {
                    $productId = $product['id'];
                    
                    // Get YMM fields for this product
                    $ymmData = [];
                    if (isset($customFieldsBatch[$productId])) {
                        foreach ($customFieldsBatch[$productId] as $field) {
                            if (str_starts_with($field['name'], 'ymm_')) {
                                $ymmData[$field['name']] = $field['value'];
                            }
                        }
                    }

                    if (!empty($ymmData)) {
                        $product['ymm_data'] = $ymmData;
                        $allProductsWithYmm[] = $product;
                    }
                }

                // Check if there are more pages
                $hasNextPage = isset($meta['current_page']) && isset($meta['total_pages']) 
                    && $meta['current_page'] < $meta['total_pages'];
                    
                if (!$hasNextPage) {
                    break;
                }
                
                $page++;
                
                // Limit to prevent infinite loops
                if ($page > 20) { // Max 1000 products (20 * 50)
                    \Log::warning("getAllProductsWithYmmData: Reached page limit for store {$credentials['store_hash']}");
                    break;
                }
                
            } catch (\Exception $e) {
                \Log::error("Error fetching products page {$page}: " . $e->getMessage());
                break;
            }
            
        } while (true);

        return $allProductsWithYmm;
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
