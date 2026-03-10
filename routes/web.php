<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyAuthController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ============================================
// SHOPIFY OAUTH ROUTES
// ============================================
Route::get('/install', [ShopifyAuthController::class, 'install'])->name('install');
Route::get('/auth/callback', [ShopifyAuthController::class, 'callback'])->name('auth.callback');

// ============================================
// APP INTERFACE (Protected)
// ============================================
Route::get('/app', function () {
    return response()->view('app')
        ->header('Content-Security-Policy', "frame-ancestors https://" . request()->query('shop') . " https://admin.shopify.com");
})->name('app')->middleware(['auth.shopify']);

// Root redirect (Preserve query parameters for Shopify)
Route::get('/', function (Request $request) {
    return redirect()->route('install', $request->query());
});

// Installation guide
Route::get('/installation', function() {
    return view('installation-guide');
})->name('installation');

Route::get('/home', function() {
    return view('home');
})->name('home');

Route::get('/pricing', function() {
    return view('pricing');
})->name('pricing');

// Legal pages (public)
Route::get('/privacy-policy', [App\Http\Controllers\LegalController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms-of-service', [App\Http\Controllers\LegalController::class, 'termsOfService'])->name('terms-of-service');
Route::get('/support', function() {
    return view('support');
})->name('support');
Route::get('/admin/documentation', function() {
    return view('installation-guide'); // Placeholder
})->name('admin.documentation');
// ============================================
// APP PROXY ROUTES (Storefront Scripts)
// ============================================
Route::prefix('app-proxy')->middleware(['auth.proxy'])->group(function () {
    
    // Product page script
    Route::get('/script.js', function(Request $request) {
        $customerId = $request->query('logged_in_customer_id');
        $jsContent = view('app-proxy.custom-price-script', ['customerId' => $customerId])->render();
        return response($jsContent)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    })->name('proxy.product.script');

    // Collection page script
    Route::get('/collection-script.js', function() {
        $jsContent = view('app-proxy.collection-price-script')->render();
        return response($jsContent)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    })->name('proxy.collection.script');
    
    // Cart page script
    Route::get('/cart-script.js', function() {
        $jsContent = view('app-proxy.cart-custom-price-script')->render();
        return response($jsContent)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    })->name('proxy.cart.script');

    // Loyalty cart widget script
Route::get('/loyalty-cart.js', function() {
    $jsContent = view('app-proxy.loyalty-cart-script')->render();
    return response($jsContent)
        ->header('Content-Type', 'application/javascript; charset=utf-8')
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->name('proxy.loyalty.cart');

// Loyalty widget script (all pages)
Route::get('/loyalty-widget.js', function() {
    $jsContent = view('app-proxy.loyalty-widget-script')->render();
    return response($jsContent)
        ->header('Content-Type', 'application/javascript; charset=utf-8')
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
        ->header('Pragma', 'no-cache')
        ->header('Expires', '0');
})->name('proxy.loyalty.widget');

    // Loyalty page (optional dedicated page)
    Route::get('/loyalty-points', function(Request $request) {
        $shop = $request->query('shop');
        return view('app-proxy.loyalty-page', ['shop_domain' => $shop]);
    })->name('proxy.loyalty.page');

    // Health check
    Route::get('/ping', function() {
        return response()->json([
            'status' => 'ok',
            'app' => 'custom-pricing-loyalty',
            'timestamp' => now()->toIso8601String()
        ]);
    })->name('proxy.ping');

    // Identify Customer (Proxy Only)
    Route::get('/identify-customer', function(Request $request) {
        $customerId = $request->query('logged_in_customer_id'); // Signed by Shopify
        return response()->json([
            'customer_id' => $customerId,
            'shop' => $request->query('shop'), // Ensure we are on correct shop context if needed
            'timestamp' => now()->timestamp
        ])->header('Content-Type', 'application/json');
    })->name('proxy.identify');
});

// Single webhook endpoint for compliance webhooks
Route::post('/webhooks', function (Request $request) {
    $topic = $request->header('X-Shopify-Topic');
    
    switch ($topic) {
        case 'customers/data_request':
            return app(WebhookController::class)->customersDataRequest($request);
        case 'customers/redact':
            return app(WebhookController::class)->customersRedact($request);
        case 'shop/redact':
            return app(WebhookController::class)->shopRedact($request);
        default:
            return response()->json(['error' => 'Unknown topic'], 400);
    }
});

// ============================================
// WEBHOOKS (No Auth - Verified by Shopify HMAC)
// ============================================
Route::post('/webhooks/orders/create', [WebhookController::class, 'orderCreate']);
Route::post('/webhooks/orders/paid', [WebhookController::class, 'orderCreate']);
Route::post('/webhooks/refunds/create', [WebhookController::class, 'orderRefund']);

// GDPR webhooks (Required by Shopify)
Route::post('/webhooks/customers/data_request', [WebhookController::class, 'customersDataRequest']);
Route::post('/webhooks/customers/redact', [WebhookController::class, 'customersRedact']);
Route::post('/webhooks/shop/redact', [WebhookController::class, 'shopRedact']);

// ============================================
// DEBUG/TEST ROUTES (Remove in production or protect)
// ============================================
if (env('APP_ENV') !== 'production') {
    
    // Check widget installation status
    Route::get('/debug/check-widget', function(Request $request) {
        try {
            $shop = $request->query('shop');
            
            if (!$shop) {
                return response()->json([
                    'error' => 'Shop parameter required',
                    'usage' => '/debug/check-widget?shop=your-store.myshopify.com'
                ], 400);
            }
            
            $store = \App\Models\Store::where('shop_domain', $shop)->first();
            
            if (!$store) {
                return response()->json([
                    'error' => 'Store not found',
                    'shop' => $shop,
                    'solution' => 'Install the app first'
                ], 404);
            }
            
            // Get all script tags
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
            ])->get("https://{$shop}/admin/api/" . env('SHOPIFY_API_VERSION', '2024-01') . "/script_tags.json");
            
            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch script tags',
                    'status' => $response->status(),
                    'body' => $response->body()
                ], 500);
            }
            
            $scriptTags = $response->json()['script_tags'] ?? [];
            $appUrl = rtrim(env('APP_URL'), '/');
            
            $installedScripts = [];
            foreach ($scriptTags as $tag) {
                // Check if src contains app url (ignoring trailing slash differences)
                if (str_contains($tag['src'], $appUrl)) {
                    $installedScripts[] = [
                        'id' => $tag['id'],
                        'src' => $tag['src'],
                        'event' => $tag['event']
                    ];
                }
            }
            
            return response()->json([
                'shop' => $shop,
                'app_url' => $appUrl,
                'installed_scripts' => $installedScripts,
                'total_script_tags' => count($scriptTags),
                'status' => !empty($installedScripts) ? 'Scripts Installed ✅' : 'No Scripts Found ❌'
            ], 200, [], JSON_PRETTY_PRINT);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    })->name('debug.check-widget');

    // Uninstall script tags manualy
    Route::get('/debug/uninstall-scripts', function(Request $request) {
        try {
            $shop = $request->query('shop');
            if (!$shop) return response()->json(['error' => 'Shop required'], 400);

            $store = \App\Models\Store::where('shop_domain', $shop)->first();
            if (!$store) return 'Store not found';

            $apiVersion = config('shopify.api_version');
            $appUrl = rtrim(env('APP_URL'), '/');

            $client = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
                'Content-Type' => 'application/json'
            ]);

            // Get all scripts
            $existing = $client->get("https://{$shop}/admin/api/{$apiVersion}/script_tags.json");
            $results = [];

            if ($existing->successful()) {
                $scriptTags = $existing->json()['script_tags'] ?? [];
                
                if (empty($scriptTags)) {
                     return response()->json(['shop' => $shop, 'message' => 'No scripts found to uninstall.']);
                }

                foreach ($scriptTags as $tag) {
                    // Check if it belongs to us
                    if (str_contains($tag['src'], $appUrl) || 
                        str_contains($tag['src'], 'loyalty-widgets') || 
                        str_contains($tag['src'], 'loyalty-cart')) {
                        
                        $delete = $client->delete("https://{$shop}/admin/api/{$apiVersion}/script_tags/{$tag['id']}.json");
                        
                        if ($delete->successful()) {
                            $results[] = "Deleted ID {$tag['id']} ({$tag['src']}) ✅";
                        } else {
                            $results[] = "Failed to delete ID {$tag['id']}: " . $delete->body();
                        }
                    }
                }
            }

            return response()->json([
                'shop' => $shop,
                'results' => $results,
                'summary' => count($results) > 0 ? 'Cleanup Complete 🧹' : 'Nothing to clean up'
            ], 200, [], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    })->name('debug.uninstall-scripts');

    // Install script tags manually
    Route::get('/debug/install-scripts', function(Request $request) {
        try {
            $shop = $request->query('shop');
            
            if (!$shop) {
                return response()->json([
                    'error' => 'Shop parameter required',
                    'usage' => '/debug/install-scripts?shop=your-store.myshopify.com'
                ], 400);
            }
            
            $store = \App\Models\Store::where('shop_domain', $shop)->first();
            
            if (!$store) {
                return 'Store not found for: ' . $shop;
            }
            
            $appUrl = rtrim(env('APP_URL'), '/');
            $apiVersion = config('shopify.api_version');
            
            $client = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Shopify-Access-Token' => $store->access_token,
                'Content-Type' => 'application/json'
            ]);
            
            // Scripts to install
            $scriptsToInstall = [
                ['src' => "{$appUrl}/app-proxy/script.js", 'name' => 'Product Page Script'],
                ['src' => "{$appUrl}/app-proxy/cart-script.js", 'name' => 'Cart Page Script'],
                ['src' => "{$appUrl}/app-proxy/loyalty-cart.js", 'name' => 'Loyalty Cart Script'],
                ['src' => "{$appUrl}/app-proxy/collection-script.js", 'name' => 'Collection Script']
            ];
            
            $results = [];
            
            // Check existing scripts
            $existing = $client->get("https://{$shop}/admin/api/{$apiVersion}/script_tags.json");
            $existingSrcs = [];
            
            if ($existing->successful()) {
                foreach ($existing->json()['script_tags'] as $tag) {
                    $existingSrcs[] = $tag['src'];
                }
            }
            
            // Install each script
            foreach ($scriptsToInstall as $script) {
                if (in_array($script['src'], $existingSrcs)) {
                    $results[] = "{$script['name']}: Already installed ✅";
                    continue;
                }
                
                $response = $client->post("https://{$shop}/admin/api/{$apiVersion}/script_tags.json", [
                    'script_tag' => [
                        'event' => 'onload',
                        'src' => $script['src'],
                        'display_scope' => 'online_store'
                    ]
                ]);
                
                if ($response->successful()) {
                    $results[] = "{$script['name']}: Installed ✅ (ID: {$response->json()['script_tag']['id']})";
                } else {
                    $results[] = "{$script['name']}: Failed ❌ - {$response->body()}";
                }
            }
            
            return response()->json([
                'shop' => $shop,
                'results' => $results
            ], 200, [], JSON_PRETTY_PRINT);
            
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    })->name('debug.install-scripts');
}

# Check widget installation
// GET /debug/check-widget?shop=store-name.myshopify.com

# Install scripts manually
// GET /debug/install-scripts?shop=store-name.myshopify.com

// Test loyalty script
Route::get('/loyalty-test.js', function() {
    $jsContent = view('app-proxy.loyalty-test-script')->render();
    return response($jsContent)
        ->header('Content-Type', 'application/javascript; charset=utf-8')
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
})->name('proxy.loyalty.test');

if (env('APP_ENV') !== 'production') {
    // Dump DB for debugging
    Route::get('/debug/db-dump', function() {
        return response()->json([
            'custom_prices' => \App\Models\CustomPrice::all(),
            'pricing_tiers' => \App\Models\PricingTier::with('customPrices')->get(),
            'settings' => \App\Models\CustomerPricingSetting::with('tier')->get()
        ]);
    });
}
