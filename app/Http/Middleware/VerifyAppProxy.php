<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyAppProxy
{
    /**
     * Handle an incoming request.
     * 
     * Shopify App Proxy signature verification.
     * https://shopify.dev/docs/apps/online-store/app-proxies#verify-the-proxy-request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $query = $request->query();
        $hmac = $query['signature'] ?? '';

        if (empty($hmac)) {
            Log::warning('App Proxy request missing signature');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Remove signature from parameters
        $params = array_diff_key($query, ['signature' => '']);
        
        // Sort parameters lexicographically by key
        ksort($params);

        // Build query string
        $pairs = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $pairs[] = $key . '=' . $value;
        }
        $queryString = implode('', $pairs);

        // Calculate expected signature using App Secret
        $calculatedHmac = hash_hmac('sha256', $queryString, config('shopify.api_secret'));

        if (!hash_equals($hmac, $calculatedHmac)) {
            Log::error('App Proxy signature verification failed', [
                'received' => $hmac,
                'calculated' => $calculatedHmac,
                'query' => $query,
                'queryString_constructed' => $queryString,
                'secret_used_mask' => substr(config('shopify.api_secret'), 0, 4) . '***'
            ]);
            return response()->json([
                'error' => 'Invalid signature',
                'debug' => env('APP_DEBUG') ? ['calculated' => $calculatedHmac, 'constructed_string' => $queryString] : null
            ], 401);
        }

        return $next($request);
    }
}
