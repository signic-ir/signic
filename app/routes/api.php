<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

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
    // Probe PostgreSQL connectivity. `getPdo()` opens a real PDO handle, so a
    // failure here means the database is genuinely unreachable.
    $dbOk = false;
    try {
        DB::connection()->getPdo();
        $dbOk = true;
    } catch (\Throwable $e) {
        $dbOk = false;
    }

    $redisOk = Redis::ping() === 'PONG';

    if (!$dbOk || !$redisOk) {
        return response()->json([
            'status' => 'degraded',
            'service' => 'signic',
            'checks' => [
                'database' => $db === false ? 'down' : 'up',
                'redis' => $redisPing === 'PONG' ? 'up' : 'down',
            ],
        ], 503);
    }

    return response()->json([
        'status' => 'ok',
        'service' => 'signic',
    ]);
})->name('health');
