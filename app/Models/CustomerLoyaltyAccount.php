<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Customer Loyalty Account Model
class CustomerLoyaltyAccount extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_customer_id',
        'customer_email',
        'total_points_earned',
        'current_points_balance',
        'points_redeemed',
        'current_tier_id',
        'birthday',
        'birthday_bonus_claimed_this_year'
    ];

    protected $casts = [
        'birthday' => 'date',
        'birthday_bonus_claimed_this_year' => 'boolean'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function tier()
    {
        return $this->belongsTo(LoyaltyTier::class, 'current_tier_id');
    }

    public function transactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function redemptions()
    {
        return $this->hasMany(PointRedemption::class);
    }

    public function addPoints($points, $type, $description, $orderData = [])
    {
        $this->current_points_balance += $points;
        $this->total_points_earned += $points;
        $this->save();

        $transaction = $this->transactions()->create([
            'type' => $type,
            'points' => $points,
            'balance_after' => $this->current_points_balance,
            'description' => $description,
            'shopify_order_id' => $orderData['order_id'] ?? null,
            'order_name' => $orderData['order_name'] ?? null,
            'order_amount' => $orderData['order_amount'] ?? null
        ]);

        $this->updateTier();
        return $transaction;
    }

    public function deductPoints($points, $type, $description)
    {
        if ($this->current_points_balance < $points) {
            throw new \Exception('Insufficient points balance');
        }

        $this->current_points_balance -= $points;
        $this->points_redeemed += $points;
        $this->save();

        return $this->transactions()->create([
            'type' => $type,
            'points' => -$points,
            'balance_after' => $this->current_points_balance,
            'description' => $description
        ]);
    }

    public function updateTier()
    {
        $tier = LoyaltyTier::where('store_id', $this->store_id)
            ->where('min_points_required', '<=', $this->total_points_earned)
            ->orderBy('min_points_required', 'desc')
            ->first();

        if ($tier && $this->current_tier_id !== $tier->id) {
            $this->current_tier_id = $tier->id;
            $this->save();
        }
    }
}