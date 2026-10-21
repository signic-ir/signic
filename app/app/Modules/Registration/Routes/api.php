<?php

use App\Modules\Registration\Http\Controllers\AttendeeController;
use App\Modules\Registration\Http\Controllers\EventController;
use App\Modules\Registration\Http\Controllers\QRController;
use App\Modules\Registration\Http\Middleware\CanCreateAttendee;
use App\Modules\Registration\Http\Middleware\CheckAttendeeDuplicate;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Registration routes
|--------------------------------------------------------------------------
|
| The module is reachable under "registration" and under "api/registration"
| so that the API prefix used by the other modules and the prefix of this
| module both resolve to the same endpoints.
|
*/

$registrationRoutes = function (): void {
    Route::post('/attendees', [AttendeeController::class, 'store'])
        ->middleware([CheckAttendeeDuplicate::class, CanCreateAttendee::class])
        ->name('attendees.store');
    Route::get('/attendees', [AttendeeController::class, 'index'])->name('attendees.index');
    Route::get('/attendees/{id}', [AttendeeController::class, 'show'])
        ->whereNumber('id')
        ->name('attendees.show');
    Route::get('/attendees/{id}/qr', [AttendeeController::class, 'qr'])
        ->whereNumber('id')
        ->name('attendees.qr');
    Route::post('/attendees/{id}/qr/expire', [AttendeeController::class, 'expireQr'])
        ->whereNumber('id')
        ->name('attendees.qr.expire');

    Route::post('/qr/validate', [QRController::class, 'validate'])->name('qr.validate');

    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/report', [EventController::class, 'report'])->name('events.report');
    Route::get('/events/{id}', [EventController::class, 'show'])
        ->whereNumber('id')
        ->name('events.show');
    Route::get('/events/{id}/report', [EventController::class, 'report'])
        ->whereNumber('id')
        ->name('events.report.show');
    Route::post('/events/cleanup', [EventController::class, 'cleanup'])->name('events.cleanup');

    Route::post('/cleanup-expired-events', [EventController::class, 'cleanup'])
        ->name('cleanup.expired_events');
};

Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('registration')
    ->name('registration.')
    ->group($registrationRoutes);

Route::middleware(['auth:sanctum', 'throttle:api'])
    ->prefix('api/registration')
    ->name('registration.api.')
    ->group($registrationRoutes);
