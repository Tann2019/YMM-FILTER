<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_hash',
        'year_start',
        'year_end',
        'make',
        'model',
        'trim',
        'engine',
        'is_active'
    ];

    protected $casts = [
        'year_start' => 'integer',
        'year_end' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the BigCommerce store that owns this vehicle
     */
    public function store()
    {
        return $this->belongsTo(BigCommerceStore::class, 'store_hash', 'store_hash');
    }

    /**
     * Check if this vehicle compatibility covers a specific year
     */
    public function coversYear($year): bool
    {
        return $year >= $this->year_start && $year <= $this->year_end;
    }

    /**
     * Get all vehicles that match a specific year, make, and model for a store
     */
    public static function findCompatible($year, $make, $model, $storeHash = null)
    {
        $query = static::where('is_active', true)
            ->where('make', $make)
            ->where('model', $model)
            ->where('year_start', '<=', $year)
            ->where('year_end', '>=', $year);

        if ($storeHash) {
            $query->where('store_hash', $storeHash);
        }

        return $query->get();
    }

    /**
     * Get unique makes for a store
     */
    public static function getUniqueMakes($storeHash = null)
    {
        $query = static::where('is_active', true);

        if ($storeHash) {
            $query->where('store_hash', $storeHash);
        }

        return $query->distinct()
            ->orderBy('make')
            ->pluck('make');
    }

    /**
     * Get unique models for a specific make and store
     */
    public static function getModelsForMake($make, $storeHash = null)
    {
        $query = static::where('is_active', true)
            ->where('make', $make);

        if ($storeHash) {
            $query->where('store_hash', $storeHash);
        }

        return $query->distinct()
            ->orderBy('model')
            ->pluck('model');
    }
}
