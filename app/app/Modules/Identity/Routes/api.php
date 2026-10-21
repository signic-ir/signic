<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Identity\Http\Controllers\OTPController;

Route::prefix('api/identity')->name('identity.api.')->group(function () {
    Route::post('/otp/request', [OTPController::class, 'request'])
        ->middleware(['throttle:otp'])
        ->name('otp.request');

    Route::post('/otp/verify', [OTPController::class, 'verify'])
        ->middleware(['throttle:otp'])
        ->name('otp.verify');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/me', [OTPController::class, 'profile'])
            ->name('profile');

        Route::post('/logout', [OTPController::class, 'logout'])
            ->name('logout');
    });
});