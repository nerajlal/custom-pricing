<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomPricingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Admin API routes (protected by Shopify session)
Route::prefix('admin')->middleware(['auth.shopify'])->group(function () {
    // Customer management
    Route::post('/customers/search', [CustomPricingController::class, 'searchCustomer']);
    Route::post('/customers/toggle-pricing', [CustomPricingController::class, 'toggleCustomPricing']);
    Route::get('/customers/{id}/prices', [CustomPricingController::class, 'getCustomerPrices']);
    
    // Product management
    Route::get('/products', [CustomPricingController::class, 'getProducts'])->name('products.index');
    Route::post('/products/search', [CustomPricingController::class, 'searchProducts']);
    Route::get('/products/{id}', [CustomPricingController::class, 'getProduct'])->name('products.show');
    
    // Custom pricing
    Route::post('/custom-prices', [CustomPricingController::class, 'setCustomPrice']);
    Route::delete('/custom-prices/{id}', [CustomPricingController::class, 'deleteCustomPrice']);
});

// Public API for storefront (requires API key or CORS setup)
Route::post('/storefront/custom-price', [CustomPricingController::class, 'getCustomerPrice']);

Route::post('/storefront/create-draft-order', [CustomPricingController::class, 'createDraftOrder']);




