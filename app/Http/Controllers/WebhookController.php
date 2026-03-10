<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\LoyaltySetting;
use App\Models\CustomerLoyaltyAccount;
use App\Models\GdprRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    // Verify Shopify webhook
    private function verifyWebhook(Request $request)
{
    $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
    
    // If no HMAC header, reject the request
    if (!$hmacHeader) {
        Log::warning('Webhook received without HMAC header', [
            'ip' => $request->ip(),
            'headers' => $request->headers->all()
        ]);
        return false;
    }
    
    $data = $request->getContent();
    $calculatedHmac = base64_encode(hash_hmac('sha256', $data, env('SHOPIFY_API_SECRET'), true));

    return hash_equals($hmacHeader, $calculatedHmac);
}

    // Handle order creation - Award loyalty points
    public function orderCreate(Request $request)
    {
        if (!$this->verifyWebhook($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $shop = $request->header('X-Shopify-Shop-Domain');
        $order = $request->all();

        Log::info('Order webhook received', ['shop' => $shop, 'order_id' => $order['id']]);

        try {
            $store = Store::where('shop_domain', $shop)->first();
            if (!$store) {
                return response()->json(['message' => 'Store not found'], 404);
            }

            $settings = LoyaltySetting::where('store_id', $store->id)->first();
            if (!$settings || !$settings->is_enabled) {
                return response()->json(['message' => 'Loyalty disabled'], 200);
            }

            // Check if order has customer
            if (!isset($order['customer']) || !$order['customer']) {
                return response()->json(['message' => 'No customer'], 200);
            }

            $account = CustomerLoyaltyAccount::where('store_id', $store->id)
                ->where('shopify_customer_id', $order['customer']['id'])
                ->first();

            if (!$account) {
                return response()->json(['message' => 'No loyalty account'], 200);
            }

            // Calculate points based on order total
            $orderTotal = floatval($order['total_price']);
            $points = $settings->calculatePointsForAmount($orderTotal);

            // Apply tier multiplier
            if ($account->tier) {
                $points = floor($points * ($account->tier->points_multiplier / 100));
            }

            // Award points
            $account->addPoints($points, 'earn', "Purchase order {$order['name']}", [
                'order_id' => $order['id'],
                'order_name' => $order['name'],
                'order_amount' => $orderTotal
            ]);

            Log::info('Points awarded', [
                'customer_id' => $order['customer']['id'],
                'points' => $points,
                'order_id' => $order['id']
            ]);

            return response()->json([
                'message' => 'Points awarded',
                'points' => $points
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    // Handle order refund - Deduct loyalty points
    public function orderRefund(Request $request)
    {
        if (!$this->verifyWebhook($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $shop = $request->header('X-Shopify-Shop-Domain');
        $order = $request->all();

        try {
            $store = Store::where('shop_domain', $shop)->first();
            if (!$store) return response()->json(['message' => 'Store not found'], 404);

            $settings = LoyaltySetting::where('store_id', $store->id)->first();
            if (!$settings || !$settings->is_enabled) {
                return response()->json(['message' => 'Loyalty disabled'], 200);
            }

            if (!isset($order['customer']) || !$order['customer']) {
                return response()->json(['message' => 'No customer'], 200);
            }

            $account = CustomerLoyaltyAccount::where('store_id', $store->id)
                ->where('shopify_customer_id', $order['customer']['id'])
                ->first();

            if (!$account) {
                return response()->json(['message' => 'No loyalty account'], 200);
            }

            // Calculate points to deduct based on refund amount
            $refundAmount = 0;
            foreach ($order['refunds'] as $refund) {
                $refundAmount += floatval($refund['total_additional_fees_set']['shop_money']['amount']);
            }

            $pointsToDeduct = $settings->calculatePointsForAmount($refundAmount);

            // Deduct points (only if they have enough)
            if ($account->current_points_balance >= $pointsToDeduct) {
                $account->deductPoints(
                    $pointsToDeduct,
                    'refund',
                    "Refund for order {$order['name']}"
                );
            }

            return response()->json(['message' => 'Points adjusted for refund']);

        } catch (\Exception $e) {
            Log::error('Refund webhook error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    // GDPR Webhooks

    // GDPR Webhooks WITH LOGGING

public function customersDataRequest(Request $request)
{
    if (!$this->verifyWebhook($request)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $shop = $request->header('X-Shopify-Shop-Domain');
    $data = $request->all();

    // Log the GDPR request
    $gdprRequest = GdprRequest::create([
        'type' => 'data_request',
        'shop_domain' => $shop,
        'customer_id' => $data['customer']['id'] ?? null,
        'payload' => $data,
        'status' => 'pending',
    ]);

    try {
        $store = Store::where('shop_domain', $shop)->first();
        
        // IMPORTANT: Return 200 even if store doesn't exist
        if (!$store) {
            $gdprRequest->update([
                'status' => 'processed',
                'processed_at' => now()
            ]);
            Log::info('GDPR Data Request for non-existent store', ['shop' => $shop]);
            return response()->json(['message' => 'Data request processed'], 200);
        }

        $customerId = $data['customer']['id'];
        
        $loyaltyAccount = CustomerLoyaltyAccount::with('transactions', 'redemptions')
            ->where('store_id', $store->id)
            ->where('shopify_customer_id', $customerId)
            ->first();

        // Mark as processed
        $gdprRequest->update([
            'status' => 'processed',
            'processed_at' => now()
        ]);

        Log::info('GDPR Data Request', [
            'shop' => $shop,
            'customer_id' => $customerId,
            'gdpr_request_id' => $gdprRequest->id,
            'has_loyalty_data' => $loyaltyAccount !== null
        ]);

        return response()->json(['message' => 'Data request processed']);
        
    } catch (\Exception $e) {
        $gdprRequest->update(['status' => 'failed']);
        Log::error('GDPR Data Request failed', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Processing failed'], 500);
    }
}

public function customersRedact(Request $request)
{
    if (!$this->verifyWebhook($request)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $shop = $request->header('X-Shopify-Shop-Domain');
    $data = $request->all();

    // Log the GDPR request
    $gdprRequest = GdprRequest::create([
        'type' => 'customer_redact',
        'shop_domain' => $shop,
        'customer_id' => $data['customer']['id'] ?? null,
        'payload' => $data,
        'status' => 'pending',
    ]);

    try {
        $store = Store::where('shop_domain', $shop)->first();
        
        // IMPORTANT: Return 200 even if store doesn't exist
        if (!$store) {
            $gdprRequest->update([
                'status' => 'processed',
                'processed_at' => now()
            ]);
            Log::info('GDPR Customer Redact for non-existent store', ['shop' => $shop]);
            return response()->json(['message' => 'Customer data deleted'], 200);
        }

        $customerId = $data['customer']['id'];

        $loyaltyAccount = CustomerLoyaltyAccount::where('store_id', $store->id)
            ->where('shopify_customer_id', $customerId)
            ->first();

        if ($loyaltyAccount) {
            $loyaltyAccount->delete();
        }

        // Mark as processed
        $gdprRequest->update([
            'status' => 'processed',
            'processed_at' => now()
        ]);

        Log::info('GDPR Customer Redact', [
            'shop' => $shop,
            'customer_id' => $customerId,
            'gdpr_request_id' => $gdprRequest->id,
            'deleted' => $loyaltyAccount !== null
        ]);

        return response()->json(['message' => 'Customer data deleted']);
        
    } catch (\Exception $e) {
        $gdprRequest->update(['status' => 'failed']);
        Log::error('GDPR Customer Redact failed', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Processing failed'], 500);
    }
}
public function shopRedact(Request $request)
{
    if (!$this->verifyWebhook($request)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $shop = $request->header('X-Shopify-Shop-Domain');

    // Log the GDPR request
    $gdprRequest = GdprRequest::create([
        'type' => 'shop_redact',
        'shop_domain' => $shop,
        'payload' => $request->all(),
        'status' => 'pending',
    ]);

    try {
        $store = Store::where('shop_domain', $shop)->first();
        if ($store) {
            $store->delete(); // Cascades to all related data
        }

        // Mark as processed
        $gdprRequest->update([
            'status' => 'processed',
            'processed_at' => now()
        ]);

        Log::info('GDPR Shop Redact', [
            'shop' => $shop,
            'gdpr_request_id' => $gdprRequest->id,
            'deleted' => $store !== null
        ]);

        return response()->json(['message' => 'Shop data deleted']);
        
    } catch (\Exception $e) {
        $gdprRequest->update(['status' => 'failed']);
        Log::error('GDPR Shop Redact failed', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Processing failed'], 500);
    }
}
}
