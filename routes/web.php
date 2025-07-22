<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\YmmFilterController;
use App\Http\Controllers\VehicleManagementController;
use App\Http\Controllers\BigCommerceYmmController;
use App\Http\Controllers\BigCommerceTestController;
use App\Http\Controllers\BigCommerceApiController;
use App\Http\Controllers\BigCommerceAppController;
use App\Http\Controllers\YmmManagementController;
use App\Http\Controllers\WidgetController;
use App\Http\Controllers\WidgetManagementController;
use App\Models\BigCommerceStore;
use App\Services\BigCommerceService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
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

// BigCommerce App callbacks - BOTH /auth AND /api/auth to be safe
Route::get('auth', [BigCommerceAppController::class, 'install'])->name('auth');
Route::get('load', [BigCommerceAppController::class, 'load'])->name('load');
Route::get('uninstall', [BigCommerceAppController::class, 'uninstall'])->name('uninstall');

// BigCommerce might call /auth/load instead of /load
Route::prefix('auth')->group(function () {
    Route::get('load', [BigCommerceAppController::class, 'load'])->name('auth.load');
    Route::get('uninstall', [BigCommerceAppController::class, 'uninstall'])->name('auth.uninstall');
    Route::get('remove_user', [BigCommerceAppController::class, 'removeUser'])->name('auth.remove_user');
});

// Debug endpoint to catch any load attempts
Route::get('debug-load', function (Request $request) {
    Log::info('Debug Load Endpoint Called', [
        'method' => $request->method(),
        'query' => $request->query(),
        'all' => $request->all(),
        'headers' => $request->headers->all()
    ]);
    return response()->json(['message' => 'Debug load endpoint reached', 'query' => $request->query()]);
});

// BigCommerce App callbacks (MUST be at /api/* according to BigCommerce docs)
Route::prefix('api')->group(function () {
    Route::get('auth', [BigCommerceAppController::class, 'install'])->name('api.auth');
    Route::get('load', [BigCommerceAppController::class, 'load'])->name('api.load');
    Route::get('uninstall', [BigCommerceAppController::class, 'uninstall'])->name('api.uninstall');
    Route::get('remove_user', [BigCommerceAppController::class, 'removeUser'])->name('api.remove_user');

    // Debug endpoint
    Route::get('test', function () {
        return [
            'message' => 'BigCommerce API endpoints are working!',
            'timestamp' => now(),
            'routes' => [
                'auth' => url('/api/auth'),
                'load' => url('/api/load'),
                'uninstall' => url('/api/uninstall'),
                'remove_user' => url('/api/remove_user')
            ]
        ];
    });
});

// OLD BigCommerce routes - REMOVE THESE
// Route::post('/bigcommerce/install', [BigCommerceAppController::class, 'install'])->name('bigcommerce.install');
// Route::get('/bigcommerce/load', [BigCommerceAppController::class, 'load'])->name('bigcommerce.load');
// Route::post('/bigcommerce/uninstall', [BigCommerceAppController::class, 'uninstall'])->name('bigcommerce.uninstall');

// For BigCommerce app development - these are the MAIN callback routes
// Route::prefix('bigcommerce')->group(function () {
//     // Installation callback
//     Route::get('auth', [BigCommerceAppController::class, 'install'])->name('bigcommerce.auth');
//     // Load callback
//     Route::get('load', [BigCommerceAppController::class, 'load'])->name('bigcommerce.load');
//     // Uninstall callback  
//     Route::get('uninstall', [BigCommerceAppController::class, 'uninstall'])->name('bigcommerce.uninstall');
//     // Remove user callback
//     Route::get('remove-user', [BigCommerceAppController::class, 'removeUser'])->name('bigcommerce.remove-user');
// });

// Public YMM API endpoints (for widget)
Route::prefix('api/ymm/{storeHash}')->group(function () {
    Route::get('/data', [YmmManagementController::class, 'getYmmData'])->name('api.ymm.data');
    Route::post('/check', [YmmManagementController::class, 'checkCompatibility'])->name('api.ymm.check');
});

// API Test Page
Route::get('/api-test', function () {
    return view('api-test');
});

