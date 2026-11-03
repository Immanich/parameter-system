<?php

use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\TripController;
use App\Http\Middleware\EnsureDriver;
use Illuminate\Support\Facades\Route;

Route::prefix('drivers')->middleware('throttle:10,1')->group(function () {
    Route::post('register', [DriverAuthController::class, 'register']);
    Route::post('login', [DriverAuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', EnsureDriver::class])->group(function () {
    Route::get('drivers/me', [DriverAuthController::class, 'me']);
    Route::post('drivers/logout', [DriverAuthController::class, 'logout']);
    Route::post('trips', [TripController::class, 'store']);
});
