<?php

/**
 * Webhook Testing Script for GDPR Endpoints
 * Usage: php tests/webhook-tests/test-gdpr-webhooks.php
 */

// Configuration
$apiSecret = env('SHOPIFY_API_SECRET');
$baseUrl = 'https://custompricing.task19.com';
$shopDomain = 'test.myshopify.com';

// Color output functions
function success($message) {
    echo "\033[32m✓ $message\033[0m\n";
}

function error($message) {
    echo "\033[31m✗ $message\033[0m\n";
}

function info($message) {
    echo "\033[36mℹ $message\033[0m\n";
}

function testWebhook($endpoint, $payload, $apiSecret, $shopDomain) {
    $url = $endpoint;
    
    // Important: Convert to JSON exactly as it will be sent
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
    
    // Calculate HMAC on the exact JSON string
    $hmac = base64_encode(hash_hmac('sha256', $jsonPayload, $apiSecret, true));
    
    info("Testing: $url");
    info("HMAC: $hmac");
    
    // Send request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Shopify-Shop-Domain: ' . $shopDomain,
        'X-Shopify-Hmac-Sha256: ' . $hmac,
        'Content-Length: ' . strlen($jsonPayload)
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    echo "Status: $httpCode\n";
    echo "Response: $result\n";
    
    if ($httpCode == 200) {
        success("Request successful");
        return true;
    } else {
        error("Request failed");
        return false;
    }
}

// Test 1: Customers Data Request
echo "\n" . str_repeat("=", 60) . "\n";
echo "TEST 1: Customers Data Request (GDPR)\n";
echo str_repeat("=", 60) . "\n";

$payload1 = [
    'shop_domain' => $shopDomain,
    'customer' => [
        'id' => 123,
        'email' => 'customer@example.com',
        'phone' => '+1234567890'
    ],
    'orders_requested' => ['123', '456']
];

testWebhook("$baseUrl/webhooks/customers/data_request", $payload1, $apiSecret, $shopDomain);

// Test 2: Customers Redact
echo "\n" . str_repeat("=", 60) . "\n";
echo "TEST 2: Customers Redact (GDPR)\n";
echo str_repeat("=", 60) . "\n";

$payload2 = [
    'shop_domain' => $shopDomain,
    'customer' => [
        'id' => 123,
        'email' => 'customer@example.com'
    ]
];

testWebhook("$baseUrl/webhooks/customers/redact", $payload2, $apiSecret, $shopDomain);

// Test 3: Shop Redact
echo "\n" . str_repeat("=", 60) . "\n";
echo "TEST 3: Shop Redact (GDPR)\n";
echo str_repeat("=", 60) . "\n";

$payload3 = [
    'shop_domain' => $shopDomain,
    'shop_id' => 12345
];

testWebhook("$baseUrl/webhooks/shop/redact", $payload3, $apiSecret, $shopDomain);

echo "\n" . str_repeat("=", 60) . "\n";
echo "Testing Complete\n";
echo str_repeat("=", 60) . "\n\n";
