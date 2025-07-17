<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles.
     */
    public function index(): JsonResponse
    {
        $vehicles = Vehicle::orderBy('year', 'desc')
            ->orderBy('make')
            ->orderBy('model')
            ->get();
            
        return response()->json($vehicles);
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:1900|max:2030',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
        ]);

        // Check if vehicle already exists
        $existingVehicle = Vehicle::where('year', $request->year)
            ->where('make', $request->make)
            ->where('model', $request->model)
            ->first();

        if ($existingVehicle) {
            return response()->json([
                'error' => 'Vehicle already exists in database'
            ], 422);
        }

        $vehicle = Vehicle::create([
            'year' => $request->year,
            'make' => $request->make,
            'model' => $request->model,
        ]);

        return response()->json($vehicle, 201);
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json($vehicle);
    }

    /**
     * Update the specified vehicle.
     */
    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer|min:1900|max:2030',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
        ]);

        // Check if updated vehicle would conflict with existing
        $existingVehicle = Vehicle::where('year', $request->year)
            ->where('make', $request->make)
            ->where('model', $request->model)
            ->where('id', '!=', $vehicle->id)
            ->first();

        if ($existingVehicle) {
            return response()->json([
                'error' => 'Vehicle already exists in database'
            ], 422);
        }

        $vehicle->update([
            'year' => $request->year,
            'make' => $request->make,
            'model' => $request->model,
        ]);

        return response()->json($vehicle);
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        // Check if vehicle is associated with any products
        if ($vehicle->products()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete vehicle that is associated with products. Remove associations first.'
            ], 422);
        }

        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted successfully']);
    }

    /**
     * Get vehicles for a specific year.
     */
    public function getByYear(int $year): JsonResponse
    {
        $vehicles = Vehicle::where('year', $year)
            ->orderBy('make')
            ->orderBy('model')
            ->get();

        return response()->json($vehicles);
    }

    /**
     * Get all makes for a specific year.
     */
    public function getMakesByYear(int $year): JsonResponse
    {
        $makes = Vehicle::where('year', $year)
            ->distinct()
            ->orderBy('make')
            ->pluck('make');

        return response()->json($makes);
    }

    /**
     * Get all models for a specific year and make.
     */
    public function getModelsByYearAndMake(int $year, string $make): JsonResponse
    {
        $models = Vehicle::where('year', $year)
            ->where('make', $make)
            ->distinct()
            ->orderBy('model')
            ->pluck('model');

        return response()->json($models);
    }

    /**
     * Import vehicles from CSV file.
     */
    public function import(Request $request): JsonResponse
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
            if (count($row) < 3) {
                $errors[] = "Row skipped - insufficient data: " . implode(',', $row);
                $skipped++;
                continue;
            }

            $year = trim($row[0]);
            $make = trim($row[1]);
            $model = trim($row[2]);

            // Validate data
            if (!is_numeric($year) || $year < 1900 || $year > 2030) {
                $errors[] = "Invalid year: $year";
                $skipped++;
                continue;
            }

            if (empty($make) || empty($model)) {
                $errors[] = "Empty make or model: $make, $model";
                $skipped++;
                continue;
            }

            // Check if vehicle already exists
            $existingVehicle = Vehicle::where('year', $year)
                ->where('make', $make)
                ->where('model', $model)
                ->first();

            if (!$existingVehicle) {
                Vehicle::create([
                    'year' => (int)$year,
                    'make' => $make,
                    'model' => $model,
                ]);
                $imported++;
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'message' => "Import completed. $imported vehicles imported, $skipped skipped.",
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
    }
}
