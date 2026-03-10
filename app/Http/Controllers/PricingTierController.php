<?php

namespace App\Http\Controllers;

use App\Models\PricingTier;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PricingTierController extends Controller
{
    /**
     * Get all pricing tiers for a shop.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate(['shop' => 'required|string']);
        
        $store = Store::where('shop_domain', $request->shop)->firstOrFail();
        
        $tiers = PricingTier::where('store_id', $store->id)
            ->withCount('customers')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($tiers);
    }

    /**
     * Create a new pricing tier.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'shop' => 'required|string',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $store = Store::where('shop_domain', $request->shop)->firstOrFail();

        $tier = PricingTier::create([
            'store_id' => $store->id,
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'message' => 'Pricing tier created successfully',
            'tier' => $tier
        ], 201);
    }

    /**
     * Update a pricing tier.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $tier = PricingTier::findOrFail($id);
        
        $tier->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'message' => 'Pricing tier updated successfully',
            'tier' => $tier
        ]);
    }

    /**
     * Delete a pricing tier.
     */
    public function destroy($id): JsonResponse
    {
        $tier = PricingTier::findOrFail($id);
        $tier->delete();

        return response()->json(['message' => 'Pricing tier deleted successfully']);
    }

    /**
     * Get all members (customers) of a pricing tier.
     */
    public function getMembers(Request $request, $id): JsonResponse
    {
        $tier = PricingTier::findOrFail($id);
        
        $members = $tier->customers()
            ->select('id', 'shopify_customer_id', 'customer_email', 'pricing_tier_id')
            ->get();

        return response()->json($members);
    }
}
