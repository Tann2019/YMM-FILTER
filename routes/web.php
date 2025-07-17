<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\YmmFilterController;
use App\Http\Controllers\VehicleManagementController;
use App\Http\Controllers\BigCommerceYmmController;
use App\Http\Controllers\BigCommerceTestController;
use App\Http\Controllers\BigCommerceApiController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\MainController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// API Test Page
Route::get('/api-test', function () {
    return view('api-test');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/list', function () {
        return Inertia::render('List');
    });

    Route::get('/inventory', function () {
        return Inertia::render('Inventory');
    })->name('inventory');

    Route::get('/reports', function () {
        return Inertia::render('Reports');
    })->name('reports');

    Route::get('/ymm-filter', function () {
        return Inertia::render('YmmFilter');
    })->name('ymm-filter');

    Route::get('/bc-ymm-filter', function () {
        return Inertia::render('BigCommerceYmmFilter');
    })->name('bc-ymm-filter');

    Route::get('/vehicle-management', function () {
        return Inertia::render('VehicleManagement');
    })->name('vehicle-management');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// YMM Filter API Routes (Local Database)
Route::prefix('api/ymm')->group(function () {
    Route::get('/makes', [YmmFilterController::class, 'getMakes']);
    Route::get('/models', [YmmFilterController::class, 'getModels']);
    Route::get('/year-ranges', [YmmFilterController::class, 'getYearRanges']);
    Route::get('/compatible-products', [YmmFilterController::class, 'getCompatibleProducts']);
    Route::post('/check-compatibility', [YmmFilterController::class, 'checkCompatibility']);
});

// BigCommerce YMM Filter API Routes (Using Custom Fields)
Route::prefix('api/bc-ymm')->group(function () {
    Route::get('/makes', [BigCommerceYmmController::class, 'getMakes']);
    Route::get('/models', [BigCommerceYmmController::class, 'getModels']);
    Route::get('/year-ranges', [BigCommerceYmmController::class, 'getYearRanges']);
    Route::get('/compatible-products', [BigCommerceYmmController::class, 'getCompatibleProducts']);
});

// BigCommerce Test Routes
Route::prefix('api/bc-test')->group(function () {
    Route::get('/connection', [BigCommerceTestController::class, 'testConnection']);
    Route::get('/products', [BigCommerceTestController::class, 'testProducts']);
});

// Vehicle Management API Routes
Route::prefix('api/vehicles')->group(function () {
    Route::get('/', [VehicleManagementController::class, 'index']);
    Route::post('/', [VehicleManagementController::class, 'store']);
    Route::put('/{vehicle}', [VehicleManagementController::class, 'update']);
    Route::delete('/{vehicle}', [VehicleManagementController::class, 'destroy']);
    Route::post('/bulk-import', [VehicleManagementController::class, 'bulkImport']);
    Route::post('/add-product', [VehicleManagementController::class, 'addProduct']);
    Route::delete('/remove-product', [VehicleManagementController::class, 'removeProduct']);
    Route::get('/{vehicle}/products', [VehicleManagementController::class, 'getVehicleProducts']);
});

Route::group(['prefix' => 'auth'], function () {
    // BigCommerce OAuth callback - must be /auth according to docs
    Route::get('/', [MainController::class, 'install']);

    Route::get('load', [MainController::class, 'load']);
    
    Route::get('error', [MainController::class, 'error']);

    Route::get('uninstall', function () {
        echo 'uninstall';
        return app()->version();
    });

    Route::get('remove-user', function () {
        echo 'remove-user';
        return app()->version();
    });
});

// Debug route to check session data
Route::get('/debug/session', function () {
    return [
        'session_id' => session()->getId(),
        'store_hash' => session()->get('store_hash'),
        'access_token' => session()->get('access_token') ? substr(session()->get('access_token'), 0, 10) . '...' : null,
        'user_id' => session()->get('user_id'),
        'user_email' => session()->get('user_email'),
        'all_session_data' => session()->all()
    ];
});

// Route to test store hash detection
Route::get('/test-store-hash', function () {
    $controller = new \App\Http\Controllers\MainController();
    $request = request();
    
    return [
        'app_env' => env('APP_ENV'),
        'bc_local_store_hash_env' => env('BC_LOCAL_STORE_HASH'),
        'bc_local_store_hash_exists' => env('BC_LOCAL_STORE_HASH') ? 'YES' : 'NO',
        'session_store_hash' => $request->session()->get('store_hash'),
        'getStoreHash_result' => $controller->getStoreHash($request),
        'all_env_bc' => [
            'BC_LOCAL_CLIENT_ID' => env('BC_LOCAL_CLIENT_ID'),
            'BC_LOCAL_SECRET' => env('BC_LOCAL_SECRET') ? substr(env('BC_LOCAL_SECRET'), 0, 10) . '...' : null,
            'BC_LOCAL_ACCESS_TOKEN' => env('BC_LOCAL_ACCESS_TOKEN') ? substr(env('BC_LOCAL_ACCESS_TOKEN'), 0, 10) . '...' : null,
            'BC_LOCAL_STORE_HASH' => env('BC_LOCAL_STORE_HASH'),
        ]
    ];
});

Route::any('/bc-api/{endpoint}', [MainController::class, 'proxyBigCommerceAPIRequest'])
    ->where('endpoint', 'v2/.*|v3/.*');

// Widget routes for embedding in BigCommerce storefront
Route::middleware(['widget.cors'])->group(function () {
    Route::get('/ymm-widget', function () {
        $response = response(view('components.ymm-filter-widget-custom-fields'));
        
        // Add headers for better browser compatibility
        $response->header('X-Frame-Options', 'ALLOWALL');
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        
        return $response;
    });
    
    Route::get('/product-compatibility/{productId}', [BigCommerceApiController::class, 'getProductCompatibilityInfo']);
});

// Widget test page for debugging
Route::get('/widget-test', function () {
    return view('widget-test');
});

// API test route
Route::get('/api-test', function () {
    return view('api-test');
});;

// Include debug routes
require __DIR__.'/debug.php';

require __DIR__.'/auth.php';
