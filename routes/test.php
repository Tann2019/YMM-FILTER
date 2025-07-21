<?php

use Illuminate\Support\Facades\Route;
use App\Services\BigCommerceService;
use App\Models\BigCommerceStore;

Route::get('/test-bc-api', function () {
    try {
        $storeHash = 'rgp5uxku7h';
        $store = BigCommerceStore::where('store_hash', $storeHash)->first();

        if (!$store) {
            return ['error' => 'Store not found'];
        }

        $service = new BigCommerceService();
        $result = $service->makeApiCall($storeHash, '/catalog/products?limit=5');

        return [
            'success' => true,
            'store_hash' => $storeHash,
            'store_found' => true,
            'api_result' => $result
        ];
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
    }
});
