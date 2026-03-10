<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Loyalty Settings Model
class LoyaltySetting extends Model
{
    protected $fillable = [
        'store_id',
        'is_enabled',
        'points_per_dollar',
        'points_value_cents',
        'min_points_redemption',
        'points_expiry_days',
        'signup_bonus_enabled',
        'signup_bonus_points',
        'birthday_bonus_enabled',
        'birthday_bonus_points',
        'allow_all_customers'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'signup_bonus_enabled' => 'boolean',
        'birthday_bonus_enabled' => 'boolean',
        'allow_all_customers' => 'boolean'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function calculatePointsForAmount($amount)
    {
        return floor($amount * $this->points_per_dollar);
    }

    public function calculateDiscountForPoints($points)
    {
        return ($points * $this->points_value_cents) / 100;
    }
}

