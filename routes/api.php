<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CartProductController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


//Authentication Routes:
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', 'logout');
    });
});

//Store & Product & Product-Images
Route::apiResource('stores', StoreController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('products.images', ImageController::class)->only(['show', 'store', 'index']);


//User Routes
Route::get('/user', [UserController::class, 'show'])->middleware('auth:sanctum');
Route::put('/user', [UserController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/user', [UserController::class, 'destroy'])->middleware('auth:sanctum');

//Cart Product Routes
Route::group(['middleware' => 'auth:sanctum', 'controller' => PaymentController::class], function () {
    Route::post('/user/payment', 'store');
    Route::get('/user/payment', 'index');
    Route::put('/user/payment/{payment}', 'update');
    Route::delete('/user/payment/{payment}', 'destroy');
});

//Cart-Product Controller
Route::group(['controller' => CartProductController::class, 'middleware' => 'auth:sanctum'], function () {
    Route::post('/cart/products/{product}', 'store');
    Route::put('/cart/products/{product}', 'update');
    Route::get('/cart/products/', 'index');
    Route::delete('/cart/products/{product}', 'destroy');
    Route::delete('/cart/products/', 'clear');
});

// Order Routes
Route::group(['controller' => OrderController::class, 'middleware' => 'auth:sanctum'], function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/history', [OrderController::class, 'history']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{orderId}/products/{productId}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    Route::put('/orders/updateStatus/{id}', [OrderController::class, 'updateStatus']);
});
