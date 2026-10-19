<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
});

Route::get('/login', function () {
    return Inertia::render('LoginPage');
});

Route::get('/register', function () {
    return Inertia::render('RegisterPage');
});

Route::get('/qr', function () {
    return Inertia::render('AttendeeQRPage');
});

Route::get('/checkin', function () {
    return Inertia::render('CheckInPage');
});

Route::get('/exhibitors', function () {
    return Inertia::render('ExhibitorPage');
});