// YMM Results Page (public route for redirects from widget)
Route::get('/ymm-results', [YmmManagementController::class, 'ymmResults'])->name('ymm.results');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// BigCommerce App Dashboard (store-specific routes)
Route::prefix('app/{storeHash}')->group(function () {
    Route::get('/dashboard', [BigCommerceAppController::class, 'dashboard'])->name('app.dashboard');

    // Vehicle Management
    Route::get('/vehicles', [YmmManagementController::class, 'vehicles'])->name('app.vehicles');
    Route::post('/vehicles', [YmmManagementController::class, 'storeVehicle'])->name('app.vehicles.store');
    Route::put('/vehicles/{vehicleId}', [YmmManagementController::class, 'updateVehicle'])->name('app.vehicles.update');
    Route::delete('/vehicles/{vehicleId}', [YmmManagementController::class, 'deleteVehicle'])->name('app.vehicles.delete');

    // Product Management
    Route::get('/products', [YmmManagementController::class, 'products'])->name('app.products');
    Route::post('/products/compatibility', [YmmManagementController::class, 'addProductCompatibility'])->name('app.products.compatibility.add');
    Route::delete('/products/compatibility', [YmmManagementController::class, 'removeProductCompatibility'])->name('app.products.compatibility.remove');
    Route::get('/products/export', [YmmManagementController::class, 'exportProducts'])->name('app.products.export');
    Route::post('/products/import', [YmmManagementController::class, 'importProductCompatibility'])->name('app.products.import');

    // Settings
    Route::get('/settings', [YmmManagementController::class, 'settings'])->name('app.settings');
    Route::post('/settings', [YmmManagementController::class, 'updateSettings'])->name('app.settings.update');

    // Widget Management
    Route::get('/widget-management', [WidgetManagementController::class, 'index'])->name('app.widget-management');
    Route::post('/install-widget', [WidgetManagementController::class, 'install'])->name('app.install-widget');
    Route::get('/api/widgets', [WidgetManagementController::class, 'getWidgets'])->name('app.api.widgets.list');
    Route::post('/api/widgets/install', [WidgetManagementController::class, 'install'])->name('app.api.widgets.install');
    Route::post('/api/widgets/create-ymm', [WidgetManagementController::class, 'createYmmWidget'])->name('app.api.widgets.create-ymm');
    Route::post('/api/widgets/remove', [WidgetManagementController::class, 'remove'])->name('app.api.widgets.remove');
    Route::post('/api/widgets/remove-all', [WidgetManagementController::class, 'removeAll'])->name('app.api.widgets.remove-all');
    Route::post('/api/widgets/preview', [WidgetManagementController::class, 'getPreview'])->name('app.api.widgets.preview');
    
    Route::post('/widgets/pagebuilder', [WidgetController::class, 'createPageBuilderWidget'])->name('app.widgets.create');
    Route::put('/widgets/pagebuilder/{templateId}', [WidgetController::class, 'updatePageBuilderWidget'])->name('app.widgets.update');
    Route::get('/widgets/templates', [WidgetController::class, 'getWidgetTemplates'])->name('app.widgets.templates');

    // Test route for BigCommerce API
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

    // Import/Export
    Route::post('/vehicles/import', [YmmManagementController::class, 'importVehicles'])->name('app.vehicles.import');
    Route::get('/vehicles/export', [YmmManagementController::class, 'exportVehicles'])->name('app.vehicles.export');
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

    // Widget Management routes
    Route::get('/widget-management', [WidgetManagementController::class, 'index'])->name('widget-management');
    Route::get('/api/widgets', [WidgetManagementController::class, 'getWidgets'])->name('api.widgets.list');
    Route::post('/api/widgets/install', [WidgetManagementController::class, 'install'])->name('api.widgets.install');
    Route::post('/api/widgets/remove', [WidgetManagementController::class, 'remove'])->name('api.widgets.remove');
    Route::post('/api/widgets/remove-all', [WidgetManagementController::class, 'removeAll'])->name('api.widgets.remove-all');
    Route::post('/api/widgets/preview', [WidgetManagementController::class, 'getPreview'])->name('api.widgets.preview');

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

// OLD ROUTES - COMMENTING OUT TO AVOID CONFLICTS
// Route::group(['prefix' => 'auth'], function () {
//     // BigCommerce OAuth callback - must be /auth according to docs
//     Route::get('/', [MainController::class, 'install']);
//     Route::get('load', [MainController::class, 'load']);
//     Route::get('error', [MainController::class, 'error']);
//     Route::get('uninstall', function () {
//         echo 'uninstall';
//         return app()->version();
//     });
//     Route::get('remove-user', function () {
//         echo 'remove-user';
//         return app()->version();
//     });
// });

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

// Public widget rendering (no auth required for BigCommerce storefronts)
Route::get('/widget/{storeHash}', [WidgetController::class, 'renderWidget'])->name('widget.render');

// API test route
Route::get('/api-test', function () {
    return view('api-test');
});;

// Include debug routes
require __DIR__ . '/debug.php';

require __DIR__ . '/auth.php';
