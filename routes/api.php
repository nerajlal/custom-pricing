<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomPricingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Api\CheckoutController as ApiCheckoutController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\PricingTierController;
use App\Http\Controllers\RedemptionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================
// SANCTUM AUTH (For future use)
// ============================================
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ============================================
// ADMIN API ROUTES (Protected by Shopify Session)
// ============================================
Route::prefix('admin')->middleware(['auth.shopify'])->group(function () {
    
    // Customer Management
    Route::post('/customers/search', [CustomPricingController::class, 'searchCustomer']);
    Route::post('/customers/assign-tier', [CustomPricingController::class, 'assignCustomerToTier']);
    Route::post('/customers/remove-tier', [CustomPricingController::class, 'removeCustomerFromTier']);
    // Route::post('/customers/toggle-pricing', [CustomPricingController::class, 'toggleCustomPricing']); // Deprecated in favor of tiers
    
    // Pricing Tiers
    // Pricing Tiers
    Route::get('/pricing-tiers', [PricingTierController::class, 'index']);
    Route::post('/pricing-tiers', [PricingTierController::class, 'store']);
    Route::put('/pricing-tiers/{id}', [PricingTierController::class, 'update']);
    Route::delete('/pricing-tiers/{id}', [PricingTierController::class, 'destroy']);
    Route::get('/pricing-tiers/{id}/prices', [CustomPricingController::class, 'getTierPrices']);
    Route::get('/pricing-tiers/{id}/members', [PricingTierController::class, 'getMembers']);

    // Product Management
    Route::get('/products', [CustomPricingController::class, 'getProducts'])->name('products.index');
    Route::post('/products/search', [CustomPricingController::class, 'searchProducts']);
    Route::get('/products/{id}', [CustomPricingController::class, 'getProduct'])->name('products.show');
    
    // Custom Pricing (Now Tier Based)
    Route::post('/custom-prices', [CustomPricingController::class, 'setCustomPrice']);
    Route::delete('/custom-prices/{id}', [CustomPricingController::class, 'deleteCustomPrice']);
    Route::get('/custom-prices/tier/{id}', [CustomPricingController::class, 'getTierPrices']);
    Route::get('/custom-prices/customer/{id}', [CustomPricingController::class, 'getCustomerPrices']);
    
    // Loyalty Settings
    Route::get('/loyalty/settings', [LoyaltyController::class, 'getSettings']);
    Route::post('/loyalty/settings', [LoyaltyController::class, 'updateSettings']);
    
    // Loyalty Tiers
    Route::get('/loyalty/tiers', [LoyaltyController::class, 'getTiers']);
    
    // Customer Loyalty Management
    Route::post('/loyalty/customers/search', [LoyaltyController::class, 'searchCustomerLoyalty']);
    Route::post('/loyalty/customers/toggle-status', [LoyaltyController::class, 'toggleCustomerLoyalty']);
    Route::get('/loyalty/customers/{id}', [LoyaltyController::class, 'getCustomerLoyalty']);
    Route::post('/loyalty/points/adjust', [LoyaltyController::class, 'adjustPoints']);
    Route::post('/loyalty/points/redeem', [LoyaltyController::class, 'redeemPoints']);
});

// ============================================
// STOREFRONT API ROUTES (Public - No Auth)
// ============================================
Route::prefix('storefront')->group(function () {
    
    // Custom Pricing
    Route::post('/custom-price', [CustomPricingController::class, 'getCustomerPrice']);
    Route::post('/create-draft-order', [CustomPricingController::class, 'createDraftOrder']);
    
    // Loyalty
    Route::post('/loyalty', [LoyaltyController::class, 'getStorefrontLoyalty']);
    Route::post('/loyalty/transactions', [LoyaltyController::class, 'getTransactionHistory']);
    Route::post('/loyalty/settings', [LoyaltyController::class, 'getStorefrontSettings']);
    Route::post('/loyalty/tiers', [LoyaltyController::class, 'getStorefrontTiers']);
    
    // Redemptions
    Route::post('/redemptions/create', [RedemptionController::class, 'createRedemption']);
    Route::post('/redemptions/active', [RedemptionController::class, 'getActiveRedemptions']);
    Route::delete('/redemptions/{id}/cancel', [RedemptionController::class, 'cancelRedemption']);
});

// ============================================
// CHECKOUT API (Custom Checkout with CORS)
// ============================================
// OPTIONS preflight
Route::options('/checkout/create', function() {
    return response()->json([], 200, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization, X-Requested-With',
    ]);
});

// POST route
Route::post('/checkout/create', [ApiCheckoutController::class, 'createCustomCheckout']);

// ============================================
// DEBUG/TEST ROUTES (Remove in production)
// ============================================
if (env('APP_ENV') !== 'production') {
    
    // Verify token for any shop
    Route::get('/debug/verify-token', function(Request $request) {
        $shop = $request->query('shop');
        
        if (!$shop) {
            return response()->json([
                'error' => 'Shop parameter required',
                'usage' => '/api/debug/verify-token?shop=your-store.myshopify.com'
            ], 400);
        }
        
        $store = \DB::table('stores')->where('shop_domain', $shop)->first();
        
        if (!$store) {
            return response()->json([
                'error' => 'Store not found',
                'shop' => $shop,
                'solution' => 'Install the app for this store first'
            ], 404);
        }
        
        $apiVersion = env('SHOPIFY_API_VERSION', '2024-01');
        
        // Test with correct API version
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'X-Shopify-Access-Token' => $store->access_token,
        ])->get("https://{$shop}/admin/api/{$apiVersion}/shop.json");
        
        return response()->json([
            'shop' => $shop,
            'api_version' => $apiVersion,
            'token_works' => $response->successful(),
            'status' => $response->status(),
            'shop_name' => $response->json()['shop']['name'] ?? null,
            'shop_email' => $response->json()['shop']['email'] ?? null,
            'plan_name' => $response->json()['shop']['plan_name'] ?? null,
            'has_draft_orders_scope' => str_contains($response->header('X-Shopify-API-Grant-Modes') ?? '', 'write_draft_orders'),
            'error' => !$response->successful() ? $response->body() : null
        ], 200, [], JSON_PRETTY_PRINT);
    });
    
    // Test storefront API endpoint
    Route::post('/debug/test-storefront', function(Request $request) {
        return response()->json([
            'message' => 'Storefront API is working',
            'received_data' => $request->all(),
            'timestamp' => now()->toIso8601String()
        ]);
    });
    
    // Test checkout API endpoint
    Route::post('/debug/test-checkout', function(Request $request) {
        return response()->json([
            'message' => 'Checkout API is working',
            'received_data' => $request->all(),
            'timestamp' => now()->toIso8601String()
        ], 200, [
            'Access-Control-Allow-Origin' => '*'
        ]);
    });
}

