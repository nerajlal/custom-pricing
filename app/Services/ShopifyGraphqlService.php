<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyGraphqlService
{
    /**
     * Execute a GraphQL query against the Shopify Admin API
     *
     * @param string $shop The shop domain
     * @param string $accessToken The access token
     * @param string $query The GraphQL query/mutation
     * @param array $variables Optional variables
     * @return array The response data or error
     * @throws \Exception
     */
    public function query($shop, $accessToken, $query, $variables = [])
    {
        $apiVersion = config('shopify.api_version', '2025-01');
        $url = "https://{$shop}/admin/api/{$apiVersion}/graphql.json";

        Log::info("GraphQL Request to {$shop}", [
            'query' => $this->truncateQuery($query),
            'variables' => $variables
        ]);

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'query' => $query,
            'variables' => $variables,
        ]);

        if (!$response->successful()) {
            Log::error("GraphQL HTTP Error", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception("GraphQL HTTP Error: " . $response->status());
        }

        $body = $response->json();

        // Check for top-level GraphQL errors (syntax, etc.)
        if (isset($body['errors'])) {
            Log::error("GraphQL API Error", ['errors' => $body['errors']]);
            throw new \Exception("GraphQL API Error: " . json_encode($body['errors']));
        }

        // Check for userErrors in mutations (optional helper, but good to inspect)
        // Usually nested in data->mutationName->userErrors
        
        return $body['data'] ?? [];
    }

    private function truncateQuery($query)
    {
        // Truncate for logging if too long
        return strlen($query) > 200 ? substr($query, 0, 200) . '...' : $query;
    }
}
