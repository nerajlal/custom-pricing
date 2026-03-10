<?php

/**
 * Register GDPR Webhooks with Shopify
 * This registers webhooks at the app level via Admin API
 */

$apiKey = 'c38580159e72cd6ca518fdbba4c9e7b5';
$apiSecret = env('SHOPIFY_API_SECRET');
$appId = '299145789441'; // From your Partner Dashboard URL

// Shopify Partner API endpoint
$url = "https://partners.shopify.com/api/apps/{$appId}/webhooks.json";

$webhooks = [
    [
        'topic' => 'customers/data_request',
        'address' => 'https://custompricing.task19.com/webhooks',
        'format' => 'json'
    ],
    [
        'topic' => 'customers/redact',
        'address' => 'https://custompricing.task19.com/webhooks',
        'format' => 'json'
    ],
    [
        'topic' => 'shop/redact',
        'address' => 'https://custompricing.task19.com/webhooks',
        'format' => 'json'
    ]
];

echo "Registering GDPR webhooks...\n\n";

foreach ($webhooks as $webhook) {
    echo "Registering: {$webhook['topic']}\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['webhook' => $webhook]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Shopify-Access-Token: ' . $apiSecret
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':' . $apiSecret);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $httpCode\n";
    echo "Response: $result\n\n";
}

echo "Done!\n";

