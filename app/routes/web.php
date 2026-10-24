<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('LandingPage');
});

Route::get('/qr-guide', function () {
    return Inertia::render('QrGuidePage');
});

Route::get('/postal-card/{attendeeId}', function ($attendeeId) {
    return Inertia::render('PostalCardPage', ['attendeeId' => $attendeeId]);
});

Route::get('/dashboard', function () {
    return Inertia::render('DashboardPage');
})->name('dashboard');

Route::get('/login', function () {
    return Inertia::render('LoginPage');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('RegisterPage');
})->name('register');

Route::get('/qr', function () {
    return Inertia::render('AttendeeQRPage');
});

Route::get('/checkin', function () {
    return Inertia::render('CheckInPage');
});

Route::get('/exhibitors', function () {
    return Inertia::render('ExhibitorPage');
});
