<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BigCommerceStore;
use App\Models\Vehicle;
use App\Models\ProductVehicle;
use App\Services\BigCommerceService;

class YmmApiController extends Controller
{
    protected $bigCommerceService;

    public function __construct(BigCommerceService $bigCommerceService)
    {
        $this->bigCommerceService = $bigCommerceService;
    }

    /**
     * Get available years for a store
     */
    public function getYears($storeHash)
    {
        $years = Vehicle::where('store_hash', $storeHash)
            ->where('is_active', true)
            ->selectRaw('MIN(year_start) as min_year, MAX(year_end) as max_year')
            ->first();

        if (!$years || !$years->min_year || !$years->max_year) {
            return response()->json([]);
        }

        $yearRange = [];
        for ($year = $years->max_year; $year >= $years->min_year; $year--) {
            $yearRange[] = $year;
        }

        return response()->json($yearRange);
    }

    /**
     * Get available makes for a year
     */
    public function getMakes(Request $request, $storeHash)
    {
        $year = $request->get('year');

        if (!$year) {
            return response()->json([]);
        }

        $makes = Vehicle::where('store_hash', $storeHash)
            ->where('is_active', true)
            ->where('year_start', '<=', $year)
            ->where('year_end', '>=', $year)
            ->distinct()
            ->orderBy('make')
            ->pluck('make')
            ->toArray();

        return response()->json($makes);
    }

    /**
     * Get available models for a year and make
     */
    public function getModels(Request $request, $storeHash)
    {
        $year = $request->get('year');
        $make = $request->get('make');

        if (!$year || !$make) {
            return response()->json([]);
        }

        $models = Vehicle::where('store_hash', $storeHash)
            ->where('is_active', true)
            ->where('make', $make)
            ->where('year_start', '<=', $year)
            ->where('year_end', '>=', $year)
            ->distinct()
            ->orderBy('model')
            ->pluck('model')
            ->toArray();

        return response()->json($models);
    }

    /**
     * Search for compatible products
     */
    public function searchCompatibleProducts(Request $request, $storeHash)
    {
        $year = $request->get('year');
        $make = $request->get('make');
        $model = $request->get('model');

        if (!$year || !$make || !$model) {
            return response()->json(['products' => []]);
        }

        try {
            // Find vehicles that match the criteria
            $vehicles = Vehicle::where('store_hash', $storeHash)
                ->where('is_active', true)
                ->where('make', $make)
                ->where('model', $model)
                ->where('year_start', '<=', $year)
                ->where('year_end', '>=', $year)
                ->get();

            if ($vehicles->isEmpty()) {
                return response()->json(['products' => []]);
            }

            // Get products that are compatible with these vehicles
            $productIds = ProductVehicle::whereIn('vehicle_id', $vehicles->pluck('id'))
                ->distinct()
                ->pluck('bigcommerce_product_id')
                ->toArray();

            if (empty($productIds)) {
                return response()->json(['products' => []]);
            }

            // Fetch product details from BigCommerce
            $products = [];
            $chunks = array_chunk($productIds, 50); // BigCommerce API limitation

            foreach ($chunks as $chunk) {
                $params = [
                    'id:in' => implode(',', $chunk),
                    'include' => 'images',
                    'is_visible' => true
                ];

                $response = $this->bigCommerceService->makeApiCall(
                    $storeHash,
                    '/catalog/products',
                    'GET',
                    $params
                );

                if (isset($response['data'])) {
                    $products = array_merge($products, $response['data']);
                }
            }

            // Format products for frontend
            $formattedProducts = array_map(function ($product) {
                return [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'sku' => $product['sku'] ?? '',
                    'images' => $product['images'] ?? [],
                    'custom_url' => $product['custom_url'] ?? null,
                    'description' => strip_tags($product['description'] ?? ''),
                ];
            }, $products);

            return response()->json([
                'products' => $formattedProducts,
                'total' => count($formattedProducts),
                'vehicle_info' => [
                    'year' => $year,
                    'make' => $make,
                    'model' => $model
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to search products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get widget configuration
     */
    public function getWidgetConfig($storeHash)
    {
        $store = BigCommerceStore::where('store_hash', $storeHash)->firstOrFail();

        return response()->json([
            'title' => 'Vehicle Compatibility Filter',
            'theme' => 'default',
            'show_images' => true,
            'button_text' => 'Search Compatible Products',
            'placeholder_year' => 'Select Year',
            'placeholder_make' => 'Select Make',
            'placeholder_model' => 'Select Model',
            'primary_color' => '#3B82F6',
            'button_color' => '#1D4ED8'
        ]);
    }

    /**
     * Health check for widget API
     */
    public function healthCheck($storeHash)
    {
        try {
            $store = BigCommerceStore::where('store_hash', $storeHash)->firstOrFail();
            $vehicleCount = Vehicle::where('store_hash', $storeHash)->where('is_active', true)->count();
            $compatibilityCount = ProductVehicle::whereHas('vehicle', function ($query) use ($storeHash) {
                $query->where('store_hash', $storeHash)->where('is_active', true);
            })->count();

            return response()->json([
                'status' => 'healthy',
                'store_active' => $store->active,
                'vehicle_count' => $vehicleCount,
                'compatibility_count' => $compatibilityCount,
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
