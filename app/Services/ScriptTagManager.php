<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Log;

class ScriptTagManager
{
    /**
     * Install loyalty widget script tag on store
     */
    /**
     * Install all required script tags on store
     */
    public static function installAllScripts(Store $store)
    {
        try {
            $appUrl = rtrim(env('APP_URL'), '/');
            $scripts = [
                [
                    'src' => "{$appUrl}/app-proxy/script.js",
                    'displayScope' => 'ONLINE_STORE',
                ],
                [
                    'src' => "{$appUrl}/app-proxy/collection-script.js",
                    'displayScope' => 'ONLINE_STORE',
                ],
                [
                    'src' => "{$appUrl}/app-proxy/cart-script.js",
                    'displayScope' => 'ONLINE_STORE',
                ],
                [
                    'src' => "{$appUrl}/app-proxy/loyalty-cart.js",
                    'displayScope' => 'ONLINE_STORE',
                ]
            ];
            
            $service = new ShopifyGraphqlService();
            $mutation = <<<'GRAPHQL'
            mutation scriptTagCreate($input: ScriptTagInput!) {
                scriptTagCreate(input: $input) {
                    scriptTag {
                        id
                        src
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            GRAPHQL;

            foreach ($scripts as $script) {
                // Check if script tag already exists
                $existing = self::getExistingScriptTag($store, $script['src']);
                
                if ($existing) {
                    Log::info("Script tag already exists: {$script['src']}", ['store' => $store->shop_domain]);
                    continue;
                }

                $variables = ['input' => $script];
                $data = $service->query($store->shop_domain, $store->access_token, $mutation, $variables);

                if (!empty($data['scriptTagCreate']['userErrors'])) {
                    Log::error("Failed to install script tag: {$script['src']}", [
                        'store' => $store->shop_domain,
                        'errors' => $data['scriptTagCreate']['userErrors']
                    ]);
                } else {
                    Log::info("Script tag installed: {$script['src']}", [
                        'store' => $store->shop_domain
                    ]);
                }
            }
            return true;

        } catch (\Exception $e) {
            Log::error('Script tag installation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if script tag already exists
     */
    private static function getExistingScriptTag(Store $store, $scriptUrl)
    {
        try {
            $query = <<<'GRAPHQL'
            query {
                scriptTags(first: 20) {
                    edges {
                        node {
                            id
                            src
                            displayScope
                        }
                    }
                }
            }
            GRAPHQL;

            $service = new ShopifyGraphqlService();
            $data = $service->query($store->shop_domain, $store->access_token, $query);

            $edges = $data['scriptTags']['edges'] ?? [];
            
            foreach ($edges as $edge) {
                $node = $edge['node'];
                if ($node['src'] === $scriptUrl) {
                    return $node;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error checking existing script tags: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Uninstall loyalty widget script tag
     */
    public static function uninstallLoyaltyWidget(Store $store)
    {
        try {
            $scriptUrl = env('APP_URL') . '/app-proxy/loyalty-widget.js';
            $existing = self::getExistingScriptTag($store, $scriptUrl);

            if (!$existing) {
                return true; // Already removed
            }

            $mutation = <<<'GRAPHQL'
            mutation scriptTagDelete($id: ID!) {
                scriptTagDelete(id: $id) {
                    deletedScriptTagId
                    userErrors {
                        field
                        message
                    }
                }
            }
            GRAPHQL;

            $variables = ['id' => $existing['id']];
            $service = new ShopifyGraphqlService();
            $data = $service->query($store->shop_domain, $store->access_token, $mutation, $variables);

            if (!empty($data['scriptTagDelete']['userErrors'])) {
                Log::error('Failed to delete script tag', [
                    'errors' => $data['scriptTagDelete']['userErrors']
                ]);
                return false;
            }

            Log::info('Loyalty widget script tag uninstalled', [
                'store' => $store->shop_domain
            ]);
            return true;

        } catch (\Exception $e) {
            Log::error('Script tag uninstall error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reinstall script tag (useful for updates)
     */
    public static function reinstallLoyaltyWidget(Store $store)
    {
        self::uninstallLoyaltyWidget($store);
        sleep(1); // Give Shopify a moment
        return self::installLoyaltyWidget($store);
    }
}
