<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\ProductVehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class YmmFilterController extends Controller
{
    /**
     * Get all available makes
     */
    public function getMakes(): JsonResponse
    {
        $makes = Vehicle::getUniqueMakes();
        
        return response()->json([
            'data' => $makes->values()
        ]);
    }

    /**
     * Get models for a specific make
     */
    public function getModels(Request $request): JsonResponse
    {
        $request->validate([
            'make' => 'required|string'
        ]);

        $models = Vehicle::getModelsForMake($request->make);
        
        return response()->json([
            'data' => $models->values()
        ]);
    }

    /**
     * Get year ranges for a specific make and model
     */
    public function getYearRanges(Request $request): JsonResponse
    {
        $request->validate([
            'make' => 'required|string',
            'model' => 'required|string'
        ]);

        $vehicles = Vehicle::where('is_active', true)
            ->where('make', $request->make)
            ->where('model', $request->model)
            ->orderBy('year_start')
            ->get(['year_start', 'year_end']);

        // Merge overlapping year ranges
        $yearRanges = [];
        foreach ($vehicles as $vehicle) {
            $yearRanges[] = [
                'start' => $vehicle->year_start,
                'end' => $vehicle->year_end,
                'display' => $vehicle->year_start == $vehicle->year_end 
                    ? (string)$vehicle->year_start 
                    : $vehicle->year_start . '-' . $vehicle->year_end
            ];
        }

        return response()->json([
            'data' => $yearRanges
        ]);
    }

    /**
     * Get compatible products for a specific vehicle
     */
    public function getCompatibleProducts(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 2),
            'make' => 'required|string',
            'model' => 'required|string'
        ]);

        $productIds = ProductVehicle::getProductsForVehicle(
            $request->year,
            $request->make,
            $request->model
        );

        if ($productIds->isEmpty()) {
            return response()->json([
                'data' => [],
                'message' => 'No compatible products found for this vehicle.'
            ]);
        }

        // Fetch product details from BigCommerce
        $products = $this->fetchBigCommerceProducts($productIds->toArray(), $request);

        return response()->json([
            'data' => $products,
            'vehicle' => [
                'year' => $request->year,
                'make' => $request->make,
                'model' => $request->model
            ]
        ]);
    }

    /**
     * Check if a specific product is compatible with a vehicle
     */
    public function checkCompatibility(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|string',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 2),
            'make' => 'required|string',
            'model' => 'required|string'
        ]);

        $productIds = ProductVehicle::getProductsForVehicle(
            $request->year,
            $request->make,
            $request->model
        );

        $isCompatible = $productIds->contains($request->product_id);

        return response()->json([
            'compatible' => $isCompatible,
            'product_id' => $request->product_id,
            'vehicle' => [
                'year' => $request->year,
                'make' => $request->make,
                'model' => $request->model
            ]
        ]);
    }

    /**
     * Fetch product details from BigCommerce API
     */
    private function fetchBigCommerceProducts(array $productIds, Request $request): array
    {
        $products = [];
        
        try {
            // Use the existing BigCommerce API proxy
            $controller = new MainController();
            
            // Fetch products in batches to respect API limits
            $batches = array_chunk($productIds, 50); // BigCommerce API limit
            
            foreach ($batches as $batch) {
                $productIdsParam = implode(',', $batch);
                
                // Create a new request for the API call
                $apiRequest = $request->duplicate();
                $apiRequest->query->set('id:in', $productIdsParam);
                $apiRequest->query->set('include', 'images,variants');
                
                $response = $controller->makeBigCommerceAPIRequest($apiRequest, 'v3/catalog/products');
                $responseData = json_decode($response->getBody(), true);
                
                if (isset($responseData['data'])) {
                    $products = array_merge($products, $responseData['data']);
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Error fetching BigCommerce products: ' . $e->getMessage());
        }

        return $products;
    }
}
