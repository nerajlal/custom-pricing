<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\CustomerPricingSetting;
use App\Models\CustomPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CustomPricingController extends Controller
{
    private function getShopifyClient($store)
    {
        return Http::withHeaders([
            'X-Shopify-Access-Token' => $store->access_token,
            'Content-Type' => 'application/json'
        ])->baseUrl("https://{$store->shop_domain}/admin/api/2024-01");
    }

    // Search customer by email
    public function searchCustomer(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'shop' => 'required|string'
        ]);

        $store = Store::where('shop_domain', $request->shop)->firstOrFail();
        $client = $this->getShopifyClient($store);

        $response = $client->get('/customers/search.json', [
            'query' => "email:{$request->email}"
        ]);

        if ($response->successful()) {
            $customers = $response->json()['customers'];
            
            if (empty($customers)) {
                return response()->json(['message' => 'Customer not found'], 404);
            }

            $customer = $customers[0];
            
            // Get or create customer pricing setting
            $setting = CustomerPricingSetting::firstOrCreate(
                [
                    'store_id' => $store->id,
                    'shopify_customer_id' => $customer['id']
                ],
                [
                    'customer_email' => $customer['email'],
                    'is_custom_pricing_enabled' => false
                ]
            );

            return response()->json([
                'customer' => $customer,
                'pricing_setting' => $setting
            ]);
        }

        return response()->json(['message' => 'Error fetching customer'], 500);
    }

    // Toggle custom pricing for customer
    public function toggleCustomPricing(Request $request)
    {
        $request->validate([
            'customer_pricing_setting_id' => 'required|integer',
            'enabled' => 'required|boolean'
        ]);

        $setting = CustomerPricingSetting::findOrFail($request->customer_pricing_setting_id);
        $setting->is_custom_pricing_enabled = $request->enabled;
        $setting->save();

        return response()->json([
            'message' => 'Custom pricing updated successfully',
            'setting' => $setting
        ]);
    }

    // Search products
    public function searchProducts(Request $request)
    {
        $request->validate([
            'query' => 'required|string',
            'shop' => 'required|string'
        ]);

        $store = Store::where('shop_domain', $request->shop)->firstOrFail();
        $client = $this->getShopifyClient($store);

        $response = $client->get('/products.json', [
            'title' => $request->query,
            'limit' => 20
        ]);

        if ($response->successful()) {
            return response()->json($response->json()['products']);
        }

        return response()->json(['message' => 'Error fetching products'], 500);
    }

    // Set custom price for product
    public function setCustomPrice(Request $request)
    {
        $request->validate([
            'customer_pricing_setting_id' => 'required|integer',
            'shopify_product_id' => 'required|integer',
            'shopify_variant_id' => 'required|integer',
            'product_title' => 'required|string',
            'variant_title' => 'nullable|string',
            'original_price' => 'required|numeric|min:0',
            'custom_price' => 'required|numeric|min:0'
        ]);

        $customPrice = CustomPrice::updateOrCreate(
            [
                'customer_pricing_setting_id' => $request->customer_pricing_setting_id,
                'shopify_variant_id' => $request->shopify_variant_id
            ],
            [
                'shopify_product_id' => $request->shopify_product_id,
                'product_title' => $request->product_title,
                'variant_title' => $request->variant_title,
                'original_price' => $request->original_price,
                'custom_price' => $request->custom_price
            ]
        );

        return response()->json([
            'message' => 'Custom price set successfully',
            'custom_price' => $customPrice
        ]);
    }

    // Get all custom prices for a customer
    public function getCustomerPrices(Request $request, $customerId)
    {
        $setting = CustomerPricingSetting::with('customPrices')->findOrFail($customerId);
        
        return response()->json([
            'setting' => $setting,
            'prices' => $setting->customPrices
        ]);
    }

    // Delete custom price
    public function deleteCustomPrice(Request $request, $priceId)
    {
        $customPrice = CustomPrice::findOrFail($priceId);
        $customPrice->delete();

        return response()->json(['message' => 'Custom price deleted successfully']);
    }

    // API endpoint for storefront to check custom pricing
    public function getCustomerPrice(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'variant_id' => 'required|integer',
            'shop' => 'required|string'
        ]);

        $store = Store::where('shop_domain', $request->shop)->first();
        
        if (!$store) {
            return response()->json(['has_custom_price' => false]);
        }

        $setting = CustomerPricingSetting::where('store_id', $store->id)
            ->where('shopify_customer_id', $request->customer_id)
            ->where('is_custom_pricing_enabled', true)
            ->first();

        if (!$setting) {
            return response()->json(['has_custom_price' => false]);
        }

        $customPrice = CustomPrice::where('customer_pricing_setting_id', $setting->id)
            ->where('shopify_variant_id', $request->variant_id)
            ->first();

        if ($customPrice) {
            return response()->json([
                'has_custom_price' => true,
                'custom_price' => $customPrice->custom_price,
                'original_price' => $customPrice->original_price,
                'currency' => $request->currency ?? 'USD'
            ]);
        }

        return response()->json(['has_custom_price' => false]);
    }
}