<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Registration\Http\Controllers\AttendeeController;

Route::middleware(['auth:sanctum'])->prefix('registration')->name('registration.')->group(function () {
    Route::post('/attendees', [AttendeeController::class, 'store'])->name('attendees.store');
    Route::get('/attendees/{id}/qr', [AttendeeController::class, 'qr'])->name('attendees.qr');
    Route::middleware('auth.basic')->prefix('api/registration')->group(function () {
        Route::post('/attendees', [AttendeeController::class, 'store']);
        Route::get('/attendees/{id}/qr', [AttendeeController::class, 'qr']);
    });
});