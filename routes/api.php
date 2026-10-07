<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "api" middleware group. Now create something great!
|
*/

// Identity Module API Routes
Route::prefix('identity')->group(function () {
    Route::post('/otp/send', [\App\Modules\Identity\Http\Controllers\OTPController::class, 'send'])
        ->middleware(['throttle:10,1']);
    Route::post('/otp/verify', [\App\Modules\Identity\Http\Controllers\OTPController::class, 'verify'])
        ->middleware(['throttle:5,1']);
});

// Registration Module API Routes
Route::prefix('registration')->group(function () {
    Route::post('/attendees', [\App\Modules\Registration\Http\Controllers\AttendeeController::class, 'store'])
        ->middleware(['auth:sanctum']);
    Route::get('/attendees/{id}/qr', [\App\Modules\Registration\Http\Controllers\AttendeeController::class, 'showQR'])
        ->middleware(['auth:sanctum']);
});

// Access Control Module API Routes
Route::prefix('access')->group(function () {
    Route::post('/checkin', [\App\Modules\AccessControl\Http\Controllers\CheckInController::class, 'store'])
        ->middleware(['auth:sanctum']);
    Route::post('/checkout', [\App\Modules\AccessControl\Http\Controllers\CheckOutController::class, 'store'])
        ->middleware(['auth:sanctum']);
});

// Exhibition Module API Routes
Route::prefix('exhibition')->group(function () {
    Route::get('/exhibitors', [\App\Modules\Exhibition\Http\Controllers\ExhibitorController::class, 'index'])
        ->middleware(['auth:sanctum']);
    Route::post('/leads', [\App\Modules\Exhibition\Http\Controllers\LeadController::class, 'store'])
        ->middleware(['auth:sanctum']);
});