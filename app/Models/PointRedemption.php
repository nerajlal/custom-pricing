<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Point Redemption Model
class PointRedemption extends Model
{
    protected $fillable = [
        'customer_loyalty_account_id',
        'points_used',
        'discount_amount',
        'coupon_code',
        'is_used',
        'shopify_order_id',
        'used_at',
        'expires_at'
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    public function account()
    {
        return $this->belongsTo(CustomerLoyaltyAccount::class, 'customer_loyalty_account_id');
    }

    public static function generateCouponCode()
    {
        do {
            $code = 'LOYALTY' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('coupon_code', $code)->exists());

        return $code;
    }
}