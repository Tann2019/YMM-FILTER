<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BigCommerceYmmController extends Controller
{
    /**
     * Get all makes from BigCommerce products with custom fields
     */
    public function getMakes(Request $request): JsonResponse
    {
        try {
            $controller = new MainController();
            
            // Get all products with custom fields
            $apiRequest = $request->duplicate();
            $apiRequest->query->set('include', 'custom_fields');
            $apiRequest->query->set('limit', 250); // Max per request
            
            $response = $controller->makeBigCommerceAPIRequest($apiRequest, 'v3/catalog/products');
            $responseData = json_decode($response->getBody(), true);
            
            $makes = [];
            
            if (isset($responseData['data'])) {
                foreach ($responseData['data'] as $product) {
                    if (isset($product['custom_fields'])) {
                        foreach ($product['custom_fields'] as $field) {
                            if ($field['name'] === 'ymm_make' && !empty($field['value'])) {
                                $makes[] = $field['value'];
                            }
                        }
                    }
                }
            }
            
            $uniqueMakes = array_unique($makes);
            sort($uniqueMakes);
            
            return response()->json([
                'data' => array_values($uniqueMakes)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching makes from BigCommerce: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch makes'], 500);
        }
    }

    /**
     * Get models for a specific make
     */
    public function getModels(Request $request): JsonResponse
    {
        $request->validate([
            'make' => 'required|string'
        ]);

        try {
            $controller = new MainController();
            
            $apiRequest = $request->duplicate();
            $apiRequest->query->set('include', 'custom_fields');
            $apiRequest->query->set('limit', 250);
            
            $response = $controller->makeBigCommerceAPIRequest($apiRequest, 'v3/catalog/products');
            $responseData = json_decode($response->getBody(), true);
            
            $models = [];
            
            if (isset($responseData['data'])) {
                foreach ($responseData['data'] as $product) {
                    if (isset($product['custom_fields'])) {
                        $productMake = null;
                        $productModel = null;
                        
                        foreach ($product['custom_fields'] as $field) {
                            if ($field['name'] === 'ymm_make') {
                                $productMake = $field['value'];
                            }
                            if ($field['name'] === 'ymm_model') {
                                $productModel = $field['value'];
                            }
                        }
                        
                        if ($productMake === $request->make && !empty($productModel)) {
                            $models[] = $productModel;
                        }
                    }
                }
            }
            
            $uniqueModels = array_unique($models);
            sort($uniqueModels);
            
            return response()->json([
                'data' => array_values($uniqueModels)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching models from BigCommerce: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch models'], 500);
        }
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

        try {
            $controller = new MainController();
            
            $apiRequest = $request->duplicate();
            $apiRequest->query->set('include', 'custom_fields');
            $apiRequest->query->set('limit', 250);
            
            $response = $controller->makeBigCommerceAPIRequest($apiRequest, 'v3/catalog/products');
            $responseData = json_decode($response->getBody(), true);
            
            $yearRanges = [];
            
            if (isset($responseData['data'])) {
                foreach ($responseData['data'] as $product) {
                    if (isset($product['custom_fields'])) {
                        $productMake = null;
                        $productModel = null;
                        $yearStart = null;
                        $yearEnd = null;
                        
                        foreach ($product['custom_fields'] as $field) {
                            switch ($field['name']) {
                                case 'ymm_make':
                                    $productMake = $field['value'];
                                    break;
                                case 'ymm_model':
                                    $productModel = $field['value'];
                                    break;
                                case 'ymm_year_start':
                                    $yearStart = (int)$field['value'];
                                    break;
                                case 'ymm_year_end':
                                    $yearEnd = (int)$field['value'];
                                    break;
                            }
                        }
                        
                        if ($productMake === $request->make && 
                            $productModel === $request->model && 
                            $yearStart && $yearEnd) {
                            
                            $yearRanges[] = [
                                'start' => $yearStart,
                                'end' => $yearEnd,
                                'display' => $yearStart == $yearEnd 
                                    ? (string)$yearStart 
                                    : $yearStart . '-' . $yearEnd
                            ];
                        }
                    }
                }
            }
            
            // Remove duplicates and sort
            $uniqueRanges = [];
            foreach ($yearRanges as $range) {
                $key = $range['start'] . '-' . $range['end'];
                $uniqueRanges[$key] = $range;
            }
            
            usort($uniqueRanges, function($a, $b) {
                return $a['start'] - $b['start'];
            });
            
            return response()->json([
                'data' => array_values($uniqueRanges)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching year ranges from BigCommerce: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch year ranges'], 500);
        }
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

        try {
            $controller = new MainController();
            
            $apiRequest = $request->duplicate();
            $apiRequest->query->set('include', 'custom_fields,images');
            $apiRequest->query->set('limit', 250);
            
            $response = $controller->makeBigCommerceAPIRequest($apiRequest, 'v3/catalog/products');
            $responseData = json_decode($response->getBody(), true);
            
            $compatibleProducts = [];
            
            if (isset($responseData['data'])) {
                foreach ($responseData['data'] as $product) {
                    if (isset($product['custom_fields']) && $this->isProductCompatible($product, $request)) {
                        $compatibleProducts[] = $product;
                    }
                }
            }
            
            return response()->json([
                'data' => $compatibleProducts,
                'vehicle' => [
                    'year' => $request->year,
                    'make' => $request->make,
                    'model' => $request->model
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching compatible products from BigCommerce: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch compatible products'], 500);
        }
    }

    /**
     * Check if a product is compatible with the requested vehicle
     */
    private function isProductCompatible($product, $request): bool
    {
        $productMake = null;
        $productModel = null;
        $yearStart = null;
        $yearEnd = null;
        
        foreach ($product['custom_fields'] as $field) {
            switch ($field['name']) {
                case 'ymm_make':
                    $productMake = $field['value'];
                    break;
                case 'ymm_model':
                    $productModel = $field['value'];
                    break;
                case 'ymm_year_start':
                    $yearStart = (int)$field['value'];
                    break;
                case 'ymm_year_end':
                    $yearEnd = (int)$field['value'];
                    break;
            }
        }
        
        return $productMake === $request->make &&
               $productModel === $request->model &&
               $yearStart <= $request->year &&
               $yearEnd >= $request->year;
    }
}
