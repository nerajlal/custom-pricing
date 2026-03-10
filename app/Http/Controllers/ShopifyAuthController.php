<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\ScriptTagManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShopifyAuthController extends Controller
{
    private $apiKey;
    private $apiSecret;
    private $scopes;
    private $redirectUri;

    public function __construct()
    {
        $this->apiKey = config('shopify.api_key');
        $this->apiSecret = config('shopify.api_secret');
        $this->scopes = env('SHOPIFY_APP_SCOPES'); // Keep env for scopes as they might not be in config
        $this->redirectUri = env('SHOPIFY_REDIRECT_URI');
    }

   public function install(Request $request)
{
    // Try to get shop from query parameter first
    $shop = $request->query('shop');
    
    // If no shop in query, try to get from Shopify embedded context
    if (!$shop && $request->query('host')) {
        // Decode the host parameter that Shopify sends
        $host = $request->query('host');
        $decodedHost = base64_decode($host);
        
        // Extract shop from host (format: shop-name.myshopify.com/admin)
        if (preg_match('/^([a-zA-Z0-9\-]+\.myshopify\.com)/', $decodedHost, $matches)) {
            $shop = $matches[1];
        }
    }
    
    Log::info("Installation started for shop: {$shop}");
    Log::info("Request data:", [
        'query_shop' => $request->query('shop'),
        'host' => $request->query('host'),
        'all_params' => $request->query()
    ]);

    if (!$shop) {
        Log::error("No shop parameter provided");
        
        // Return a view that uses Shopify App Bridge to get the shop
        return view('get-shop', [
            'apiKey' => $this->apiKey,
            'host' => $request->query('host')
        ]);
    }

    // Rest of your code stays the same...
    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $shop)) {
        Log::error("Invalid shop domain: {$shop}");
        return response()->json(['error' => 'Invalid shop domain'], 400);
    }

    $nonce = Str::random(32);
    
    $stateData = [
        'nonce' => $nonce,
        'shop' => $shop,
        'timestamp' => time()
    ];
    
    $stateJson = json_encode($stateData);
    $signature = hash_hmac('sha256', $stateJson, $this->apiSecret);
    $state = base64_encode($stateJson . '|' . $signature);
    
    Log::info("Generated signed state for installation");

    $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
        'client_id' => $this->apiKey,
        'scope' => $this->scopes,
        'redirect_uri' => $this->redirectUri,
        'state' => $state
    ]);

    Log::info("Redirecting to Shopify OAuth: {$installUrl}");

    return redirect($installUrl);
}
    
    public function callback(Request $request)
    {
        Log::info("🔥 CALLBACK HIT! Raw request data:", [
            'shop' => $request->query('shop'),
            'has_code' => !empty($request->query('code')),
            'has_state' => !empty($request->query('state'))
        ]);
        
        $shop = $request->query('shop');
        $code = $request->query('code');
        $stateParam = $request->query('state');
    
        Log::info("=== OAUTH CALLBACK STARTED ===");
        Log::info("Shop: {$shop}");
    
        // Decode and verify the signed state
        try {
            $decoded = base64_decode($stateParam);
            $parts = explode('|', $decoded);
            
            if (count($parts) !== 2) {
                throw new \Exception('Invalid state format');
            }
            
            [$stateJson, $signature] = $parts;
            
            // Verify signature
            $expectedSignature = hash_hmac('sha256', $stateJson, $this->apiSecret);
            
            // DEBUG LOGGING
            Log::info("🔐 State Verification Debug:", [
                'api_secret_len' => strlen($this->apiSecret),
                'api_secret_start' => substr($this->apiSecret, 0, 4) . '...',
                'received_signature' => $signature,
                'expected_signature' => $expectedSignature,
                'state_json' => $stateJson,
                'match' => hash_equals($signature, $expectedSignature) ? 'YES' : 'NO'
            ]);

            if (!hash_equals($signature, $expectedSignature)) {
                throw new \Exception('Invalid state signature (Check logs for details)');
            }
            
            $stateData = json_decode($stateJson, true);
            
            // Verify timestamp (within 10 minutes)
            if (time() - $stateData['timestamp'] > 600) {
                throw new \Exception('State expired');
            }
            
            // Verify shop matches
            if ($stateData['shop'] !== $shop) {
                throw new \Exception('Shop mismatch');
            }
            
            Log::info("✅ State verified successfully");
            
        } catch (\Exception $e) {
            Log::error("❌ State verification failed: " . $e->getMessage());
            return response()->json([
                'error' => 'Invalid state parameter: ' . $e->getMessage(),
                'debug_hint' => 'Check laravel.log for "State Verification Debug"'
            ], 400);
        }
    
        // Verify HMAC
        if (!$this->verifyHmac($request->query())) {
            Log::error("❌ HMAC verification failed");
            return response()->json(['error' => 'HMAC verification failed'], 400);
        }
        Log::info("✅ HMAC verified");
    
        // Exchange code for access token
        Log::info("Requesting access token from Shopify...");
        
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => $this->apiKey,
            'client_secret' => $this->apiSecret,
            'code' => $code
        ]);
    
        Log::info("Shopify response status: " . $response->status());
    
        if (!$response->successful()) {
            Log::error("❌ Failed to get access token");
            Log::error("Response: " . $response->body());
            return response()->json([
                'error' => 'Failed to get access token',
                'details' => $response->body()
            ], 500);
        }
    
        $data = $response->json();
        $accessToken = $data['access_token'];
        $scopes = $data['scope'] ?? '';
        
        Log::info("✅ Access token received: " . substr($accessToken, 0, 20) . "...");
    
        // Save store to database
        try {
            Log::info("Saving to database...");
            
            $store = Store::updateOrCreate(
                ['shop_domain' => $shop],
                [
                    'access_token' => $accessToken,
                    'scopes' => $scopes
                ]
            );
            
            // Install script tags (Handled in installAutomations now)
            // No action needed here
            
            Log::info("✅ Store saved successfully!");
            Log::info("Store ID: {$store->id}");
            
            // Verify
            $verify = Store::where('shop_domain', $shop)->first();
            Log::info("Verification: " . (!empty($verify->access_token) ? 'SUCCESS' : 'FAILED'));
            
        } catch (\Exception $e) {
            Log::error("❌ DATABASE ERROR: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to save store',
                'details' => $e->getMessage()
            ], 500);
        }

        // ✨ NEW: Install automations (script tags & webhooks)
        Log::info("🚀 Installing automations...");
        $this->installAutomations($store);
    
        Log::info("=== OAUTH CALLBACK COMPLETED ===");
        
        // Redirect to Shopify admin apps page
        return redirect()->away("https://{$shop}/admin/apps");
    }

    /**
     * ✨ NEW: Install all automations (Script Tags, Webhooks)
     */
    private function installAutomations(Store $store)
    {
        try {
            Log::info("Installing automations for store: {$store->shop_domain}");
            
            // 1. Install all script tags
            Log::info("📦 Installing script tags...");
            ScriptTagManager::installAllScripts($store);
            
            // 2. Register webhooks
            Log::info("🔗 Registering webhooks...");
            $this->registerWebhooks($store);
            
            Log::info("✅ All automations installed successfully");
            
        } catch (\Exception $e) {
            Log::error("❌ Failed to install automations: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            // Don't fail the installation, just log the error
        }
    }

    /**
     * ✨ NEW: Register all webhooks
     */
    /**
     * ✨ NEW: Register all webhooks
     */
    private function registerWebhooks(Store $store)
    {
        $service = new \App\Services\ShopifyGraphqlService();

        $webhooks = [
            ['topic' => 'ORDERS_CREATE', 'address' => env('APP_URL') . '/webhooks/orders/create'],
            ['topic' => 'ORDERS_PAID', 'address' => env('APP_URL') . '/webhooks/orders/paid'],
            ['topic' => 'REFUNDS_CREATE', 'address' => env('APP_URL') . '/webhooks/refunds/create'],
            ['topic' => 'CUSTOMERS_DATA_REQUEST', 'address' => env('APP_URL') . '/webhooks/customers/data_request'],
            ['topic' => 'CUSTOMERS_REDACT', 'address' => env('APP_URL') . '/webhooks/customers/redact'],
            ['topic' => 'SHOP_REDACT', 'address' => env('APP_URL') . '/webhooks/shop/redact'],
        ];

        foreach ($webhooks as $webhook) {
            try {
                $mutation = <<<'GRAPHQL'
                mutation webhookSubscriptionCreate($topic: WebhookSubscriptionTopic!, $webhookSubscription: WebhookSubscriptionInput!) {
                    webhookSubscriptionCreate(topic: $topic, webhookSubscription: $webhookSubscription) {
                        webhookSubscription {
                            id
                        }
                        userErrors {
                            field
                            message
                        }
                    }
                }
                GRAPHQL;

                $variables = [
                    'topic' => $webhook['topic'],
                    'webhookSubscription' => [
                        'callbackUrl' => $webhook['address'],
                        'format' => 'JSON'
                    ]
                ];

                $data = $service->query($store->shop_domain, $store->access_token, $mutation, $variables);

                if (!empty($data['webhookSubscriptionCreate']['userErrors'])) {
                    // Ignore "already exists" errors if possible, or log them
                    $errors = $data['webhookSubscriptionCreate']['userErrors'];
                    $alreadyExists = false;
                    foreach ($errors as $error) {
                        if (str_contains($error['message'], 'already exists')) {
                            $alreadyExists = true;
                            break;
                        }
                    }

                    if ($alreadyExists) {
                        Log::info("✓ Webhook already exists: {$webhook['topic']}");
                    } else {
                        Log::warning("Failed to register webhook: {$webhook['topic']}", ['errors' => $errors]);
                    }
                } else {
                    Log::info("✓ Webhook registered: {$webhook['topic']}");
                }

            } catch (\Exception $e) {
                Log::error("Webhook registration error: {$webhook['topic']}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    // Verify Shopify HMAC
    private function verifyHmac($queryParams)
    {
        // Convert to array if it's a collection
        $query = $queryParams instanceof \Illuminate\Support\Collection 
            ? $queryParams->all() 
            : (array) $queryParams;
        
        // Get the HMAC from query
        $hmac = $query['hmac'] ?? '';
        
        if (empty($hmac)) {
            Log::error('No HMAC in request');
            return false;
        }
        
        // Remove hmac and signature from params
        unset($query['hmac']);
        unset($query['signature']);
        
        // Sort parameters
        ksort($query);
        
        // Build query string (Shopify uses specific encoding)
        $pairs = [];
        foreach ($query as $key => $value) {
            if (is_array($value)) {
                // Handle array values
                $value = '["' . implode('","', $value) . '"]';
            }
            $pairs[] = $key . '=' . $value;
        }
        $queryString = implode('&', $pairs);
        
        Log::info('HMAC verification details:', [
            'query_string' => $queryString,
            'received_hmac' => $hmac
        ]);
        
        // Calculate expected HMAC
        $calculatedHmac = hash_hmac('sha256', $queryString, $this->apiSecret);
        
        Log::info('HMAC comparison:', [
            'calculated' => $calculatedHmac,
            'received' => $hmac,
            'match' => hash_equals($hmac, $calculatedHmac)
        ]);
        
        return hash_equals($hmac, $calculatedHmac);
    }

    // Create JWT session token
    private function createSessionToken($shop)
    {
        $payload = [
            'shop' => $shop,
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ];

        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "$header.$payload", $this->apiSecret);

        return "$header.$payload.$signature";
    }

    // Verify session token middleware
    public function verifyToken(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Verify JWT token
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        [$header, $payload, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', "$header.$payload", $this->apiSecret);

        if (!hash_equals($signature, $expectedSignature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode(base64_decode($payload), true);

        if ($payload['exp'] < time()) {
            return response()->json(['error' => 'Token expired'], 401);
        }

        return $payload['shop'];
    }
}
