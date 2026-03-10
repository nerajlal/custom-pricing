<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPricingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'shopify_customer_id',
        'customer_email',
        'is_custom_pricing_enabled',
        'pricing_tier_id'
    ];

    protected $casts = [
        'is_custom_pricing_enabled' => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function tier()
    {
        return $this->belongsTo(PricingTier::class, 'pricing_tier_id');
    }

    public function customPrices()
    {
        return $this->hasMany(CustomPrice::class, 'customer_pricing_setting_id');
    }
}
