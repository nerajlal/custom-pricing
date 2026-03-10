<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Loyalty Tier Model
class LoyaltyTier extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'min_points_required',
        'points_multiplier',
        'discount_percentage',
        'color',
        'order'
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customers()
    {
        return $this->hasMany(CustomerLoyaltyAccount::class, 'current_tier_id');
    }

    public function getMultiplierAttribute()
    {
        return $this->points_multiplier / 100;
    }
}