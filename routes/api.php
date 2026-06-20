<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\DeliveryChargeController;
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

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');

// Google OAuth routes need session support
Route::group(['middleware' => ['web']], function () {
    Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::get('products', [ProductController::class, 'getProducts']);
Route::get('products/search', [ProductController::class, 'searchProducts']);
Route::get('products/{identifier}', [ProductController::class, 'getSingleProduct']);
Route::get('categories', [CategoryController::class, 'getCategories']);

Route::get('banners', [BannerController::class, 'getBanners']);

Route::get('delivery-charges', [DeliveryChargeController::class, 'getDeliveryCharges']);

Route::get('blogs', [BlogController::class, 'getBlogs']);
Route::get('blogs/{id}', [BlogController::class, 'getSingleBlog']);
Route::get('blog-categories', [BlogController::class, 'getBlogCategories']);
Route::get('blog-tags', [BlogController::class, 'getBlogTags']);

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
            Route::get('products/{slug}', [ProductController::class, 'getAdminProduct']);
            Route::delete('products/{productId}/images/{imageId}', [ProductController::class, 'deleteProductImage']);
            Route::post('products/{id}/variants', [ProductController::class, 'addVariant']);
            Route::post('variants/delete/{id}', [ProductController::class, 'deleteVariant']);
            Route::get('variants/{id}', [ProductController::class, 'getAdminVariant']);
            Route::match(['PUT', 'POST'], 'variants/{id}', [ProductController::class, 'updateVariant']);
            Route::post('variants/{id}/images', [ProductController::class, 'addVariantImage']);
            Route::delete('variants/{variantId}/images/{imageId}', [ProductController::class, 'deleteVariantImage']);

            Route::get('orders', [OrderController::class, 'getAdminOrders']);
            Route::get('orders/{id}', [OrderController::class, 'getAdminOrder']);
            Route::post('orders/update', [OrderController::class, 'updateAdminOrder']);

            Route::get('coupon', [CouponController::class, 'getCoupons']);
            Route::post('coupon/add', [CouponController::class, 'addCoupon']);
            Route::post('coupon/update', [CouponController::class, 'updateCoupon']);
            Route::delete('coupon/delete/{id}', [CouponController::class, 'deleteCoupon']);

            Route::get('blogs', [BlogController::class, 'getBlogs']);
            Route::post('blogs/add', [BlogController::class, 'addBlog']);
            Route::post('blogs/update', [BlogController::class, 'updateBlog']);
            Route::delete('blogs/delete/{id}', [BlogController::class, 'deleteBlog']);

            Route::post('blog-categories/add', [BlogController::class, 'addBlogCategory']);
            Route::post('blog-categories/update', [BlogController::class, 'updateBlogCategory']);
            Route::delete('blog-categories/delete/{id}', [BlogController::class, 'deleteBlogCategory']);

            Route::post('blog-tags/add', [BlogController::class, 'addBlogTag']);
            Route::post('blog-tags/update', [BlogController::class, 'updateBlogTag']);
            Route::delete('blog-tags/delete/{id}', [BlogController::class, 'deleteBlogTag']);

            Route::get('banners', [BannerController::class, 'getBanners']);
            Route::post('banners/add', [BannerController::class, 'addBanner']);
            Route::post('banners/update', [BannerController::class, 'updateBanner']);
            Route::delete('banners/delete/{id}', [BannerController::class, 'deleteBanner']);

            Route::get('delivery-charges', [DeliveryChargeController::class, 'getDeliveryCharges']);
            Route::post('delivery-charges/add', [DeliveryChargeController::class, 'addDeliveryCharge']);
            Route::post('delivery-charges/update', [DeliveryChargeController::class, 'updateDeliveryCharge']);
            Route::delete('delivery-charges/delete/{id}', [DeliveryChargeController::class, 'deleteDeliveryCharge']);
        });
    });
});

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::get('cart', [CartController::class, 'getCart']);
    Route::post('cart/add', [CartController::class, 'addToCart'])->middleware('throttle:cart');
    Route::post('cart/update', [CartController::class, 'updateCart'])->middleware('throttle:cart');
    Route::delete('cart/delete/{id}', [CartController::class, 'deleteCart']);
    Route::post('cart/toggle-selection', [CartController::class, 'toggleSelection']);
    Route::post('cart/select-all', [CartController::class, 'selectAll']);
    Route::post('cart/deselect-all', [CartController::class, 'deselectAll']);

    Route::get('wishlist', [WishListController::class, 'getWishList']);
    Route::post('wishlist/add', [WishListController::class, 'addToWishList'])->middleware('throttle:sensitive');
    Route::delete('wishlist/delete/{id}', [WishListController::class, 'deleteWishList']);

    Route::post('verify-coupon', [CouponController::class, 'verifyCoupon'])->middleware('throttle:sensitive');

    Route::get('orders', [OrderController::class, 'getOrder']);
    Route::post('orders/add', [OrderController::class, 'addOrder'])->middleware('throttle:sensitive');
    Route::post('orders/direct', [OrderController::class, 'directOrder'])->middleware('throttle:sensitive');

    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
});
