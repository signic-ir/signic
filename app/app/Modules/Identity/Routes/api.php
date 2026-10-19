<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Identity\Http\Controllers\OTPController;

Route::prefix('api/identity')->name('identity.api.')->group(function () {
    Route::post('/otp/request', [OTPController::class, 'request'])
        ->middleware(['throttle:5,1'])
        ->name('otp.request');

    Route::post('/otp/verify', [OTPController::class, 'verify'])
        ->middleware(['throttle:10,1'])
        ->name('otp.verify');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/me', [OTPController::class, 'profile'])
            ->name('profile');

        Route::post('/logout', [OTPController::class, 'logout'])
            ->name('logout');
    });
});