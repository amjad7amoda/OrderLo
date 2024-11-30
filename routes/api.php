<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



//Authentication Routes:
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::apiResource('stores', StoreController::class);
Route::apiResource('stores.products', ProductController::class);
//Route::apiResource('products.images', ImageController::class);



// user Routes
Route::get('/user', function (Request $request){return $request->user();})->middleware('auth:sanctum');
Route::put( '/user', [UserController::class,'update'])->middleware('auth:sanctum');
Route::delete('/user', [UserController::class,'destroy'])->middleware('auth:sanctum');


//Route::apiResource('user.payment', PaymentController::class);
Route::post('/user/payment', [PaymentController::class,'store'])->middleware('auth:sanctum');
Route::get('/user/payment', [PaymentController::class,'index'])->middleware('auth:sanctum');
Route::delete('/user/payment/{payment}', [PaymentController::class,'destroy'])->middleware('auth:sanctum');
