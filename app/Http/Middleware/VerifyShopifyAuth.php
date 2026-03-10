<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyShopifyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Get shop from query parameter or header
        $shop = $request->query('shop') ?? $request->input('shop') ?? $request->header('X-Shopify-Shop-Domain');
        
        if (!$shop) {
            return response()->json([
                'error' => 'Shop parameter required',
                'message' => 'Please provide shop parameter in the request'
            ], 401);
        }

        // 2. Security: Verify HMAC if present (first installation/navigation)
        if ($request->has('hmac')) {
            if (!$this->verifyHmac($request->all())) {
                return response()->json(['error' => 'HMAC verification failed'], 401);
            }
        } 
        // 3. Security: Otherwise verify session/token (for embedded app requests)
        // Note: For a pure custom implementation, we expect a session token or valid session
        elseif (!session()->has('shopify_shop_domain') && !$request->bearerToken()) {
            // If it's an AJAX request within the app, we need auth
            if ($request->ajax() || $request->wantsJson()) {
                 return response()->json(['error' => 'Unauthorized session'], 401);
            }
            // If it's a page load, redirect to install
            return redirect()->route('install', ['shop' => $shop]);
        }
        
        // 4. Verify shop exists in database
        $store = \DB::table('stores')->where('shop_domain', $shop)->first();
        
        if (!$store) {
            return response()->json([
                'error' => 'Store not found',
                'message' => 'Please install the app for this store'
            ], 401);
        }
        
        // Add store/shop to request
        $request->attributes->set('shop', $shop);
        $request->attributes->set('store', $store);
        
        return $next($request);
    }

    /**
     * Verify Shopify HMAC
     */
    private function verifyHmac(array $params): bool
    {
        $hmac = $params['hmac'] ?? '';
        unset($params['hmac'], $params['signature']);
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = "$key=$value";
        }
        $queryString = implode('&', $pairs);
        $calculatedHmac = hash_hmac('sha256', $queryString, config('shopify.api_secret'));

        return hash_equals($hmac, $calculatedHmac);
    }
}
