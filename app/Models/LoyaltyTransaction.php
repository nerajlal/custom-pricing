<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Loyalty Transaction Model
class LoyaltyTransaction extends Model
{
    protected $fillable = [
        'customer_loyalty_account_id',
        'type',
        'points',
        'balance_after',
        'description',
        'shopify_order_id',
        'order_name',
        'order_amount',
        'expires_at'
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
        'expires_at' => 'date'
    ];

    public function account()
    {
        return $this->belongsTo(CustomerLoyaltyAccount::class, 'customer_loyalty_account_id');
    }

    public function getFormattedPointsAttribute()
    {
        return $this->points >= 0 ? '+' . $this->points : $this->points;
    }
}