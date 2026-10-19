<?php

use Illuminate\Support\Facades\Route;

// Shared module routes — utility endpoints
Route::middleware(['auth:sanctum'])->prefix('shared')->name('shared.')->group(function () {
    // Reserved for cross-module shared endpoints
});
