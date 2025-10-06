<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'shop_domain',
        'access_token'
    ];

    protected $hidden = [
        'access_token'
    ];

    public function customerPricingSettings()
    {
        return $this->hasMany(CustomerPricingSetting::class);
    }
}
