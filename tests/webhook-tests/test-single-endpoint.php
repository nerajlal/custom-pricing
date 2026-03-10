<?php

$apiSecret = env('SHOPIFY_API_SECRET');
$baseUrl = 'https://custompricing.task19.com';
$shopDomain = 'test.myshopify.com';

function testWebhook($topic, $payload, $apiSecret, $shopDomain) {
    $url = 'https://custompricing.task19.com/webhooks'; // Single endpoint
    
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $hmac = base64_encode(hash_hmac('sha256', $jsonPayload, $apiSecret, true));
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Testing: $topic\n";
    echo str_repeat("=", 60) . "\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Shopify-Shop-Domain: ' . $shopDomain,
        'X-Shopify-Topic: ' . $topic,
        'X-Shopify-Hmac-Sha256: ' . $hmac,
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $httpCode\n";
    echo "Response: $result\n";
    
    if ($httpCode == 200) {
        echo "\033[32m✓ Success\033[0m\n";
    } else {
        echo "\033[31m✗ Failed\033[0m\n";
    }
}

// Test 1: customers/data_request
testWebhook('customers/data_request', [
    'shop_domain' => $shopDomain,
    'customer' => ['id' => 123, 'email' => 'customer@example.com']
], $apiSecret, $shopDomain);

// Test 2: customers/redact
testWebhook('customers/redact', [
    'shop_domain' => $shopDomain,
    'customer' => ['id' => 123, 'email' => 'customer@example.com']
], $apiSecret, $shopDomain);

// Test 3: shop/redact
testWebhook('shop/redact', [
    'shop_domain' => $shopDomain,
    'shop_id' => 12345
], $apiSecret, $shopDomain);

echo "\n✅ All tests complete\n";
