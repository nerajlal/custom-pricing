<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\LoyaltySetting;
use App\Models\LoyaltyTier;
use App\Models\CustomerLoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\PointRedemption;
use App\Services\ShopifyGraphqlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoyaltyController extends Controller
{
    // Get or create loyalty settings
    public function getSettings(Request $request)
    {
        try {
            $request->validate(['shop' => 'required|string']);
            $store = Store::where('shop_domain', $request->shop)->first();
            
            if (!$store) {
                return response()->json(['error' => 'Store not found'], 404);
            }

            $settings = LoyaltySetting::firstOrCreate(
                ['store_id' => $store->id],
                [
                    'is_enabled' => true,
                    'points_per_dollar' => 10,
                    'points_value_cents' => 10,
                    'min_points_redemption' => 100,
                    'signup_bonus_enabled' => true,
                    'signup_bonus_points' => 100,
                    'birthday_bonus_enabled' => true,
                    'birthday_bonus_points' => 200
                ] + (\Schema::hasColumn('loyalty_settings', 'allow_all_customers') ? ['allow_all_customers' => false] : [])
            );

            return response()->json($settings);
        } catch (\Exception $e) {
            Log::error('Loyalty getSettings error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get settings', 'message' => $e->getMessage()], 500);
        }
    }

    // Update loyalty settings
    public function updateSettings(Request $request)
    {
        Log::info('Loyalty updateSettings request', ['shop' => $request->shop, 'settings' => $request->settings]);
        try {
            $request->validate([
                'shop' => 'required|string',
                'settings' => 'required|array'
            ]);

            $store = Store::where('shop_domain', $request->shop)->first();
            if (!$store) {
                return response()->json(['error' => 'Store not found'], 404);
            }

            $settings = LoyaltySetting::where('store_id', $store->id)->first();
            if (!$settings) {
                return response()->json(['error' => 'Settings not found'], 404);
            }

            // Safety check for allow_all_customers column
            $settingsData = $request->settings;
            if (isset($settingsData['allow_all_customers']) && !\Schema::hasColumn('loyalty_settings', 'allow_all_customers')) {
                Log::warning('allow_all_customers column missing in loyalty_settings table. Please run migration.');
                unset($settingsData['allow_all_customers']);
            }

            $settings->update($settingsData);

            return response()->json(['message' => 'Settings updated', 'settings' => $settings]);
        } catch (\Exception $e) {
            Log::error('Loyalty updateSettings error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update settings', 'message' => $e->getMessage()], 500);
        }
    }

    // Get loyalty tiers
    public function getTiers(Request $request)
    {
        try {
            $request->validate(['shop' => 'required|string']);
            $store = Store::where('shop_domain', $request->shop)->first();
            
            if (!$store) {
                return response()->json(['error' => 'Store not found'], 404);
            }

            $tiers = LoyaltyTier::where('store_id', $store->id)
                ->orderBy('order')
                ->get();

            // Create default tiers if none exist
            if ($tiers->isEmpty()) {
                $defaultTiers = [
                    ['name' => 'Bronze', 'min_points_required' => 0, 'points_multiplier' => 100, 'discount_percentage' => 0, 'color' => '#CD7F32', 'order' => 1],
                    ['name' => 'Silver', 'min_points_required' => 500, 'points_multiplier' => 110, 'discount_percentage' => 5, 'color' => '#C0C0C0', 'order' => 2],
                    ['name' => 'Gold', 'min_points_required' => 1000, 'points_multiplier' => 125, 'discount_percentage' => 10, 'color' => '#FFD700', 'order' => 3],
                    ['name' => 'Platinum', 'min_points_required' => 2000, 'points_multiplier' => 150, 'discount_percentage' => 15, 'color' => '#E5E4E2', 'order' => 4]
                ];

                foreach ($defaultTiers as $tierData) {
                    $tierData['store_id'] = $store->id;
                    $tiers[] = LoyaltyTier::create($tierData);
                }
            }

            return response()->json($tiers);
        } catch (\Exception $e) {
            Log::error('Loyalty getTiers error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get tiers', 'message' => $e->getMessage()], 500);
        }
    }

    // Search customer for loyalty
    public function searchCustomerLoyalty(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'shop' => 'required|string'
            ]);

            $store = Store::where('shop_domain', $request->shop)->first();
            if (!$store) {
                return response()->json(['error' => 'Store not found'], 404);
            }

            $settings = LoyaltySetting::where('store_id', $store->id)->first();

            // Get customer from Shopify using GraphQL
            $service = new ShopifyGraphqlService();
            
            $query = <<<'GRAPHQL'
            query customers($query: String!) {
                customers(first: 1, query: $query) {
                    edges {
                        node {
                            id
                            email
                        }
                    }
                }
            }
            GRAPHQL;

            $data = $service->query($store->shop_domain, $store->access_token, $query, [
                'query' => "email:{$request->email}"
            ]);

            $edges = $data['customers']['edges'] ?? [];

            if (empty($edges)) {
                return response()->json(['message' => 'Customer not found'], 404);
            }

            $customerNode = $edges[0]['node'];
            // Extract numeric ID from GID
            $numericId = (int) filter_var($customerNode['id'], FILTER_SANITIZE_NUMBER_INT);
            $customer = [
                'id' => $numericId,
                'email' => $customerNode['email']
            ];

            // Get or create loyalty account
            $account = CustomerLoyaltyAccount::firstOrCreate(
                [
                    'store_id' => $store->id,
                    'shopify_customer_id' => $customer['id']
                ],
                [
                    'customer_email' => $customer['email'],
                    'current_points_balance' => 0,
                    'total_points_earned' => 0,
                    'points_redeemed' => 0,
                ] + (\Schema::hasColumn('customer_loyalty_accounts', 'is_enabled') ? ['is_enabled' => true] : [])
            );

            // Give signup bonus if new account
            if ($account->wasRecentlyCreated && $settings && $settings->signup_bonus_enabled) {
                $account->addPoints(
                    $settings->signup_bonus_points,
                    'bonus',
                    'Welcome bonus for joining loyalty program'
                );
                $account->refresh(); // Reload to get updated balance
            }

            // Load relationships
            $account->load('tier', 'transactions');

            return response()->json([
                'customer' => $customer,
                'loyalty_account' => $account
            ]);

        } catch (\Exception $e) {
            Log::error('Loyalty searchCustomerLoyalty error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to search customer', 
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    // Toggle customer loyalty status
    public function toggleCustomerLoyalty(Request $request)
    {
        Log::info('Loyalty toggleCustomerLoyalty request', $request->all());
        try {
            if (!\Schema::hasColumn('customer_loyalty_accounts', 'is_enabled')) {
                Log::error('is_enabled column missing in customer_loyalty_accounts table. Please run migration.');
                return response()->json(['error' => 'Database update required. Please run php artisan migrate.'], 500);
            }
            $request->validate([
                'account_id' => 'required|integer',
                'enabled' => 'required|boolean'
            ]);

            $account = CustomerLoyaltyAccount::find($request->account_id);
            if (!$account) {
                return response()->json(['error' => 'Account not found'], 404);
            }

            $account->is_enabled = $request->enabled;
            $account->save();

            return response()->json([
                'message' => 'Customer loyalty ' . ($request->enabled ? 'enabled' : 'disabled') . ' successfully',
                'account' => $account
            ]);
        } catch (\Exception $e) {
            Log::error('Loyalty toggleCustomerLoyalty error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to toggle status'], 500);
        }
    }

    // Get customer loyalty details
    public function getCustomerLoyalty(Request $request, $accountId)
    {
        try {
            $account = CustomerLoyaltyAccount::with(['tier', 'transactions' => function($q) {
                $q->orderBy('created_at', 'desc')->limit(50);
            }])->find($accountId);

            if (!$account) {
                return response()->json(['error' => 'Account not found'], 404);
            }

            return response()->json($account);
        } catch (\Exception $e) {
            Log::error('Loyalty getCustomerLoyalty error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get account', 'message' => $e->getMessage()], 500);
        }
    }

    // Manually adjust points
    public function adjustPoints(Request $request)
    {
        try {
            $request->validate([
                'account_id' => 'required|integer',
                'points' => 'required|integer',
                'reason' => 'required|string'
            ]);

            $account = CustomerLoyaltyAccount::find($request->account_id);
            if (!$account) {
                return response()->json(['error' => 'Account not found'], 404);
            }

            if ($request->points > 0) {
                $account->addPoints($request->points, 'adjust', $request->reason);
            } else {
                $account->deductPoints(abs($request->points), 'adjust', $request->reason);
            }

            $account->refresh();
            $account->load('tier', 'transactions');

            return response()->json([
                'message' => 'Points adjusted successfully',
                'account' => $account
            ]);
        } catch (\Exception $e) {
            Log::error('Loyalty adjustPoints error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to adjust points', 'message' => $e->getMessage()], 500);
        }
    }

    // Redeem points for discount
    public function redeemPoints(Request $request)
    {
        try {
            $request->validate([
                'account_id' => 'required|integer',
                'points' => 'required|integer|min:1'
            ]);

            $account = CustomerLoyaltyAccount::find($request->account_id);
            if (!$account) {
                return response()->json(['error' => 'Account not found'], 404);
            }

            $settings = LoyaltySetting::where('store_id', $account->store_id)->first();
            if (!$settings) {
                return response()->json(['error' => 'Settings not found'], 404);
            }

            // Check minimum redemption
            if ($request->points < $settings->min_points_redemption) {
                return response()->json([
                    'message' => "Minimum {$settings->min_points_redemption} points required"
                ], 400);
            }

            // Check balance
            if ($account->current_points_balance < $request->points) {
                return response()->json(['message' => 'Insufficient points'], 400);
            }

            // Calculate discount
            $discountAmount = $settings->calculateDiscountForPoints($request->points);

            // Create redemption
            $redemption = PointRedemption::create([
                'customer_loyalty_account_id' => $account->id,
                'points_used' => $request->points,
                'discount_amount' => $discountAmount,
                'coupon_code' => PointRedemption::generateCouponCode(),
                'expires_at' => now()->addDays(30)
            ]);

            // Deduct points
            $account->deductPoints(
                $request->points,
                'redeem',
                "Redeemed for \${$discountAmount} discount - Code: {$redemption->coupon_code}"
            );

            $account->refresh();

            return response()->json([
                'message' => 'Points redeemed successfully',
                'redemption' => $redemption,
                'remaining_balance' => $account->current_points_balance
            ]);
        } catch (\Exception $e) {
            Log::error('Loyalty redeemPoints error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to redeem points', 'message' => $e->getMessage()], 500);
        }
    }

    // Get customer loyalty info for storefront
    public function getStorefrontLoyalty(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|integer',
                'shop' => 'required|string'
            ]);

            $store = Store::where('shop_domain', $request->shop)->first();
            if (!$store) {
                return response()->json(['has_loyalty' => false]);
            }

            $settings = LoyaltySetting::where('store_id', $store->id)->first();
            if (!$settings || !$settings->is_enabled) {
                return response()->json(['has_loyalty' => false]);
            }

            $account = CustomerLoyaltyAccount::with('tier')
                ->where('store_id', $store->id)
                ->where('shopify_customer_id', $request->customer_id)
                ->first();

            // Logic:
            // 1. If global toggle "allow_all_customers" is TRUE:
            //    - If account exists AND is_enabled is FALSE, deny.
            //    - Otherwise, allow (create account if doesn't exist).
            // 2. If "allow_all_customers" is FALSE:
            //    - Only allow if account exists AND is_enabled is TRUE.

            $hasLoyalty = false;
            if ($settings->allow_all_customers) {
                if ($account) {
                    $hasLoyalty = $account->is_enabled;
                } else {
                    $hasLoyalty = true; // All allowed by default
                }
            } else {
                $hasLoyalty = $account ? $account->is_enabled : false;
            }

            if (!$hasLoyalty) {
                return response()->json(['has_loyalty' => false]);
            }

            // If we allow all but account doesn't exist, we might want to return some defaults
            return response()->json([
                'has_loyalty' => true,
                'points_balance' => $account ? $account->current_points_balance : 0,
                'tier' => $account ? $account->tier : null,
                'points_value' => $account ? $settings->calculateDiscountForPoints($account->current_points_balance) : 0
            ]);
        } catch (\Exception $e) {
            Log::error('Loyalty getStorefrontLoyalty error: ' . $e->getMessage());
            return response()->json(['has_loyalty' => false]);
        }
    }

    // Process order and award points (webhook handler)
    public function processOrder(Request $request)
    {
        try {
            $shop = $request->header('X-Shopify-Shop-Domain');
            $order = $request->all();

            Log::info('Order webhook received', ['shop' => $shop, 'order_id' => $order['id'] ?? null]);

            $store = Store::where('shop_domain', $shop)->first();
            if (!$store) {
                return response()->json(['message' => 'Store not found'], 404);
            }

            $settings = LoyaltySetting::where('store_id', $store->id)->first();
            if (!$settings || !$settings->is_enabled) {
                return response()->json(['message' => 'Loyalty disabled'], 200);
            }

            if (!isset($order['customer']) || !$order['customer']) {
                return response()->json(['message' => 'No customer'], 200);
            }

            $account = CustomerLoyaltyAccount::where('store_id', $store->id)
                ->where('shopify_customer_id', $order['customer']['id'])
                ->first();

            // Check if we should award points based on global vs per-customer logic
            $shouldAward = false;
            if ($settings->allow_all_customers) {
                if ($account) {
                    $shouldAward = $account->is_enabled;
                } else {
                    // Create account automatically
                    $account = CustomerLoyaltyAccount::create([
                        'store_id' => $store->id,
                        'shopify_customer_id' => $order['customer']['id'],
                        'customer_email' => $order['customer']['email'] ?? '',
                        'is_enabled' => true
                    ]);
                    $shouldAward = true;
                }
            } else {
                $shouldAward = $account ? $account->is_enabled : false;
            }

            if (!$shouldAward || !$account) {
                return response()->json(['message' => 'Loyalty not active for this customer'], 200);
            }

            // Calculate points
            $orderTotal = floatval($order['total_price']);
            $points = $settings->calculatePointsForAmount($orderTotal);

            // Apply tier multiplier
            if ($account->tier) {
                $points = floor($points * ($account->tier->points_multiplier / 100));
            }

            // Award points
            $account->addPoints($points, 'earn', "Purchase order {$order['name']}", [
                'order_id' => $order['id'],
                'order_name' => $order['name'],
                'order_amount' => $orderTotal
            ]);

            Log::info('Points awarded', ['customer_id' => $order['customer']['id'], 'points' => $points]);

            return response()->json(['message' => 'Points awarded', 'points' => $points]);

        } catch (\Exception $e) {
            Log::error('Loyalty processOrder error: ' . $e->getMessage());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Get customer transaction history for storefront
     */
    public function getTransactionHistory(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|integer',
                'shop' => 'required|string'
            ]);

            $store = Store::where('shop_domain', $request->shop)->first();
            if (!$store) {
                return response()->json(['transactions' => []]);
            }

            $account = CustomerLoyaltyAccount::where('store_id', $store->id)
                ->where('shopify_customer_id', $request->customer_id)
                ->first();

            if (!$account) {
                return response()->json(['transactions' => []]);
            }

            // Get transactions with limit
            $transactions = LoyaltyTransaction::where('customer_loyalty_account_id', $account->id)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'transactions' => $transactions,
                'total_count' => $transactions->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching transaction history: ' . $e->getMessage());
            return response()->json(['transactions' => []]);
        }
    }

    /**
     * Get loyalty settings for storefront display
     */
    public function getStorefrontSettings(Request $request)
    {
        try {
            $request->validate(['shop' => 'required|string']);
            
            $store = Store::where('shop_domain', $request->shop)->first();
            if (!$store) {
                return response()->json([
                    'points_per_dollar' => 10,
                    'points_value_cents' => 10,
                    'min_points_redemption' => 100
                ]);
            }

            $settings = LoyaltySetting::where('store_id', $store->id)->first();
            
            if (!$settings) {
                return response()->json([
                    'points_per_dollar' => 10,
                    'points_value_cents' => 10,
                    'min_points_redemption' => 100
                ]);
            }

            return response()->json([
                'points_per_dollar' => $settings->points_per_dollar,
                'points_value_cents' => $settings->points_value_cents,
                'min_points_redemption' => $settings->min_points_redemption
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching settings: ' . $e->getMessage());
            return response()->json([
                'points_per_dollar' => 10,
                'points_value_cents' => 10,
                'min_points_redemption' => 100
            ]);
        }
    }

    /**
     * Get all tiers for storefront display
     */
    public function getStorefrontTiers(Request $request)
    {
        try {
            $request->validate(['shop' => 'required|string']);
            
            $store = Store::where('shop_domain', $request->shop)->first();
            if (!$store) {
                return response()->json(['tiers' => $this->getDefaultTiers()]);
            }

            $tiers = LoyaltyTier::where('store_id', $store->id)
                ->orderBy('order')
                ->get();

            if ($tiers->isEmpty()) {
                return response()->json(['tiers' => $this->getDefaultTiers()]);
            }

            return response()->json(['tiers' => $tiers]);

        } catch (\Exception $e) {
            Log::error('Error fetching tiers: ' . $e->getMessage());
            return response()->json(['tiers' => $this->getDefaultTiers()]);
        }
    }

    /**
     * Get default tier structure
     */
    private function getDefaultTiers()
    {
        return [
            ['name' => 'Bronze', 'min_points_required' => 0, 'points_multiplier' => 100, 'discount_percentage' => 0, 'color' => '#CD7F32'],
            ['name' => 'Silver', 'min_points_required' => 500, 'points_multiplier' => 110, 'discount_percentage' => 5, 'color' => '#C0C0C0'],
            ['name' => 'Gold', 'min_points_required' => 1000, 'points_multiplier' => 125, 'discount_percentage' => 10, 'color' => '#FFD700'],
            ['name' => 'Platinum', 'min_points_required' => 2000, 'points_multiplier' => 150, 'discount_percentage' => 15, 'color' => '#E5E4E2']
        ];
    }
}
