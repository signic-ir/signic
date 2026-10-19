<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Exhibition\Http\Controllers\ExhibitorController;
use App\Modules\Exhibition\Http\Controllers\LeadController;

Route::middleware(['auth:sanctum'])->prefix('exhibition')->name('exhibition.')->group(function () {
    // Exhibitors
    Route::get('/exhibitors', [ExhibitorController::class, 'index'])->name('exhibitors.index');

    // Leads
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
});
