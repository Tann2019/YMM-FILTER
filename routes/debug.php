<?php

Route::get('/debug-env', function() {
    $mainController = new \App\Http\Controllers\MainController();
    
    return response()->json([
        'env_direct' => [
            'APP_ENV' => env('APP_ENV'),
            'BC_LOCAL_STORE_HASH' => env('BC_LOCAL_STORE_HASH'),
            'BC_LOCAL_CLIENT_ID' => env('BC_LOCAL_CLIENT_ID'),
            'BC_LOCAL_ACCESS_TOKEN' => env('BC_LOCAL_ACCESS_TOKEN'),
        ],
        'config_values' => [
            'APP_ENV' => config('app.env'),
            'BC_LOCAL_STORE_HASH' => config('bigcommerce.local.store_hash'),
            'BC_LOCAL_CLIENT_ID' => config('bigcommerce.local.client_id'),
            'BC_LOCAL_ACCESS_TOKEN' => config('bigcommerce.local.access_token'),
        ],
        'controller_methods' => [
            'getAppClientId' => $mainController->getAppClientId(),
            'getStoreHash' => $mainController->getStoreHash(request()),
            'getAccessToken' => $mainController->getAccessToken(request()),
        ]
    ]);
});

// Simple test endpoint that BigCommerce can hit
Route::get('/test-bc-access', function() {
    \Log::info("=== BIGCOMMERCE TEST ACCESS ===");
    \Log::info("Request method: " . request()->method());
    \Log::info("Request URL: " . request()->fullUrl());
    \Log::info("Request headers: " . json_encode(request()->headers->all()));
    \Log::info("User agent: " . request()->userAgent());
    \Log::info("IP address: " . request()->ip());
    \Log::info("===============================");
    
    return response()->json([
        'status' => 'success',
        'message' => 'BigCommerce can access this endpoint',
        'timestamp' => now()->toISOString()
    ]);
});

// Test the exact auth endpoint that BigCommerce is trying to reach
Route::get('/test-auth-endpoint', function() {
    \Log::info("=== TESTING AUTH ENDPOINT ===");
    \Log::info("Request method: " . request()->method());
    \Log::info("Request URL: " . request()->fullUrl());
    \Log::info("Request headers: " . json_encode(request()->headers->all()));
    \Log::info("Query params: " . json_encode(request()->query->all()));
    \Log::info("==============================");
    
    return response()->json([
        'status' => 'success',
        'message' => 'Auth endpoint is accessible',
        'query_params' => request()->query->all(),
        'timestamp' => now()->toISOString()
    ]);
});
