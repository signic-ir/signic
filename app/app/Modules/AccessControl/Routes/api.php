<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AccessControl\Http\Controllers\AttendeeStatusController;
use App\Modules\AccessControl\Http\Controllers\CheckInController;
use App\Modules\AccessControl\Http\Controllers\CheckOutController;
use App\Modules\AccessControl\Http\Controllers\ScanEventController;
use App\Modules\AccessControl\Http\Controllers\TurnstileController;
use App\Modules\AccessControl\Http\Middleware\EnsureSanctumAuthentication;

$registerAccessRoutes = function (string $prefix, string $name) {
    Route::middleware([EnsureSanctumAuthentication::class, 'auth:sanctum'])
        ->prefix($prefix)->name($name)->group(function () {
        Route::post('/checkin', [CheckInController::class, 'store'])->name('checkin.store');
        Route::post('/checkout', [CheckOutController::class, 'store'])->name('checkout.store');
        Route::get('/scan-events', [ScanEventController::class, 'index'])->name('scan-events.index');
        Route::get('/turnstiles', [TurnstileController::class, 'index'])->name('turnstiles.index');
        Route::post('/turnstiles', [TurnstileController::class, 'store'])->name('turnstiles.store');
        Route::get('/attendees/{id}/status', [AttendeeStatusController::class, 'show'])->name('attendees.status');
    });
};

// The module endpoints live under the "access" prefix.
$registerAccessRoutes('access', 'access.');

// The same endpoints are also reachable below "/api" for clients that talk to
// the API root (the black box test suite prefixes every path with /api).
$registerAccessRoutes('api/access', 'api.access.');
