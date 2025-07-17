<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BigCommerceStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_hash',
        'store_name',
        'access_token',
        'user_id',
        'user_email', 
        'owner_id',
        'owner_email',
        'scope',
        'installed_at',
        'last_accessed_at',
        'active'
    ];

    protected $casts = [
        'scope' => 'array',
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
