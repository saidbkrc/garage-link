<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\CommandController;
use App\Http\Controllers\Api\V1\DeviceTypeController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\GatewayController;
use App\Http\Controllers\SceneController;

// V1 API Routes
Route::prefix('v1')->group(function () {

    // Auth (Public) — brute-force koruması için rate-limit
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    // Protected Routes — web session (dealer guard) ile çalışır
    Route::middleware('auth:dealer,sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Devices
        Route::get('/devices/pending', [DeviceController::class, 'pending']);
        Route::get('/devices/states',  [DeviceController::class, 'states']);   // tüm state'leri toplu döner
        Route::post('/devices/sync',   [DeviceController::class, 'sync']);     // tüm cihazlara get_state gönder
        Route::get('/devices', [DeviceController::class, 'index']);
        Route::get('/devices/{id}', [DeviceController::class, 'show']);
        Route::post('/devices', [DeviceController::class, 'store']);
        Route::put('/devices/{id}', [DeviceController::class, 'update']);
        Route::delete('/devices/{id}', [DeviceController::class, 'destroy']);
        Route::get('/devices/{id}/state', [DeviceController::class, 'state']);
        Route::post('/devices/{id}/command', [DeviceController::class, 'sendCommand']);

        // Commands
        Route::get('/commands', [CommandController::class, 'index']);
        Route::post('/commands/group', [CommandController::class, 'sendGroupCommand']);

        // Device Types
        Route::get('/device-types', [DeviceTypeController::class, 'index']);
        Route::get('/device-types/{id}', [DeviceTypeController::class, 'show']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'index']);

        // Scenes (API — Dashboard'dan fetch ile çağrılır)
        Route::post('/scenes/{scene}/run', [SceneController::class, 'run']);

        // Gateways
        Route::get('/gateways', [GatewayController::class, 'index']);
        Route::post('/gateways/scan-all', [GatewayController::class, 'scanAll']);
        Route::post('/gateways/{gatewayId}/claim', [GatewayController::class, 'claim']);
        Route::post('/gateways/{gatewayId}/scan', [GatewayController::class, 'scan']);
        Route::get('/gateways/{gatewayId}/devices', [GatewayController::class, 'devices']);
        Route::post('/gateways/{gatewayId}/devices/configure', [GatewayController::class, 'configureDevices']);
    });
});
