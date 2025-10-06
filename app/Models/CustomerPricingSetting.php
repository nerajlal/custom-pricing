<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPricingSetting extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_customer_id',
        'customer_email',
        'is_custom_pricing_enabled'
    ];

    protected $casts = [
        'is_custom_pricing_enabled' => 'boolean',
        'shopify_customer_id' => 'integer'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customPrices()
    {
        return $this->hasMany(CustomPrice::class);
    }
}
