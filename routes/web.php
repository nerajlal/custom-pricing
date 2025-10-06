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

Route::get('/', function () {
    return view('welcome');
});


Route::get('/install', [ShopifyAuthController::class, 'install']);
Route::get('/auth/callback', [ShopifyAuthController::class, 'callback']);
Route::get('/app', function () {
    return view('app'); // Your admin interface
});