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

