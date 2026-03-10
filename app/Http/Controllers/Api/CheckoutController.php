<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShopifyGraphqlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * CORS Headers
     */
    private function corsHeaders()
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization',
        ];
    }

    /**
     * Handle OPTIONS preflight
     */
    public function handlePreflight()
    {
        return response()->json([], 200, $this->corsHeaders());
    }
    
    /**
     * Create custom checkout with custom pricing and loyalty discount
     */
    public function createCustomCheckout(Request $request)
    {
        // Handle CORS preflight
        if ($request->isMethod('options')) {
            return $this->handlePreflight();
        }
    
        try {
            // Validate request
            $validated = $request->validate([
                'customer_id' => 'required|integer',
                'shop' => 'required|string',
                'cart_items' => 'required|array',
                'cart_items.*.variant_id' => 'required|integer',
                'cart_items.*.quantity' => 'required|integer|min:1',
                'cart_items.*.price' => 'required|integer|min:0',
                'custom_prices' => 'nullable|array',
                'loyalty_discount_code' => 'nullable|string' // NEW: Accept loyalty discount
            ]);
    
            $customerId = $validated['customer_id'];
            $shop = $validated['shop'];
            $cartItems = $validated['cart_items'];
            $customPrices = $validated['custom_prices'] ?? [];
            $loyaltyDiscountCode = $validated['loyalty_discount_code'] ?? null;
    
            Log::info('🛒 Creating custom checkout', [
                'customer_id' => $customerId,
                'shop' => $shop,
                'items_count' => count($cartItems),
                'has_custom_prices' => !empty($customPrices),
                'loyalty_discount' => $loyaltyDiscountCode
            ]);
    
            // Build line items
            $lineItems = $this->buildLineItems($shop, $cartItems, $customPrices);
    
            // Create draft order
            $draftOrder = $this->createShopifyDraftOrder(
                $shop, 
                $customerId, 
                $lineItems, 
                $loyaltyDiscountCode
            );
    
            // Get checkout URL
            $checkoutUrl = $draftOrder['invoiceUrl'];
    
            Log::info('✅ Checkout created successfully', [
                'draft_order_id' => $draftOrder['id'],
                'checkout_url' => $checkoutUrl
            ]);
    
            return response()->json([
                'success' => true,
                'checkout_url' => $checkoutUrl,
                'draft_order_id' => $draftOrder['id'],
                'loyalty_discount_applied' => !empty($loyaltyDiscountCode)
            ], 200, $this->corsHeaders());
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed', [
                'errors' => $e->errors()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422, $this->corsHeaders());
    
        } catch (\Exception $e) {
            Log::error('❌ Checkout creation failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to create checkout: ' . $e->getMessage()
            ], 500, $this->corsHeaders());
        }
    }
    
    /**
     * Build line items for draft order
     */
    private function buildLineItems($shop, $cartItems, $customPrices)
    {
        $lineItems = [];
        
        foreach ($cartItems as $item) {
            $variantId = (string)$item['variant_id'];
            $originalPrice = $item['price'] / 100; // Convert from cents
            $quantity = $item['quantity'];
            
            // Check if this variant has a custom price
            if (isset($customPrices[$variantId])) {
                $customPrice = (float)$customPrices[$variantId]['custom'];
                
                Log::info('💰 Using custom price', [
                    'variant_id' => $variantId,
                    'original' => $originalPrice,
                    'custom' => $customPrice,
                    'savings' => $originalPrice - $customPrice
                ]);
                
                // Get product details for custom line item
                $variantDetails = $this->getVariantDetails($shop, $variantId);
                
                // Create CUSTOM line item (without variant_id so we can set custom price)
                $lineItem = [
                    'title' => $variantDetails['title'],
                    'originalUnitPrice' => number_format($customPrice, 2, '.', ''), // GraphQL expects originalUnitPrice for custom items
                    'quantity' => $quantity,
                    'taxable' => true,
                    'requiresShipping' => true,
                ];
                
                // Add SKU if available
                if (!empty($variantDetails['sku'])) {
                    $lineItem['sku'] = $variantDetails['sku'];
                }
                
                // Add custom properties to show it's a custom price
                $lineItem['customAttributes'] = [
                    ['key' => '_custom_pricing', 'value' => 'true'],
                    ['key' => 'Original Price', 'value' => '₹' . number_format($originalPrice, 2)],
                    ['key' => 'Your Price', 'value' => '₹' . number_format($customPrice, 2)]
                ];
                
                $lineItems[] = $lineItem;
                
            } else {
                // No custom price - use standard variant_id
                Log::info('📦 Using regular price', [
                    'variant_id' => $variantId,
                    'price' => $originalPrice
                ]);
                
                $lineItems[] = [
                    'variantId' => "gid://shopify/ProductVariant/{$variantId}",
                    'quantity' => $quantity
                ];
            }
        }
        
        Log::info('📋 Built line items', [
            'total_items' => count($lineItems)
        ]);
        
        return $lineItems;
    }
    
    /**
     * Get variant details from Shopify
     */
    private function getVariantDetails($shop, $variantId)
    {
        try {
            $accessToken = $this->getShopAccessToken($shop);
            $service = new ShopifyGraphqlService();

            $query = <<<'GRAPHQL'
            query productVariant($id: ID!) {
                productVariant(id: $id) {
                    title
                    sku
                    product {
                        title
                    }
                }
            }
            GRAPHQL;

            $data = $service->query($shop, $accessToken, $query, ['id' => "gid://shopify/ProductVariant/{$variantId}"]);
            
            if (empty($data['productVariant'])) {
                return [
                    'title' => 'Product (ID: ' . $variantId . ')',
                    'sku' => null
                ];
            }

            $variant = $data['productVariant'];
            $title = $variant['product']['title'];
            
            if (!empty($variant['title']) && $variant['title'] !== 'Default Title') {
                $title .= ' - ' . $variant['title'];
            }
            
            return [
                'title' => $title,
                'sku' => $variant['sku'] ?? null
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Error fetching variant details', [
                'variant_id' => $variantId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'title' => 'Product (ID: ' . $variantId . ')',
                'sku' => null
            ];
        }
    }
    
    /**
     * Create Shopify draft order
     */
    private function createShopifyDraftOrder($shop, $customerId, $lineItems, $loyaltyDiscountCode = null)
    {
        $accessToken = $this->getShopAccessToken($shop);
        $service = new ShopifyGraphqlService();

        $mutation = <<<'GRAPHQL'
        mutation draftOrderCreate($input: DraftOrderInput!) {
            draftOrderCreate(input: $input) {
                draftOrder {
                    id
                    invoiceUrl
                    name
                    totalPrice
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

        $input = [
            'customerId' => "gid://shopify/Customer/{$customerId}",
            'lineItems' => $lineItems,
            'useCustomerDefaultAddress' => true,
            'tags' => ['custom-pricing']
        ];

        // Handle loyalty discount
        $notes = [];
        if (!empty(array_filter($lineItems, function($item) { return !isset($item['variantId']); }))) {
            $notes[] = 'This order includes custom pricing for this customer.';
        }

        if ($loyaltyDiscountCode) {
             $redemption = DB::table('point_redemptions')
                ->where('coupon_code', $loyaltyDiscountCode)
                ->where('is_used', false)
                ->first();
            
            if ($redemption) {
                $discountAmount = $redemption->discount_amount;
                
                $input['appliedDiscount'] = [
                    'description' => 'Loyalty Points Discount',
                    'valueType' => 'FIXED_AMOUNT',
                    'value' => (float)$discountAmount,
                    'title' => "Loyalty Discount ({$loyaltyDiscountCode})"
                ];
                
                $notes[] = "Loyalty discount applied: ₹{$discountAmount} (Code: {$loyaltyDiscountCode})";
            }
        }

        if (!empty($notes)) {
            $input['note'] = implode(' ', $notes);
        }

        $data = $service->query($shop, $accessToken, $mutation, ['input' => $input]);

        if (!empty($data['draftOrderCreate']['userErrors'])) {
            Log::error('Draft order creation failed (GraphQL)', [
                'errors' => $data['draftOrderCreate']['userErrors']
            ]);
            throw new \Exception("Shopify Draft Order Error: " . json_encode($data['draftOrderCreate']['userErrors']));
        }

        $draftOrder = $data['draftOrderCreate']['draftOrder'];

        // Mark redemption as used
        if ($loyaltyDiscountCode && isset($redemption)) {
             DB::table('point_redemptions')
                ->where('id', $redemption->id)
                ->update([
                    'is_used' => true,
                    'used_at' => now()
                ]);
        }

        return $draftOrder;
    }
    
    /**
     * Get access token for shop from database
     */
    private function getShopAccessToken($shop)
    {
        try {
            $store = DB::table('stores')
                ->where('shop_domain', $shop)
                ->first();
            
            if (!$store || empty($store->access_token)) {
                return null;
            }
            
            return $store->access_token;
            
        } catch (\Exception $e) {
            return null;
        }
    }
}
