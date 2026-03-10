<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\ShopifyGraphqlService;
use Illuminate\Support\Facades\Log;

class ScriptTagController extends Controller
{
    public function installScriptTags($shop)
    {
        $store = Store::where('shop_domain', $shop)->first();
        if (!$store) {
            return false;
        }

        $service = new ShopifyGraphqlService();
        $appUrl = env('APP_URL');

        // Script tags to install
        $scripts = [
            [
                'event' => 'ON_LOAD',
                'src' => "{$appUrl}/app-proxy/script.js",
                'displayScope' => 'ONLINE_STORE' 
            ],
            [
                'event' => 'ON_LOAD',
                'src' => "{$appUrl}/app-proxy/collection-script.js",
                'displayScope' => 'ONLINE_STORE'
            ],
            [
                'event' => 'ON_LOAD',
                'src' => "{$appUrl}/app-proxy/cart-script.js",
                'displayScope' => 'ONLINE_STORE'
            ],
            [
                'event' => 'ON_LOAD',
                'src' => "{$appUrl}/app-proxy/loyalty-cart.js",
                'displayScope' => 'ONLINE_STORE'
            ]
        ];

        foreach ($scripts as $script) {
            try {
                // Check if script already exists
                $query = <<<'GRAPHQL'
                query {
                    scriptTags(first: 20) {
                        edges {
                            node {
                                id
                                src
                            }
                        }
                    }
                }
                GRAPHQL;

                $data = $service->query($shop, $store->access_token, $query);
                $existingEdges = $data['scriptTags']['edges'] ?? [];
                
                $exists = false;
                foreach ($existingEdges as $edge) {
                    if ($edge['node']['src'] === $script['src']) {
                        $exists = true;
                        break;
                    }
                }

                if ($exists) {
                    Log::info('Script tag already exists', ['src' => $script['src']]);
                    continue;
                }

                // Create script tag
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

                $variables = ['input' => $script];
                $createData = $service->query($shop, $store->access_token, $mutation, $variables);

                if (empty($createData['scriptTagCreate']['userErrors'])) {
                    Log::info('Script tag installed', ['src' => $script['src']]);
                } else {
                    Log::error('Failed to install script tag', [
                        'src' => $script['src'],
                        'errors' => $createData['scriptTagCreate']['userErrors']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Script tag installation error', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return true;
    }

    public function uninstallScriptTags($shop)
    {
        $store = Store::where('shop_domain', $shop)->first();
        if (!$store) {
            return false;
        }

        $service = new ShopifyGraphqlService();

        try {
            // Get all script tags
            $query = <<<'GRAPHQL'
            query {
                scriptTags(first: 20) {
                    edges {
                        node {
                            id
                            src
                        }
                    }
                }
            }
            GRAPHQL;

            $data = $service->query($shop, $store->access_token, $query);
            $edges = $data['scriptTags']['edges'] ?? [];
            $appUrl = env('APP_URL');

            foreach ($edges as $edge) {
                $node = $edge['node'];
                // Delete script tags that belong to this app
                // Refined check: either contains APP_URL (safer for self-hosted) or legacy markers
                if (str_contains($node['src'], $appUrl) || 
                    str_contains($node['src'], 'loyalty-widgets') || 
                    str_contains($node['src'], 'loyalty-cart')) {
                    
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

                    $service->query($shop, $store->access_token, $mutation, ['id' => $node['id']]);
                    Log::info('Script tag uninstalled', ['id' => $node['id']]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Script tag uninstall error', ['error' => $e->getMessage()]);
        }

        return true;
    }
}
