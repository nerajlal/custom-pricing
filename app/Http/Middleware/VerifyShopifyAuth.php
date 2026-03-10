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
        // Get shop from query parameter
        $shop = $request->query('shop') ?? $request->input('shop');
        
        Log::info('VerifyShopifyAuth middleware', [
            'shop' => $shop,
            'has_shop' => !empty($shop),
            'url' => $request->fullUrl()
        ]);
        
        if (!$shop) {
            return response()->json([
                'error' => 'Shop parameter required',
                'message' => 'Please provide shop parameter in the request'
            ], 401);
        }
        
        // Verify shop exists in database
        $store = \DB::table('stores')->where('shop_domain', $shop)->first();
        
        if (!$store) {
            return response()->json([
                'error' => 'Store not found',
                'message' => 'Please install the app for this store'
            ], 401);
        }
        
        // Add store to request for use in controllers
        $request->attributes->set('shop', $shop);
        $request->attributes->set('store', $store);
        
        return $next($request);
    }
}
