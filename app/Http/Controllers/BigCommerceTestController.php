<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BigCommerceTestController extends Controller
{
    /**
     * Test BigCommerce API connection
     */
    public function testConnection(Request $request): JsonResponse
    {
        try {
            $controller = new MainController();
            
            // Test simple store info API call
            $response = $controller->makeBigCommerceAPIRequest($request, 'v2/store');
            $storeData = json_decode($response->getBody(), true);
            
            return response()->json([
                'success' => true,
                'message' => 'BigCommerce API connection successful!',
                'store_name' => $storeData['name'] ?? 'Unknown',
                'store_url' => $storeData['domain'] ?? 'Unknown',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'BigCommerce API connection failed',
                'error' => $e->getMessage(),
                'suggestions' => [
                    'Check your .env file has correct BC_LOCAL_* credentials',
                    'Verify API account has proper scopes (Products: Read-only)',
                    'Ensure store hash is correct (without stores/ prefix)',
                    'Check if your IP is allowed in BigCommerce API settings'
                ]
            ], 500);
        }
    }

    /**
     * Test products API with custom fields
     */
    public function testProducts(Request $request): JsonResponse
    {
        try {
            $controller = new MainController();
            
            $apiRequest = $request->duplicate();
            $apiRequest->query->set('include', 'custom_fields');
            $apiRequest->query->set('limit', 5);
            
            $response = $controller->makeBigCommerceAPIRequest($apiRequest, 'v3/catalog/products');
            $responseData = json_decode($response->getBody(), true);
            
            $productsWithYmm = [];
            $totalProducts = count($responseData['data'] ?? []);
            
            if (isset($responseData['data'])) {
                foreach ($responseData['data'] as $product) {
                    $ymmFields = [];
                    if (isset($product['custom_fields'])) {
                        foreach ($product['custom_fields'] as $field) {
                            if (strpos($field['name'], 'ymm_') === 0) {
                                $ymmFields[$field['name']] = $field['value'];
                            }
                        }
                    }
                    
                    if (!empty($ymmFields)) {
                        $productsWithYmm[] = [
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'sku' => $product['sku'],
                            'ymm_fields' => $ymmFields
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Products API test successful!',
                'total_products' => $totalProducts,
                'products_with_ymm' => count($productsWithYmm),
                'sample_products' => $productsWithYmm,
                'suggestions' => count($productsWithYmm) === 0 ? [
                    'Add custom fields to your products: ymm_make, ymm_model, ymm_year_start, ymm_year_end',
                    'Go to Products > [Select Product] > Edit > Custom Fields section',
                    'Example: ymm_make = "Ford", ymm_model = "F-150"'
                ] : []
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Products API test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
