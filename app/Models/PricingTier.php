<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'description'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customPrices()
    {
        return $this->hasMany(CustomPrice::class);
    }

    public function customers()
    {
        return $this->hasMany(CustomerPricingSetting::class);
    }
}
