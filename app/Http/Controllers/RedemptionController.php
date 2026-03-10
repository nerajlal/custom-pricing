<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\LoyaltySetting;
use App\Models\CustomerLoyaltyAccount;
use App\Models\PointRedemption;
use App\Services\ShopifyGraphqlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RedemptionController extends Controller
{
    // ... (Previous methods createRedemption, getActiveRedemptions, cancelRedemption stay the same, I will only replace the helper methods at the bottom and imports)

    // Create redemption
    public function createRedemption(Request $request)
    {
        // ... (Keep existing logic, it calls createShopifyDiscount which I will refactor)
        // I need to include the full method body since replace_file_content replaces the whole block if I selected a range, 
        // OR I can just target the specific helper methods if I am careful. 
        // However, I also need to update the imports.
        // To be safe and since the tool requires specific ranges, I will assume I am replacing the whole file content or a large chunk.
        // The user prompt implies I should "convert all". I will rewrite the whole file to ensure imports and methods are correct.
        
        try {
            Log::info('Redemption request received', $request->all());
            
            $request->validate([
                'customer_id' => 'required|integer',
                'points' => 'required|integer|min:1',
                'shop' => 'required|string'
            ]);

            $store = Store::where('shop_domain', $request->shop)->first();
            if (!$store) {
                return response()->json(['error' => 'Store not found'], 404);
            }

            $settings = LoyaltySetting::where('store_id', $store->id)->first();
            if (!$settings || !$settings->is_enabled) {
                return response()->json(['error' => 'Loyalty program not enabled'], 400);
            }

            $account = CustomerLoyaltyAccount::where('store_id', $store->id)
                ->where('shopify_customer_id', $request->customer_id)
                ->first();

            if (!$account) {
                return response()->json(['error' => 'Loyalty account not found'], 404);
            }

            if (!$account->is_enabled) {
                return response()->json(['error' => 'Loyalty is disabled for this customer'], 403);
            }

            // Validate points
            if ($request->points < $settings->min_points_redemption) {
                return response()->json([
                    'error' => "Minimum {$settings->min_points_redemption} points required"
                ], 400);
            }

            if ($account->current_points_balance < $request->points) {
                return response()->json([
                    'error' => 'Insufficient points',
                    'available' => $account->current_points_balance,
                    'requested' => $request->points
                ], 400);
            }

            // Calculate discount
            $discountAmount = ($request->points * $settings->points_value_cents) / 100;

            // Generate coupon code
            $couponCode = $this->generateCouponCode();

            // Create Shopify discount
            $priceRuleId = $this->createShopifyDiscount($store, $couponCode, $discountAmount, $account);
            
            if (!$priceRuleId) {
                return response()->json(['error' => 'Failed to create discount code'], 500);
            }

            // Create redemption record
            $redemption = PointRedemption::create([
                'customer_loyalty_account_id' => $account->id,
                'points_used' => $request->points,
                'discount_amount' => $discountAmount,
                'coupon_code' => $couponCode,
                'price_rule_id' => $priceRuleId,
                'is_used' => false,
                'expires_at' => now()->addDays(30)
            ]);

            // Deduct points
            $account->deductPoints(
                $request->points,
                'redeem',
                "Redeemed {$request->points} points for ₹{$discountAmount} discount - Code: {$couponCode}"
            );

            Log::info('Redemption created successfully', [
                'redemption_id' => $redemption->id,
                'coupon_code' => $couponCode
            ]);

            return response()->json([
                'success' => true,
                'redemption' => $redemption,
                'coupon_code' => $couponCode,
                'discount_amount' => $discountAmount,
                'remaining_balance' => $account->current_points_balance,
                'message' => "Successfully redeemed {$request->points} points!"
            ]);

        } catch (\Exception $e) {
            Log::error('Redemption error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => 'Failed to create redemption',
                'message' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    // Get active redemptions
    public function getActiveRedemptions(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|integer',
                'shop' => 'required|string'
            ]);

            $store = Store::where('shop_domain', $request->shop)->first();
            if (!$store) {
                return response()->json(['redemptions' => [], 'has_active' => false]);
            }

            $account = CustomerLoyaltyAccount::where('store_id', $store->id)
                ->where('shopify_customer_id', $request->customer_id)
                ->first();

            if (!$account) {
                return response()->json(['redemptions' => [], 'has_active' => false]);
            }

            $redemptions = PointRedemption::where('customer_loyalty_account_id', $account->id)
                ->where('is_used', false)
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'redemptions' => $redemptions,
                'has_active' => $redemptions->isNotEmpty()
            ]);

        } catch (\Exception $e) {
            Log::error('Get redemptions error: ' . $e->getMessage());
            return response()->json(['redemptions' => [], 'has_active' => false]);
        }
    }

    // Cancel redemption
    public function cancelRedemption(Request $request, $redemptionId)
    {
        try {
            $redemption = PointRedemption::find($redemptionId);
            
            if (!$redemption) {
                return response()->json(['error' => 'Redemption not found'], 404);
            }

            if ($redemption->is_used) {
                return response()->json(['error' => 'Redemption already used'], 400);
            }

            $account = $redemption->account;

            // Refund points
            $account->addPoints(
                $redemption->points_used,
                'refund',
                "Cancelled redemption - Code: {$redemption->coupon_code}"
            );

            // Delete Shopify discount
            $this->deleteShopifyDiscount($account->store, $redemption->price_rule_id);

            // Delete redemption
            $redemption->delete();

            return response()->json([
                'success' => true,
                'message' => 'Redemption cancelled',
                'refunded_points' => $redemption->points_used
            ]);

        } catch (\Exception $e) {
            Log::error('Cancel error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to cancel'], 500);
        }
    }

    // Helper: Generate coupon code
    private function generateCouponCode()
    {
        do {
            $code = 'LOYALTY' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        } while (PointRedemption::where('coupon_code', $code)->exists());

        return $code;
    }

    // Helper: Create Shopify discount using GraphQL
    private function createShopifyDiscount($store, $code, $amount, $account)
    {
        try {
            $service = new ShopifyGraphqlService();

            // Use discountCodeBasicCreate (Modern API)
            $mutation = <<<'GRAPHQL'
            mutation discountCodeBasicCreate($basicCodeDiscount: DiscountCodeBasicInput!) {
                discountCodeBasicCreate(basicCodeDiscount: $basicCodeDiscount) {
                    codeDiscountNode {
                        id
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            GRAPHQL;

            // Define the discount payload
            $variables = [
                'basicCodeDiscount' => [
                    'title' => "Loyalty Reward - {$code}",
                    'code' => $code,
                    'startsAt' => now()->toIso8601String(),
                    'endsAt' => now()->addDays(30)->toIso8601String(),
                    'customerSelection' => [
                        'customers' => [
                            'add' => ["gid://shopify/Customer/{$account->shopify_customer_id}"]
                        ]
                    ],
                    'customerGets' => [
                        'value' => [
                            'discountAmount' => [
                                'amount' => (string)$amount,
                                'appliesOnEachItem' => false
                            ]
                        ],
                        'items' => [
                            'all' => true
                        ]
                    ],
                    'usageLimit' => 1,
                    'appliesOncePerCustomer' => true
                ]
            ];

            $data = $service->query($store->shop_domain, $store->access_token, $mutation, $variables);

            if (!empty($data['discountCodeBasicCreate']['userErrors'])) {
                Log::error('Discount creation failed (GraphQL)', [
                    'errors' => $data['discountCodeBasicCreate']['userErrors']
                ]);
                return null;
            }

            // Return the GID
            return $data['discountCodeBasicCreate']['codeDiscountNode']['id'];

        } catch (\Exception $e) {
            Log::error('Shopify discount creation error: ' . $e->getMessage());
            return null;
        }
    }

    // Helper: Delete Shopify discount using GraphQL
    private function deleteShopifyDiscount($store, $discountId)
    {
        try {
            if (!$discountId) return;

            $service = new ShopifyGraphqlService();

            $mutation = <<<'GRAPHQL'
            mutation discountCodeDelete($id: ID!) {
                discountCodeDelete(id: $id) {
                    deletedCodeDiscountId
                    userErrors {
                        field
                        message
                    }
                }
            }
            GRAPHQL;

            $service->query($store->shop_domain, $store->access_token, $mutation, ['id' => $discountId]);

        } catch (\Exception $e) {
            Log::error('Delete discount error: ' . $e->getMessage());
        }
    }
}
