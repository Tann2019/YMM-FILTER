<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ProductVehicleController;
use App\Http\Controllers\BigCommerceApiController;
use App\Http\Controllers\Api\YmmApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Vehicle management routes
Route::apiResource('vehicles', VehicleController::class);

// Additional vehicle lookup routes
Route::get('vehicles/year/{year}/makes', [VehicleController::class, 'getMakesByYear']);
Route::get('vehicles/year/{year}/make/{make}/models', [VehicleController::class, 'getModelsByYearAndMake']);

// YMM Widget API routes (public, no auth required)
Route::middleware(['widget.cors'])->prefix('ymm/{storeHash}')->group(function () {
    Route::get('years', [YmmApiController::class, 'getYears']);
    Route::get('makes', [YmmApiController::class, 'getMakes']);
    Route::get('models', [YmmApiController::class, 'getModels']);
    Route::get('search', [YmmApiController::class, 'searchCompatibleProducts']);
    Route::get('config', [YmmApiController::class, 'getWidgetConfig']);
    Route::get('health', [YmmApiController::class, 'healthCheck']);
});

// Product-Vehicle association routes
Route::get('products/{productId}/vehicles', [ProductVehicleController::class, 'getProductVehicles']);
Route::post('products/{productId}/vehicles', [ProductVehicleController::class, 'associateVehicles']);
Route::delete('products/{productId}/vehicles/{vehicleId}', [ProductVehicleController::class, 'dissociateVehicle']);

// Product compatibility routes
Route::get('products/compatible', [ProductVehicleController::class, 'getCompatibleProducts']);

// Import/Export routes
Route::post('vehicles/import', [VehicleController::class, 'import']);
Route::post('product-vehicles/import', [ProductVehicleController::class, 'importAssociations']);
Route::get('product-vehicles/export', [ProductVehicleController::class, 'exportAssociations']);

// Settings routes
Route::post('settings', [ProductVehicleController::class, 'saveSettings']);
Route::get('settings', [ProductVehicleController::class, 'getSettings']);

// BigCommerce Custom Fields API routes (stateless, no session required)
Route::middleware(['widget.cors'])->group(function () {
    Route::get('bigcommerce/products-with-ymm', [BigCommerceApiController::class, 'getProductsWithYmmFields']);
    Route::get('bigcommerce/compatible-products', [BigCommerceApiController::class, 'getCompatibleProductsByCustomFields']);
    Route::get('bigcommerce/makes', [BigCommerceApiController::class, 'getAvailableMakes']);
    Route::get('bigcommerce/models', [BigCommerceApiController::class, 'getAvailableModels']);
    Route::get('bigcommerce/years', [BigCommerceApiController::class, 'getAvailableYears']);
});
