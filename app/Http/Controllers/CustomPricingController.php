<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\CustomerPricingSetting;
use App\Models\CustomPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Exception;

class CustomPricingController extends Controller
{
    /**
     * Get the Shopify API client for a given store.
     *
     * @param Store $store
     * @return \Illuminate\Http\Client\PendingRequest
     */
    private function getShopifyClient(Store $store)
    {
        return Http::withHeaders([
            'X-Shopify-Access-Token' => $store->access_token,
            'Content-Type' => 'application/json'
        ])->baseUrl("https://{$store->shop_domain}/admin/api/2024-01");
    }

    /**
     * Search for a customer by email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'shop' => 'required|string'
        ]);

        try {
            $store = Store::where('shop_domain', $request->shop)->firstOrFail();
            $client = $this->getShopifyClient($store);

            $response = $client->get('/customers/search.json', [
                'query' => "email:{$request->email}"
            ]);

            $response->throw();

            $customers = $response->json()['customers'];

            if (empty($customers)) {
                return response()->json(['message' => 'Customer not found'], 404);
            }

            $customer = $customers[0];

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
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching customer: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle custom pricing for a customer.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toggleCustomPricing(Request $request): JsonResponse
    {
        $request->validate([
            'customer_pricing_setting_id' => 'required|integer|exists:customer_pricing_settings,id',
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

    /**
     * Get a paginated list of products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProducts(Request $request): JsonResponse
    {
        $request->validate([
            'shop' => 'required|string',
            'limit' => 'integer|min:1|max:250',
            'page_info' => 'string'
        ]);

        try {
            $store = Store::where('shop_domain', $request->shop)->firstOrFail();
            $client = $this->getShopifyClient($store);

            $response = $client->get('/products.json', [
                'limit' => $request->input('limit', 50),
                'page_info' => $request->input('page_info')
            ]);

            $response->throw();

            return response()->json($response->json());
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching products: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a single product by its ID.
     *
     * @param Request $request
     * @param int $productId
     * @return JsonResponse
     */
    public function getProduct(Request $request, $productId): JsonResponse
    {
        $request->validate(['shop' => 'required|string']);

        try {
            $store = Store::where('shop_domain', $request->shop)->firstOrFail();
            $client = $this->getShopifyClient($store);

            $response = $client->get("/products/{$productId}.json");
            $response->throw();

            return response()->json($response->json());
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Search for products by title.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'shop' => 'required|string'
        ]);

        try {
            $store = Store::where('shop_domain', $request->shop)->firstOrFail();
            $client = $this->getShopifyClient($store);

            $response = $client->get('/products.json', [
                'title' => $request->query,
                'limit' => 20
            ]);

            $response->throw();

            return response()->json($response->json()['products']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching products: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Set a custom price for a product variant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setCustomPrice(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'customer_pricing_setting_id' => 'required|integer|exists:customer_pricing_settings,id',
            'shopify_product_id' => 'required|integer',
            'shopify_variant_id' => 'required|integer',
            'product_title' => 'required|string',
            'variant_title' => 'nullable|string',
            'original_price' => 'required|numeric|min:0',
            'custom_price' => 'required|numeric|min:0'
        ]);

        $customPrice = CustomPrice::updateOrCreate(
            [
                'customer_pricing_setting_id' => $validatedData['customer_pricing_setting_id'],
                'shopify_variant_id' => $validatedData['shopify_variant_id']
            ],
            $validatedData
        );

        return response()->json([
            'message' => 'Custom price set successfully',
            'custom_price' => $customPrice
        ]);
    }

    /**
     * Get all custom prices for a customer.
     *
     * @param Request $request
     * @param int $customerId
     * @return JsonResponse
     */
    public function getCustomerPrices(Request $request, $customerId): JsonResponse
    {
        $setting = CustomerPricingSetting::with('customPrices')->findOrFail($customerId);

        return response()->json([
            'setting' => $setting,
            'prices' => $setting->customPrices
        ]);
    }

    /**
     * Delete a custom price.
     *
     * @param int $priceId
     * @return JsonResponse
     */
    public function deleteCustomPrice($priceId): JsonResponse
    {
        $customPrice = CustomPrice::findOrFail($priceId);
        $customPrice->delete();

        return response()->json(['message' => 'Custom price deleted successfully']);
    }

    /**
     * API endpoint for the storefront to get a custom price for a variant.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCustomerPrice(Request $request): JsonResponse
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