<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'domain',
        'logo_url',
        'primary_color',
        'secondary_color',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    /**
     * Get the BigCommerce store for this client
     */
    public function bigcommerceStore(): HasOne
    {
        return $this->hasOne(BigCommerceStore::class);
    }

    /**
     * Get all vehicles for this client
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get all product vehicle relationships for this client
     */
    public function productVehicles(): HasMany
    {
        return $this->hasMany(ProductVehicle::class);
    }

    /**
     * Get active vehicles only
     */
    public function activeVehicles(): HasMany
    {
        return $this->vehicles()->where('is_active', true);
    }

    /**
     * Check if client has BigCommerce store configured
     */
    public function hasBigCommerceStore(): bool
    {
        return $this->bigcommerceStore()->exists();
    }

    /**
     * Get client's BigCommerce API configuration
     */
    public function getBigCommerceConfig(): array
    {
        $store = $this->bigcommerceStore;

        if (!$store) {
            return [];
        }

        return [
            'store_hash' => $store->store_hash,
            'access_token' => $store->access_token,
            'client_id' => config('bigcommerce.app.client_id'),
            'client_secret' => config('bigcommerce.app.secret'),
        ];
    }

    /**
     * Get default settings for a new client
     */
    public static function getDefaultSettings(): array
    {
        return [
            'filter_style' => 'dropdown', // dropdown, modal, sidebar
            'show_year_first' => true,
            'enable_search' => true,
            'auto_filter_products' => true,
            'show_no_match_message' => true,
            'custom_css' => '',
            'widget_position' => 'top', // top, bottom, sidebar
        ];
    }
}
