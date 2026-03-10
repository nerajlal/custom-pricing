<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'pricing_tier_id',
        'customer_pricing_setting_id',
        'shopify_variant_id',
        'shopify_product_id',
        'product_title',
        'variant_title',
        'original_price',
        'custom_price'
    ];

    public function tier()
    {
        return $this->belongsTo(PricingTier::class, 'pricing_tier_id');
    }

    public function customerPricingSetting()
    {
        return $this->belongsTo(CustomerPricingSetting::class, 'customer_pricing_setting_id');
    }
}
