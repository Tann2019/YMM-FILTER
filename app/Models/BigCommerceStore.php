<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BigCommerceStore extends Model
{
    use HasFactory;

    protected $table = 'bigcommerce_stores';

    protected $fillable = [
        'store_hash',
        'store_name',
        'access_token',
        'user_id',
        'user_email',
        'owner_id',
        'owner_email',
        'scope',
        'settings',
        'installed_at',
        'last_accessed_at',
        'active'
    ];

    protected $casts = [
        'scope' => 'array',
        'settings' => 'array',
        'installed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'active' => 'boolean'
    ];

    /**
     * Encrypt/decrypt access token
     */
    public function getAccessTokenAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = $value ? encrypt($value) : null;
    }

    /**
     * Get the client that owns this store
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get all vehicles for this store
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'store_hash', 'store_hash');
    }

    /**
     * Get all product vehicle relationships for this store
     */
    public function productVehicles()
    {
        return $this->hasMany(ProductVehicle::class, 'store_hash', 'store_hash');
    }

    /**
     * Update last accessed timestamp
     */
    public function updateLastAccessed()
    {
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Check if store has specific scope
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scope ?? []);
    }

    /**
     * Get API URL for this store
     */
    public function getApiUrl(): string
    {
        return "https://api.bigcommerce.com/stores/{$this->store_hash}/v3";
    }
}
