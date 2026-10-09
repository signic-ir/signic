<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Module routes are loaded via each module's ServiceProvider.
| This file serves as the base API route file.
|
*/

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'signic']);
})->name('health');
