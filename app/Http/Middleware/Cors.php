<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            return response()->json([], 200, $this->headers());
        }

        try {
            $response = $next($request);
        } catch (\Exception $e) {
            // Catch exceptions and add CORS headers before re-throwing
            $response = response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500, $this->headers());
            
            return $response;
        }

        // Add CORS headers to successful responses
        foreach ($this->headers() as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    private function headers(): array
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization, X-Requested-With',
            'Access-Control-Max-Age' => '86400',
        ];
    }
}