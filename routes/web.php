<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/



// Shopify OAuth routes
Route::get('/install', [ShopifyAuthController::class, 'install'])->name('install');
Route::get('/auth/callback', [ShopifyAuthController::class, 'callback'])->name('auth.callback');

// App interface (after authentication)
Route::get('/app', function () {
    return view('app');
})->name('app');

// Optional: Redirect root to install
Route::get('/', function () {
    return redirect('/install');
});

// App Proxy Routes (accessed via Shopify proxy)
Route::prefix('app-proxy')->group(function () {
    // Serve the custom price JavaScript
    Route::get('/script.js', function() {
        $jsContent = view('app-proxy.custom-price-script')->render();
        
        return response($jsContent)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=3600');
    })->name('app-proxy.script');

    // Health check endpoint
    Route::get('/ping', function() {
        return response()->json(['status' => 'ok', 'app' => 'custom-pricing']);
    });
});

Route::get('/installation', function() {
    return view('installation-guide');
})->name('installation');