<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'year_start',
        'year_end',
        'make',
        'model',
        'is_active'
    ];

    protected $casts = [
        'year_start' => 'integer',
        'year_end' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Check if this vehicle compatibility covers a specific year
     */
    public function coversYear($year): bool
    {
        return $year >= $this->year_start && $year <= $this->year_end;
    }

    /**
     * Get all vehicles that match a specific year, make, and model
     */
    public static function findCompatible($year, $make, $model)
    {
        return static::where('is_active', true)
            ->where('make', $make)
            ->where('model', $model)
            ->where('year_start', '<=', $year)
            ->where('year_end', '>=', $year)
            ->get();
    }

    /**
     * Get unique makes
     */
    public static function getUniqueMakes()
    {
        return static::where('is_active', true)
            ->distinct()
            ->orderBy('make')
            ->pluck('make');
    }

    /**
     * Get unique models for a specific make
     */
    public static function getModelsForMake($make)
    {
        return static::where('is_active', true)
            ->where('make', $make)
            ->distinct()
            ->orderBy('model')
            ->pluck('model');
    }
}
