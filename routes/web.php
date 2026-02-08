<?php

use Illuminate\Support\Facades\Route;

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

// Debug route to check environment variables
Route::get('/debug/env', function () {
    return response()->json([
        'app_url' => config('app.url'),
        'frontend_url' => env('FRONTEND_URL'),
        'session_domain' => config('session.domain'),
        'session_secure' => config('session.secure'),
        'session_same_site' => config('session.same_site'),
        'google_redirect' => config('services.google.redirect'),
        'google_client_id_set' => ! empty(config('services.google.client_id')),
    ]);
});

// Google OAuth Routes - Explicitly define here for web middleware
Route::get('auth/google', [\App\Http\Controllers\AuthController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [\App\Http\Controllers\AuthController::class, 'handleGoogleCallback']);
