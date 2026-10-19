<?php

declare(strict_types=1);

use App\Modules\Shared\Http\Controllers\PermissionController;
use App\Modules\Shared\Http\Controllers\RoleController;
use App\Modules\Shared\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shared module routes
|--------------------------------------------------------------------------
|
| The role and permission system is exposed under the `shared` prefix. The
| very same surface is also mounted under `api/shared` so that clients
| addressing every module through the API prefix reach it as well.
|
| Every route is protected by the Sanctum guard. The `{role}`, `{user}` and
| `{permission}` segments accept a numeric id or the natural key of the
| record (role name, user id / phone number, permission name).
|
*/

$sharedRoutes = function (): void {
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::post('/roles/{role}/permissions', [RoleController::class, 'storePermission'])->name('roles.permissions.store');
    Route::delete('/roles/{role}/permissions/{permission}', [RoleController::class, 'destroyPermission'])->name('roles.permissions.destroy');
    Route::post('/roles/{role}/inherit', [RoleController::class, 'inherit'])->name('roles.inherit.store');
    Route::post('/roles/{role}/inherit-all', [RoleController::class, 'inheritAll'])->name('roles.inherit-all.store');

    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::get('/permissions/check', [PermissionController::class, 'check'])->name('permissions.check');

    Route::get('/users/{user}', [UserRoleController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/roles', [UserRoleController::class, 'index'])->name('users.roles.index');
    Route::post('/users/{user}/roles', [UserRoleController::class, 'store'])->name('users.roles.store');
    Route::delete('/users/{user}/roles/{role}', [UserRoleController::class, 'destroy'])->name('users.roles.destroy');
    Route::get('/users/{user}/permissions/{permission}', [UserRoleController::class, 'checkPermission'])->name('users.permissions.check');
};

Route::middleware(['auth:sanctum'])->prefix('shared')->name('shared.')->group($sharedRoutes);

Route::middleware(['auth:sanctum'])->prefix('api/shared')->name('shared.api.')->group($sharedRoutes);
