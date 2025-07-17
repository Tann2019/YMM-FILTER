<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\ProductVehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleManagementController extends Controller
{
    /**
     * Get all vehicles with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $vehicles = Vehicle::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('make', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('year_start', 'like', "%{$search}%")
                        ->orWhere('year_end', 'like', "%{$search}%");
                });
            })
            ->orderBy('make')
            ->orderBy('model')
            ->orderBy('year_start')
            ->paginate(20);

        return response()->json($vehicles);
    }

    /**
     * Create a new vehicle
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year_start' => 'required|integer|min:1900|max:' . (date('Y') + 2),
            'year_end' => 'required|integer|min:1900|max:' . (date('Y') + 2) . '|gte:year_start',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        // Check for overlapping ranges for the same make/model
        $overlap = Vehicle::where('make', $validated['make'])
            ->where('model', $validated['model'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('year_start', [$validated['year_start'], $validated['year_end']])
                    ->orWhereBetween('year_end', [$validated['year_start'], $validated['year_end']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('year_start', '<=', $validated['year_start'])
                            ->where('year_end', '>=', $validated['year_end']);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'This year range overlaps with an existing vehicle entry.'
            ], 422);
        }

        $vehicle = Vehicle::create($validated);

        return response()->json($vehicle, 201);
    }

    /**
     * Update a vehicle
     */
    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'year_start' => 'required|integer|min:1900|max:' . (date('Y') + 2),
            'year_end' => 'required|integer|min:1900|max:' . (date('Y') + 2) . '|gte:year_start',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        // Check for overlapping ranges for the same make/model (excluding current vehicle)
        $overlap = Vehicle::where('make', $validated['make'])
            ->where('model', $validated['model'])
            ->where('id', '!=', $vehicle->id)
            ->where(function ($query) use ($validated) {
                $query->whereBetween('year_start', [$validated['year_start'], $validated['year_end']])
                    ->orWhereBetween('year_end', [$validated['year_start'], $validated['year_end']])
                    ->orWhere(function ($q) use ($validated) {
                        $q->where('year_start', '<=', $validated['year_start'])
                            ->where('year_end', '>=', $validated['year_end']);
                    });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'This year range overlaps with an existing vehicle entry.'
            ], 422);
        }

        $vehicle->update($validated);

        return response()->json($vehicle);
    }

    /**
     * Delete a vehicle
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted successfully']);
    }

    /**
     * Add product to vehicle compatibility
     */
    public function addProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'bigcommerce_product_id' => 'required|string'
        ]);

        $productVehicle = ProductVehicle::updateOrCreate(
            [
                'vehicle_id' => $validated['vehicle_id'],
                'bigcommerce_product_id' => $validated['bigcommerce_product_id']
            ]
        );

        return response()->json($productVehicle, 201);
    }

    /**
     * Remove product from vehicle compatibility
     */
    public function removeProduct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'bigcommerce_product_id' => 'required|string'
        ]);

        ProductVehicle::where('vehicle_id', $validated['vehicle_id'])
            ->where('bigcommerce_product_id', $validated['bigcommerce_product_id'])
            ->delete();

        return response()->json(['message' => 'Product removed from vehicle compatibility']);
    }

    /**
     * Get products for a specific vehicle
     */
    public function getVehicleProducts(Vehicle $vehicle): JsonResponse
    {
        $productVehicles = ProductVehicle::where('vehicle_id', $vehicle->id)
            ->get();

        return response()->json($productVehicles);
    }

    /**
     * Bulk import vehicles from CSV
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $request->validate([
            'csv_data' => 'required|string'
        ]);

        $lines = str_getcsv($request->csv_data, "\n");
        $imported = 0;
        $errors = [];

        foreach ($lines as $index => $line) {
            if ($index === 0) continue; // Skip header

            $data = str_getcsv($line);
            
            if (count($data) < 4) {
                $errors[] = "Line " . ($index + 1) . ": Invalid format";
                continue;
            }

            try {
                Vehicle::create([
                    'year_start' => (int)$data[0],
                    'year_end' => (int)$data[1],
                    'make' => trim($data[2]),
                    'model' => trim($data[3]),
                    'is_active' => true
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Line " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'imported' => $imported,
            'errors' => $errors
        ]);
    }
}
