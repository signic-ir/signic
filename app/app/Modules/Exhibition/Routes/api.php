<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Exhibition\Http\Controllers\BoothController;
use App\Modules\Exhibition\Http\Controllers\ExhibitorController;
use App\Modules\Exhibition\Http\Controllers\LeadController;

/*
|--------------------------------------------------------------------------
| Exhibition module routes
|--------------------------------------------------------------------------
|
| The module is mounted on its own prefix. The same routes are also exposed
| below /api so clients that prefix every endpoint with /api keep working.
|
*/

$exhibitionRoutes = function (string $prefix, string $name) {
    Route::middleware(['auth:sanctum'])->prefix($prefix)->name($name)->group(function () {
        // Exhibitors
        Route::get('/exhibitors', [ExhibitorController::class, 'index'])->name('exhibitors.index');
        Route::post('/exhibitors', [ExhibitorController::class, 'store'])->name('exhibitors.store');
        Route::get('/exhibitors/{exhibitor}', [ExhibitorController::class, 'show'])->name('exhibitors.show');
        Route::post('/exhibitors/{exhibitor}/members', [ExhibitorController::class, 'addMember'])->name('exhibitors.members.store');

        // Booths
        Route::get('/booths', [BoothController::class, 'index'])->name('booths.index');
        Route::post('/booths', [BoothController::class, 'store'])->name('booths.store');
        Route::get('/booths/{booth}', [BoothController::class, 'show'])->name('booths.show');

        // Leads
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
        Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::match(['put', 'patch'], '/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::get('/leads/{lead}/history', [LeadController::class, 'history'])->name('leads.history');
        Route::post('/leads/{lead}/tags', [LeadController::class, 'addTag'])->name('leads.tags.store');
        Route::delete('/leads/{lead}/tags/{tag}', [LeadController::class, 'removeTag'])->name('leads.tags.destroy');
    });
};

$exhibitionRoutes('exhibition', 'exhibition.');
$exhibitionRoutes('api/exhibition', 'exhibition.api.');
