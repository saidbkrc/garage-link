<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\CommandController;
use App\Http\Controllers\Api\V1\DeviceTypeController;
use App\Http\Controllers\Api\V1\DashboardController;

// V1 API Routes
Route::prefix('v1')->group(function () {

    // Auth (Public)
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Devices
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
    });
});
