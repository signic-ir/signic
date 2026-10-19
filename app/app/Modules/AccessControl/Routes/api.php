<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AccessControl\Http\Controllers\CheckInController;
use App\Modules\AccessControl\Http\Controllers\CheckOutController;

Route::middleware(['auth:sanctum'])->prefix('access')->name('access.')->group(function () {
    Route::post('/checkin', [CheckInController::class, 'store'])->name('checkin.store');
    Route::post('/checkout', [CheckOutController::class, 'store'])->name('checkout.store');
});