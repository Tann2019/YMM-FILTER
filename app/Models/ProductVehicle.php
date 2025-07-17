<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'bigcommerce_product_id',
        'vehicle_id',
    ];

    /**
     * Get the vehicle that this product fits
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get products that fit a specific vehicle
     */
    public static function getProductsForVehicle($year, $make, $model)
    {
        $vehicles = Vehicle::findCompatible($year, $make, $model);
        
        return static::whereIn('vehicle_id', $vehicles->pluck('id'))
            ->pluck('bigcommerce_product_id')
            ->unique()
            ->values();
    }

    /**
     * Get vehicles that a specific product fits
     */
    public static function getVehiclesForProduct($bigcommerceProductId)
    {
        return static::where('bigcommerce_product_id', $bigcommerceProductId)
            ->with('vehicle')
            ->get()
            ->pluck('vehicle');
    }
}
