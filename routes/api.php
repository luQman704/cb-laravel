<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ShippingMethodController;
use App\Http\Controllers\Api\TaxRateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All routes are under /api/ prefix (configured in bootstrap/app.php).
|
*/

// -------------------------------------------------------------------------
// Public routes — no authentication required
// -------------------------------------------------------------------------

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}/products', [CategoryController::class, 'products']);

// Cart — session-based for guests, token-based for authenticated customers.
// These routes do not require auth; the CartController resolves the cart
// from either the session (guest) or customer_id (auth).
Route::get('/cart', [CartController::class, 'show']);
Route::post('/cart/items', [CartController::class, 'addItem']);
Route::patch('/cart/items/{cartItemId}', [CartController::class, 'updateItem']);
Route::delete('/cart/items/{cartItemId}', [CartController::class, 'removeItem']);
Route::delete('/cart', [CartController::class, 'clear']);

// Shipping methods & tax rates
Route::get('/shipping-methods', [ShippingMethodController::class, 'index']);
Route::get('/tax-rates', [TaxRateController::class, 'index']);

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Checkout — optionally authenticated.
// No auth middleware here; CheckoutController reads $request->user('sanctum')
// which returns null for unauthenticated guests. Guest checkout requires
// guest_email in the request body.
Route::post('/checkout', [CheckoutController::class, 'store']);

// -------------------------------------------------------------------------
// Protected routes — Sanctum token required
// -------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});
