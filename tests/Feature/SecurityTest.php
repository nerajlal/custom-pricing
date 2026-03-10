<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock DB for store lookup
        DB::shouldReceive('table')->with('stores')->andReturnSelf();
        DB::shouldReceive('where')->with('shop_domain', 'test-store.myshopify.com')->andReturnSelf();
        DB::shouldReceive('first')->andReturn((object)[
            'id' => 1,
            'shop_domain' => 'test-store.myshopify.com',
            'access_token' => 'dummy_token'
        ]);
        
        // Mock config for secret
        Config::set('shopify.api_secret', 'test_secret');
    }

    /** @test */
    public function app_proxy_fails_without_signature()
    {
        $response = $this->get('/app-proxy/ping');
        $response->assertStatus(401);
    }

    /** @test */
    public function app_proxy_succeeds_with_valid_signature()
    {
        $params = [
            'shop' => 'test-store.myshopify.com',
            'timestamp' => '1234567890', // Fixed timestamp for consistency
        ];
        
        // Calculate signature (same logic as VerifyAppProxy)
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = "$key=$value";
        }
        $queryString = implode('', $pairs);
        $signature = hash_hmac('sha256', $queryString, 'test_secret');
        
        $params['signature'] = $signature;
        
        $response = $this->get('/app-proxy/ping?' . http_build_query($params));
        // We expect 200 since signature matches
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_route_fails_without_session_or_hmac()
    {
        // For a JSON request, it returns 401
        $response = $this->getJson('/app?shop=test-store.myshopify.com');
        $response->assertStatus(401);
    }

    /** @test */
    public function app_returns_csp_header()
    {
        // Mock session to pass middleware
        $response = $this->withSession(['shopify_shop_domain' => 'test-store.myshopify.com'])
                         ->get('/app?shop=test-store.myshopify.com');
        
        $response->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString('frame-ancestors', $response->headers->get('Content-Security-Policy'));
    }
}
