<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPrice extends Model
{
    protected $fillable = [
        'customer_pricing_setting_id',
        'shopify_product_id',
        'shopify_variant_id',
        'product_title',
        'variant_title',
        'original_price',
        'custom_price'
    ];

    protected $casts = [
        'shopify_product_id' => 'integer',
        'shopify_variant_id' => 'integer',
        'original_price' => 'decimal:2',
        'custom_price' => 'decimal:2'
    ];

    public function customerPricingSetting()
    {
        return $this->belongsTo(CustomerPricingSetting::class);
    }

    // Custom attribute for % discount
    public function getDiscountPercentageAttribute()
    {
        if ($this->original_price > 0) {
            return round((($this->original_price - $this->custom_price) / $this->original_price) * 100, 2);
        }
        return 0;
    }
}
