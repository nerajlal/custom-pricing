<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\CustomerPricingSetting;
use App\Models\CustomPrice;
use App\Models\PricingTier;
use App\Services\ShopifyGraphqlService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class CustomPricingController extends Controller
{
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
            // Use store identified by middleware if available
            $store = $request->attributes->get('store') ?? Store::where('shop_domain', $request->shop)->first();
            
            if (!$store) {
                Log::error("Store not found for search: {$request->shop}");
                return response()->json(['message' => 'Store not found. Please re-install the app.'], 404);
            }

            // Log token status (masked) for debugging 401s
            $token = $store->access_token;
            $maskedToken = $token ? substr($token, 0, 10) . '...' . substr($token, -5) : 'MISSING';
            Log::info("Searching customer for store: {$store->shop_domain} with token: {$maskedToken}");

            $service = new ShopifyGraphqlService();

            $query = <<<'GRAPHQL'
            query customers($query: String!) {
                customers(first: 10, query: $query) {
                    edges {
                        node {
                            id
                            email
                            firstName
                            lastName
                        }
                    }
                }
            }
            GRAPHQL;

            $data = $service->query($store->shop_domain, $store->access_token, $query, [
                'query' => "email:{$request->email}*" 
            ]);
            // Added wildcard * to help with partial matches if needed, though strictly email usually exact. 
            // Just passed email is fine. Let's stick to original query param but with check.

            $edges = $data['customers']['edges'] ?? [];

            if (empty($edges)) {
                return response()->json(['message' => 'Customer not found in Shopify'], 404);
            }

            // Iterate to find exact match if multiple returned (due to partial) or just take first
            $customerNode = $edges[0]['node'];
            $numericId = (int) filter_var($customerNode['id'], FILTER_SANITIZE_NUMBER_INT);

            // Fetch or create existing setting
            $setting = CustomerPricingSetting::with('tier')->firstOrCreate(
                [
                    'store_id' => $store->id,
                    'shopify_customer_id' => $numericId
                ],
                [
                    'customer_email' => $customerNode['email'],
                    'is_custom_pricing_enabled' => false
                ]
            );

            return response()->json([
                'customer' => [
                    'id' => $numericId,
                    'email' => $customerNode['email'],
                    'first_name' => $customerNode['firstName'],
                    'last_name' => $customerNode['lastName']
                ],
                'pricing_setting' => $setting
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error searching customer: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Assign a customer to a pricing tier.
     */
    public function assignCustomerToTier(Request $request): JsonResponse
    {
        $request->validate([
            'shop' => 'required|string',
            'shopify_customer_id' => 'required|integer',
            'email' => 'required|email',
            'pricing_tier_id' => 'required|integer|exists:pricing_tiers,id'
        ]);

        $store = Store::where('shop_domain', $request->shop)->firstOrFail();

        $setting = CustomerPricingSetting::updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_customer_id' => $request->shopify_customer_id
            ],
            [
                'customer_email' => $request->email,
                'pricing_tier_id' => $request->pricing_tier_id,
                'is_custom_pricing_enabled' => true
            ]
        );

        return response()->json([
            'message' => 'Customer assigned to tier successfully',
            'setting' => $setting
        ]);
    }

    /**
     * Remove a customer from a tier.
     */
    public function removeCustomerFromTier(Request $request): JsonResponse
    {
        $request->validate([
            'customer_pricing_setting_id' => 'required|integer'
        ]);

        $setting = CustomerPricingSetting::findOrFail($request->customer_pricing_setting_id);
        $setting->update([
            'pricing_tier_id' => null,
            'is_custom_pricing_enabled' => false
        ]);

        return response()->json(['message' => 'Customer removed from tier']);
    }

    /**
     * Get a paginated list of products.
     */
    public function getProducts(Request $request): JsonResponse
    {
        $request->validate([
            'shop' => 'required|string',
            'limit' => 'integer|min:1|max:250',
            'cursor' => 'string|nullable'
        ]);

        try {
            $store = Store::where('shop_domain', $request->shop)->firstOrFail();
            $service = new ShopifyGraphqlService();

            $query = <<<'GRAPHQL'
            query products($first: Int!, $after: String) {
                products(first: $first, after: $after) {
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                    edges {
                        node {
                            id
                            title
                            handle
                            images(first: 1) {
                                edges {
                                    node {
                                        originalSrc
                                    }
                                }
                            }
                            variants(first: 10) {
                                edges {
                                    node {
                                        id
                                        title
                                        price
                                        sku
                                    }
                                }
                            }
                        }
                    }
                }
            }
            GRAPHQL;

            $variables = [
                'first' => (int) $request->input('limit', 50),
                'after' => $request->input('cursor')
            ];

            $data = $service->query($store->shop_domain, $store->access_token, $query, $variables);
            
            $products = [];
            foreach ($data['products']['edges'] as $edge) {
                $node = $edge['node'];
                $products[] = [
                    'id' => (int) filter_var($node['id'], FILTER_SANITIZE_NUMBER_INT),
                    'title' => $node['title'],
                    'handle' => $node['handle'],
                    'image' => $node['images']['edges'][0]['node']['originalSrc'] ?? null,
                    'variants' => array_map(function($v) {
                        return [
                            'id' => (int) filter_var($v['node']['id'], FILTER_SANITIZE_NUMBER_INT),
                            'title' => $v['node']['title'],
                            'price' => $v['node']['price'],
                            'sku' => $v['node']['sku']
                        ];
                    }, $node['variants']['edges'])
                ];
            }

            return response()->json([
                'products' => $products,
                'page_info' => $data['products']['pageInfo']['endCursor']
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching products: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get a single product by its ID.
     */
    public function getProduct(Request $request, $productId): JsonResponse
    {
        $request->validate(['shop' => 'required|string']);

        try {
            $store = Store::where('shop_domain', $request->shop)->firstOrFail();
            $service = new ShopifyGraphqlService();

            $query = <<<'GRAPHQL'
            query product($id: ID!) {
                product(id: $id) {
                    id
                    title
                    handle
                    descriptionHtml
                    images(first: 1) {
                        edges {
                            node {
                                originalSrc
                            }
                        }
                    }
                    variants(first: 50) {
                        edges {
                            node {
                                id
                                title
                                price
                                sku
                            }
                        }
                    }
                }
            }
            GRAPHQL;

            $data = $service->query($store->shop_domain, $store->access_token, $query, [
                'id' => "gid://shopify/Product/{$productId}"
            ]);

            if (empty($data['product'])) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            $node = $data['product'];
            $product = [
                'id' => (int) filter_var($node['id'], FILTER_SANITIZE_NUMBER_INT),
                'title' => $node['title'],
                'body_html' => $node['descriptionHtml'],
                'image' => [
                    'src' => $node['images']['edges'][0]['node']['originalSrc'] ?? null
                ],
                'variants' => array_map(function($v) {
                    return [
                        'id' => (int) filter_var($v['node']['id'], FILTER_SANITIZE_NUMBER_INT),
                        'title' => $v['node']['title'],
                        'price' => $v['node']['price'],
                        'sku' => $v['node']['sku']
                    ];
                }, $node['variants']['edges'])
            ];

            return response()->json(['product' => $product]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Search for products by title.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'shop' => 'required|string'
        ]);

        try {
            $store = Store::where('shop_domain', $request->shop)->firstOrFail();
            $service = new ShopifyGraphqlService();

            $query = <<<'GRAPHQL'
            query products($query: String!) {
                products(first: 20, query: $query) {
                    edges {
                        node {
                            id
                            title
                            handle
                            images(first: 1) {
                                edges {
                                    node {
                                        originalSrc
                                    }
                                }
                            }
                            variants(first: 10) {
                                edges {
                                    node {
                                        id
                                        title
                                        price
                                        sku
                                    }
                                }
                            }
                        }
                    }
                }
            }
            GRAPHQL;

            $data = $service->query($store->shop_domain, $store->access_token, $query, [
                'query' => "title:*{$request->input('query')}*"
            ]);

            $products = [];
            foreach ($data['products']['edges'] as $edge) {
                $node = $edge['node'];
                $products[] = [
                    'id' => (int) filter_var($node['id'], FILTER_SANITIZE_NUMBER_INT),
                    'title' => $node['title'],
                    'handle' => $node['handle'],
                    'image' => [
                        'src' => $node['images']['edges'][0]['node']['originalSrc'] ?? null
                    ],
                    'variants' => array_map(function($v) {
                        return [
                            'id' => (int) filter_var($v['node']['id'], FILTER_SANITIZE_NUMBER_INT),
                            'title' => $v['node']['title'],
                            'price' => $v['node']['price'],
                            'sku' => $v['node']['sku']
                        ];
                    }, $node['variants']['edges'])
                ];
            }

            return response()->json(array_values($products));
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching products: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Set a custom price for a product variant in a specific tier.
     */
    public function setCustomPrice(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'pricing_tier_id' => 'nullable|integer|exists:pricing_tiers,id',
            'customer_pricing_setting_id' => 'nullable|integer|exists:customer_pricing_settings,id',
            'shopify_product_id' => 'required|integer',
            'shopify_variant_id' => 'required|integer',
            'product_title' => 'required|string',
            'variant_title' => 'nullable|string',
            'original_price' => 'required|numeric|min:0',
            'custom_price' => 'required|numeric|min:0'
        ]);

        if (empty($validatedData['pricing_tier_id']) && empty($validatedData['customer_pricing_setting_id'])) {
            return response()->json(['message' => 'Either Pricing Tier or Customer Pricing Setting is required.'], 422);
        }

        $conditions = [
            'shopify_variant_id' => $validatedData['shopify_variant_id']
        ];

        if (!empty($validatedData['pricing_tier_id'])) {
            $conditions['pricing_tier_id'] = $validatedData['pricing_tier_id'];
            // Ensure we don't accidentally match a customer specific one if we are setting a tier one
             $conditions['customer_pricing_setting_id'] = null; 
        } else {
            $conditions['customer_pricing_setting_id'] = $validatedData['customer_pricing_setting_id'];
             $conditions['pricing_tier_id'] = null;
        }

        $customPrice = CustomPrice::updateOrCreate(
            $conditions,
            $validatedData
        );

        return response()->json([
            'message' => 'Custom price set successfully',
            'custom_price' => $customPrice
        ]);
    }

    /**
     * Get all custom prices for a specific tier.
     */
    public function getTierPrices(Request $request, $tierId): JsonResponse
    {
        $tier = PricingTier::with('customPrices')->findOrFail($tierId);

        return response()->json([
            'tier' => $tier,
            'prices' => $tier->customPrices
        ]);
    }

    /**
     * Get custom prices for a specific customer setting.
     */
    public function getCustomerPrices(Request $request, $settingId): JsonResponse
    {
        $setting = CustomerPricingSetting::with(['customPrices' => function($query) {
            $query->orderBy('updated_at', 'desc');
        }])->findOrFail($settingId);

        return response()->json([
            'prices' => $setting->customPrices
        ]);
    }

    /**
     * Delete a custom price.
     */
    public function deleteCustomPrice($priceId): JsonResponse
    {
        $customPrice = CustomPrice::findOrFail($priceId);
        $customPrice->delete();

        return response()->json(['message' => 'Custom price deleted successfully']);
    }

    /**
     * API endpoint for the storefront to get a custom price.
     * Looks up customer -> tier -> price.
     */
    /**
     * API endpoint for the storefront to get a custom price.
     * Looks up customer -> tier -> price.
     */
    public function getCustomerPrice(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'variant_id' => 'required|integer',
            'shop' => 'required|string'
        ]);

        // Use store from middleware if available, otherwise fetch
        $store = $request->attributes->get('store');
        if (!$store) {
            $store = Store::where('shop_domain', $request->shop)->first();
        }

        if (!$store) {
            return response()->json(['has_custom_price' => false]);
        }

        // 1. Find the customer's pricing setting
        // Optimization: Use simple where clauses that match composite indices if available
        $setting = CustomerPricingSetting::where('store_id', $store->id)
            ->where('shopify_customer_id', $request->customer_id)
            ->where('is_custom_pricing_enabled', true)
            ->first();

        if (!$setting) {
            return response()->json(['has_custom_price' => false])
                ->header('Cache-Control', 'private, max-age=300'); // Cache negative result for 5 mins
        }

        // 2. Priority 1: Check for Individual Customer Price
        $individualPrice = CustomPrice::where('customer_pricing_setting_id', $setting->id)
            ->where('shopify_variant_id', $request->variant_id)
            ->first();

        if ($individualPrice) {
            return response()->json([
                'has_custom_price' => true,
                'custom_price' => $individualPrice->custom_price,
                'original_price' => $individualPrice->original_price,
                'currency' => $request->currency ?? 'USD',
                'source' => 'individual'
            ])->header('Cache-Control', 'private, max-age=300');
        }

        // 3. Priority 2: Check for Tier Price (if assigned)
        if ($setting->pricing_tier_id) {
            $tierPrice = CustomPrice::where('pricing_tier_id', $setting->pricing_tier_id)
                ->where('shopify_variant_id', $request->variant_id)
                ->first();

            if ($tierPrice) {
                return response()->json([
                    'has_custom_price' => true,
                    'custom_price' => $tierPrice->custom_price,
                    'original_price' => $tierPrice->original_price,
                    'currency' => $request->currency ?? 'USD',
                    'source' => 'tier'
                ])->header('Cache-Control', 'private, max-age=300');
            }
        }

        return response()->json(['has_custom_price' => false])
            ->header('Cache-Control', 'private, max-age=300');
    }


    /**
     * Create draft order with custom pricing.
     */
    public function createDraftOrder(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'variant_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'shop' => 'required|string'
        ]);

        $store = Store::where('shop_domain', $request->shop)->firstOrFail();
        
        // 1. Check if customer has a tier
        $setting = CustomerPricingSetting::where('store_id', $store->id)
            ->where('shopify_customer_id', $request->customer_id)
            ->where('is_custom_pricing_enabled', true)
            ->whereNotNull('pricing_tier_id')
            ->first();

        if (!$setting) {
            return response()->json(['has_custom_price' => false]);
        }

        // 2. Check if price exists for tier
        $customPrice = CustomPrice::where('pricing_tier_id', $setting->pricing_tier_id)
            ->where('shopify_variant_id', $request->variant_id)
            ->first();

        if (!$customPrice) {
            // Fallback: check if we should create a standard order or fail?
            // If API is called, it assumes custom price. 
            return response()->json(['has_custom_price' => false]);
        }

        // Create draft order with custom price
        $service = new ShopifyGraphqlService();

        $mutation = <<<'GRAPHQL'
        mutation draftOrderCreate($input: DraftOrderInput!) {
            draftOrderCreate(input: $input) {
                draftOrder {
                    id
                    invoiceUrl
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GRAPHQL;

        $variables = [
            'input' => [
                'customerId' => "gid://shopify/Customer/{$request->customer_id}",
                'lineItems' => [
                    [
                        'title' => $customPrice->product_title ?? 'Custom Item',
                        'originalUnitPrice' => (string)$customPrice->custom_price,
                        'quantity' => $request->quantity,
                        'requiresShipping' => true,
                        // Passing actual variant ID allows inventory tracking, but overriding price requires careful field usage
                        // 'variantId' => "gid://shopify/ProductVariant/{$request->variant_id}",
                        'customAttributes' => [
                            ['key' => '_custom_pricing_tier', 'value' => (string)$setting->pricing_tier_id]
                        ]
                    ]
                ],
                'note' => 'Custom pricing tier applied'
            ]
        ];

        $data = $service->query($store->shop_domain, $store->access_token, $mutation, $variables);

        if (!empty($data['draftOrderCreate']['userErrors'])) {
            return response()->json(['success' => false, 'errors' => $data['draftOrderCreate']['userErrors']], 500);
        }

        $draftOrder = $data['draftOrderCreate']['draftOrder'];

        return response()->json([
            'success' => true,
            'invoice_url' => $draftOrder['invoiceUrl']
        ]);
    }
}
