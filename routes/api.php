<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WishListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::get('products', [ProductController::class, 'getProducts']);
Route::get('products/{id}', [ProductController::class, 'getSingleProduct']);
Route::get('categories', [CategoryController::class, 'getCategories']);

Route::group(['prefix' => 'admin'], function () {
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::group(['middleware' => 'admin'], function () {
            Route::get('users', [AuthController::class, 'getUsers']);
            Route::post('users/add', [AuthController::class, 'addUser']);
            Route::post('users/update', [AuthController::class, 'updateUser']);
            Route::delete('users/delete/{id}', [AuthController::class, 'deleteUser']);

            Route::post('categories/add', [CategoryController::class, 'addCategory']);
            Route::delete('categories/delete/{id}', [CategoryController::class, 'deleteCategory']);

            Route::post('products/add', [ProductController::class, 'addProduct']);
            Route::post('products/update', [ProductController::class, 'updateProduct']);
            Route::delete('products/delete/{id}', [ProductController::class, 'deleteProduct']);
            Route::delete('products/{productId}/images/{imageId}', [ProductController::class, 'deleteProductImage']);

            Route::get('orders', [OrderController::class, 'getAdminOrders']);
            Route::get('orders/{id}', [OrderController::class, 'getAdminOrder']);
            Route::post('orders/update', [OrderController::class, 'updateAdminOrder']);

            Route::get('coupon', [CouponController::class, 'getCoupons']);
            Route::post('coupon/add', [CouponController::class, 'addCoupon']);
            Route::post('coupon/update', [CouponController::class, 'updateCoupon']);
            Route::delete('coupon/delete/{id}', [CouponController::class, 'deleteCoupon']);
        });
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::get('cart', [CartController::class, 'getCart']);
    Route::post('cart/add', [CartController::class, 'addToCart']);
    Route::post('cart/update', [CartController::class, 'updateCart']);
    Route::delete('cart/delete/{id}', [CartController::class, 'deleteCart']);

    Route::get('wishlist', [WishListController::class, 'getWishList']);
    Route::post('wishlist/add', [WishListController::class, 'addToWishList']);
    Route::delete('wishlist/delete/{id}', [WishListController::class, 'deleteWishList']);

    Route::post('verify-coupon', [CouponController::class, 'verifyCoupon']);

    Route::get('orders', [OrderController::class, 'getOrder']);
    Route::post('orders/add', [OrderController::class, 'addOrder']);

    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
});
