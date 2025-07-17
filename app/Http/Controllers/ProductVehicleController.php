<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\ProductVehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ProductVehicleController extends Controller
{
    /**
     * Get vehicles associated with a specific product.
     */
    public function getProductVehicles($productId): JsonResponse
    {
        $productVehicles = ProductVehicle::where('product_id', $productId)
            ->with('vehicle')
            ->get();

        $vehicles = $productVehicles->map(function ($pv) {
            return $pv->vehicle;
        });

        return response()->json($vehicles);
    }

    /**
     * Associate vehicles with a product.
     */
    public function associateVehicles(Request $request, $productId): JsonResponse
    {
        $request->validate([
            'vehicle_ids' => 'required|array',
            'vehicle_ids.*' => 'exists:vehicles,id'
        ]);

        // Remove existing associations for this product
        ProductVehicle::where('product_id', $productId)->delete();

        // Create new associations
        foreach ($request->vehicle_ids as $vehicleId) {
            ProductVehicle::create([
                'product_id' => $productId,
                'vehicle_id' => $vehicleId,
            ]);
        }

        // Clear cache for this product
        Cache::forget("product_vehicles_{$productId}");

        return response()->json(['message' => 'Vehicle associations updated successfully']);
    }

    /**
     * Remove a specific vehicle association from a product.
     */
    public function dissociateVehicle($productId, $vehicleId): JsonResponse
    {
        $deleted = ProductVehicle::where('product_id', $productId)
            ->where('vehicle_id', $vehicleId)
            ->delete();

        if ($deleted) {
            // Clear cache for this product
            Cache::forget("product_vehicles_{$productId}");
            return response()->json(['message' => 'Vehicle dissociated successfully']);
        }

        return response()->json(['error' => 'Association not found'], 404);
    }

    /**
     * Get all products compatible with specific vehicle criteria.
     */
    public function getCompatibleProducts(Request $request): JsonResponse
    {
        $year = $request->input('year');
        $make = $request->input('make');
        $model = $request->input('model');

        $query = ProductVehicle::with('vehicle');

        if ($year) {
            $query->whereHas('vehicle', function ($q) use ($year) {
                $q->where('year', $year);
            });
        }

        if ($make) {
            $query->whereHas('vehicle', function ($q) use ($make) {
                $q->where('make', $make);
            });
        }

        if ($model) {
            $query->whereHas('vehicle', function ($q) use ($model) {
                $q->where('model', $model);
            });
        }

        $productVehicles = $query->get();
        $productIds = $productVehicles->pluck('product_id')->unique();

        return response()->json([
            'product_ids' => $productIds->values(),
            'count' => $productIds->count()
        ]);
    }

    /**
     * Save widget settings.
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $request->validate([
            'widget_title' => 'required|string|max:255',
            'default_message' => 'required|string|max:500',
            'enable_widget' => 'boolean',
            'widget_position' => 'in:top,bottom,sidebar',
            'show_all_products_initially' => 'boolean',
        ]);

        $settings = [
            'widget_title' => $request->widget_title,
            'default_message' => $request->default_message,
            'enable_widget' => $request->enable_widget ?? true,
            'widget_position' => $request->widget_position ?? 'top',
            'show_all_products_initially' => $request->show_all_products_initially ?? true,
        ];

        // Store settings in cache/database
        Cache::forever('ymm_filter_settings', $settings);

        return response()->json(['message' => 'Settings saved successfully']);
    }

    /**
     * Get widget settings.
     */
    public function getSettings(): JsonResponse
    {
        $defaultSettings = [
            'widget_title' => 'Vehicle Compatibility',
            'default_message' => 'Select your vehicle to view compatible products',
            'enable_widget' => true,
            'widget_position' => 'top',
            'show_all_products_initially' => true,
        ];

        $settings = Cache::get('ymm_filter_settings', $defaultSettings);

        return response()->json($settings);
    }

    /**
     * Bulk import product-vehicle associations from CSV.
     */
    public function importAssociations(Request $request): JsonResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $file = $request->file('csv_file');
        $csvData = array_map('str_getcsv', file($file->path()));
        
        // Remove header row if present
        $header = array_shift($csvData);
        
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($csvData as $row) {
            if (count($row) < 4) {
                $errors[] = "Row skipped - insufficient data: " . implode(',', $row);
                $skipped++;
                continue;
            }

            $productId = trim($row[0]);
            $year = trim($row[1]);
            $make = trim($row[2]);
            $model = trim($row[3]);

            // Find the vehicle
            $vehicle = Vehicle::where('year', $year)
                ->where('make', $make)
                ->where('model', $model)
                ->first();

            if (!$vehicle) {
                $errors[] = "Vehicle not found: $year $make $model";
                $skipped++;
                continue;
            }

            // Check if association already exists
            $existingAssociation = ProductVehicle::where('product_id', $productId)
                ->where('vehicle_id', $vehicle->id)
                ->first();

            if (!$existingAssociation) {
                ProductVehicle::create([
                    'product_id' => $productId,
                    'vehicle_id' => $vehicle->id,
                ]);
                $imported++;
                
                // Clear cache for this product
                Cache::forget("product_vehicles_{$productId}");
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'message' => "Import completed. $imported associations imported, $skipped skipped.",
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
    }

    /**
     * Export product-vehicle associations to CSV.
     */
    public function exportAssociations(): JsonResponse
    {
        $associations = ProductVehicle::with('vehicle')->get();

        $csvData = [];
        $csvData[] = ['Product ID', 'Year', 'Make', 'Model']; // Header

        foreach ($associations as $association) {
            $csvData[] = [
                $association->product_id,
                $association->vehicle->year,
                $association->vehicle->make,
                $association->vehicle->model,
            ];
        }

        $filename = 'product_vehicle_associations_' . date('Y-m-d_H-i-s') . '.csv';
        $filePath = storage_path('app/exports/' . $filename);

        // Create exports directory if it doesn't exist
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $file = fopen($filePath, 'w');
        foreach ($csvData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        return response()->json([
            'message' => 'Export completed successfully',
            'filename' => $filename,
            'download_url' => url('/api/download-export/' . $filename)
        ]);
    }
}
