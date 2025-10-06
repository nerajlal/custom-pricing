<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\Store;
use App\Models\CustomerPricingSetting;
use App\Models\CustomPrice;

class CustomPricingControllerTest extends TestCase
{
    use RefreshDatabase;

    private $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::create([
            'shop_domain' => 'test.myshopify.com',
            'access_token' => 'test_token',
        ]);
    }

    /** @test */
    public function it_can_search_for_a_customer()
    {
        Http::fake([
            'test.myshopify.com/*' => Http::response([
                'customers' => [
                    ['id' => 123, 'email' => 'test@example.com']
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/admin/customers/search', [
            'email' => 'test@example.com',
            'shop' => $this->store->shop_domain,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'customer' => ['id' => 123],
                'pricing_setting' => ['shopify_customer_id' => 123]
            ]);

        $this->assertDatabaseHas('customer_pricing_settings', [
            'shopify_customer_id' => 123,
            'store_id' => $this->store->id
        ]);
    }

    /** @test */
    public function it_can_toggle_custom_pricing()
    {
        $setting = CustomerPricingSetting::create([
            'store_id' => $this->store->id,
            'shopify_customer_id' => 123,
            'customer_email' => 'test@example.com',
            'is_custom_pricing_enabled' => false
        ]);

        $response = $this->postJson('/api/admin/customers/toggle-pricing', [
            'customer_pricing_setting_id' => $setting->id,
            'enabled' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson(['setting' => ['is_custom_pricing_enabled' => true]]);

        $this->assertDatabaseHas('customer_pricing_settings', [
            'id' => $setting->id,
            'is_custom_pricing_enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_get_a_paginated_list_of_products()
    {
        Http::fake([
            'test.myshopify.com/*' => Http::response(['products' => [['id' => 456, 'title' => 'Test Product']]], 200)
        ]);

        $response = $this->getJson('/api/admin/products?shop=' . $this->store->shop_domain);

        $response->assertStatus(200)
            ->assertJson(['products' => [['id' => 456]]]);
    }

    /** @test */
    public function it_can_get_a_single_product()
    {
        Http::fake([
            'test.myshopify.com/*' => Http::response(['product' => ['id' => 456, 'title' => 'Test Product']], 200)
        ]);

        $response = $this->getJson('/api/admin/products/456?shop=' . $this->store->shop_domain);

        $response->assertStatus(200)
            ->assertJson(['product' => ['id' => 456]]);
    }

    /** @test */
    public function it_can_search_for_products()
    {
        Http::fake([
            'test.myshopify.com/*' => Http::response(['products' => [['id' => 789, 'title' => 'Searched Product']]], 200)
        ]);

        $response = $this->postJson('/api/admin/products/search', [
            'query' => 'Searched',
            'shop' => $this->store->shop_domain,
        ]);

        $response->assertStatus(200)
            ->assertJson([['id' => 789]]);
    }

    /** @test */
    public function it_can_set_a_custom_price()
    {
        $setting = CustomerPricingSetting::create([
            'store_id' => $this->store->id,
            'shopify_customer_id' => 123,
            'customer_email' => 'test@example.com',
            'is_custom_pricing_enabled' => true
        ]);

        $response = $this->postJson('/api/admin/custom-prices', [
            'customer_pricing_setting_id' => $setting->id,
            'shopify_product_id' => 456,
            'shopify_variant_id' => 789,
            'product_title' => 'Test Product',
            'variant_title' => 'Test Variant',
            'original_price' => 19.99,
            'custom_price' => 15.99,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Custom price set successfully']);

        $this->assertDatabaseHas('custom_prices', [
            'shopify_variant_id' => 789,
            'custom_price' => 15.99
        ]);
    }
}