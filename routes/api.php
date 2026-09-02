<?php

use App\Http\Controllers\Api\Mobile\AuthController as MobileAuthController;
use App\Http\Controllers\Api\Mobile\CustomerController as MobileCustomerController;
use App\Http\Controllers\Api\Mobile\DashboardController as MobileDashboardController;
use App\Http\Controllers\Api\Mobile\OrderController as MobileOrderController;
use App\Http\Controllers\Api\Mobile\ProductController as MobileProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->name('mobile.')->group(function (): void {
    Route::middleware('throttle:5,1')->group(function (): void {
        Route::post('auth/register', [MobileAuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [MobileAuthController::class, 'login'])->name('auth.login');
        Route::post('auth/forgot-password', [MobileAuthController::class, 'forgotPassword'])->name('auth.forgot-password');
        Route::post('auth/reset-password', [MobileAuthController::class, 'resetPassword'])->name('auth.reset-password');
    });

    Route::middleware(['mobile.auth', 'throttle:60,1'])->group(function (): void {
        Route::get('auth/me', [MobileAuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [MobileAuthController::class, 'logout'])->name('auth.logout');

        Route::get('dashboard', MobileDashboardController::class)->name('dashboard');

        Route::apiResource('customers', MobileCustomerController::class);
        Route::apiResource('products', MobileProductController::class);
        Route::apiResource('orders', MobileOrderController::class);
    });
});
