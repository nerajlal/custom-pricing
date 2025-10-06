<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShopifyAuthController extends Controller
{
    private $apiKey;
    private $apiSecret;
    private $scopes;
    private $redirectUri;

    public function __construct()
    {
        $this->apiKey = env('SHOPIFY_API_KEY');
        $this->apiSecret = env('SHOPIFY_API_SECRET');
        $this->scopes = env('SHOPIFY_APP_SCOPES');
        $this->redirectUri = env('SHOPIFY_REDIRECT_URI');
    }

    // Step 1: Initiate OAuth
    public function install(Request $request)
    {
        $shop = $request->query('shop');

        if (!$shop) {
            return response()->json(['error' => 'Shop parameter required'], 400);
        }

        // Validate shop domain
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $shop)) {
            return response()->json(['error' => 'Invalid shop domain'], 400);
        }

        $nonce = Str::random(32);
        session(['shopify_nonce' => $nonce]);

        $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $this->apiKey,
            'scope' => $this->scopes,
            'redirect_uri' => $this->redirectUri,
            'state' => $nonce
        ]);

        return redirect($installUrl);
    }

    // Step 2: Handle OAuth callback
    public function callback(Request $request)
    {
        $shop = $request->query('shop');
        $code = $request->query('code');
        $state = $request->query('state');

        // Verify state (nonce)
        if ($state !== session('shopify_nonce')) {
            return response()->json(['error' => 'Invalid state parameter'], 400);
        }

        // Verify HMAC
        if (!$this->verifyHmac($request->query())) {
            return response()->json(['error' => 'HMAC verification failed'], 400);
        }

        // Exchange code for access token
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => $this->apiKey,
            'client_secret' => $this->apiSecret,
            'code' => $code
        ]);

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'];

            // Save or update store
            Store::updateOrCreate(
                ['shop_domain' => $shop],
                ['access_token' => $accessToken]
            );

            // Create session token for frontend
            $token = $this->createSessionToken($shop);

            // Redirect to app with token
            return redirect("/app?shop={$shop}&token={$token}");
        }

        return response()->json(['error' => 'Failed to get access token'], 500);
    }

    // Verify Shopify HMAC
    private function verifyHmac($query)
    {
        $hmac = $query['hmac'] ?? '';
        unset($query['hmac']);

        ksort($query);
        $queryString = http_build_query($query);
        $calculatedHmac = hash_hmac('sha256', $queryString, $this->apiSecret);

        return hash_equals($hmac, $calculatedHmac);
    }

    // Create JWT session token
    private function createSessionToken($shop)
    {
        $payload = [
            'shop' => $shop,
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ];

        // Simple JWT creation (use a proper JWT library in production)
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