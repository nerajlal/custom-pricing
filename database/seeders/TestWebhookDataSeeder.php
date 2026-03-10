<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;
use App\Models\CustomerLoyaltyAccount;
use App\Models\LoyaltySetting;

class TestWebhookDataSeeder extends Seeder
{
    public function run()
    {
        // Create test store
        $store = Store::firstOrCreate(
            ['shop_domain' => 'test.myshopify.com'],
            [
                'access_token' => 'test_token_' . bin2hex(random_bytes(16)),
            ]
        );

        echo "✓ Store created/found: {$store->shop_domain} (ID: {$store->id})\n";

        // Create loyalty settings for the store
        $settings = LoyaltySetting::firstOrCreate(
            ['store_id' => $store->id],
            [
                'is_enabled' => true,
                'points_per_dollar' => 10,
                'points_value_cents' => 1,
                'min_points_redemption' => 100,
                'points_expiry_days' => 365,
                'signup_bonus_enabled' => true,
                'signup_bonus_points' => 50,
                'birthday_bonus_enabled' => false,
                'birthday_bonus_points' => 0,
            ]
        );

        echo "✓ Loyalty settings created/found\n";

        // Create test customer loyalty account (CORRECT COLUMNS)
        $account = CustomerLoyaltyAccount::firstOrCreate(
            [
                'store_id' => $store->id,
                'shopify_customer_id' => '123',
            ],
            [
                'customer_email' => 'customer@example.com',
                'total_points_earned' => 1000,
                'current_points_balance' => 250,
                'points_redeemed' => 750,
            ]
        );

        echo "✓ Test customer loyalty account created/found (ID: {$account->id})\n";
        echo "  - Email: {$account->customer_email}\n";
        echo "  - Points: {$account->current_points_balance}\n";

        // Create another test customer
        $account2 = CustomerLoyaltyAccount::firstOrCreate(
            [
                'store_id' => $store->id,
                'shopify_customer_id' => '456',
            ],
            [
                'customer_email' => 'customer2@example.com',
                'total_points_earned' => 2000,
                'current_points_balance' => 500,
                'points_redeemed' => 1500,
            ]
        );

        echo "✓ Test customer 2 created/found (ID: {$account2->id})\n";
        
        echo "\n✅ Test data seeded successfully!\n";
    }
}

