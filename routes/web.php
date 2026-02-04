<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\RoomController;
use App\Services\MqttService;
use App\Models\Device;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test/mqtt/{command}', function ($command, MqttService $mqttService) {
    $device = Device::first();

    if (!$device) {
        return 'Cihaz bulunamadı!';
    }

    $params = [];

    if ($command === 'brightness') {
        $params['brightness'] = request('value', 50);
    }

    $log = $mqttService->sendCommandBySlug($device, $command, $params);

    return [
        'success'   => true,
        'message'   => 'Komut gönderildi',
        'command'   => $command,
        'device'    => $device->name,
        'log_id'    => $log->id,
        'payload'   => $log->request_payload
    ];
});

Route::middleware('guest:dealer')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:dealer')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    //Devices
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
    Route::put('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
    Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

    // Rooms
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
});
