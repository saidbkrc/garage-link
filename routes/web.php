<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SceneController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Services\MqttService;
use App\Models\Device;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;

// Geliştirme amaçlı MQTT test ucu — yalnızca local ortamda ve giriş yapmış bayi için.
// Prod'da bu route hiç kayıt olmaz.
if (App::environment('local')) {
    Route::middleware('auth:dealer')->get('/test/mqtt/{command}', function ($command, MqttService $mqttService) {
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
}

Route::middleware('guest:dealer')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});

Route::middleware('auth:dealer')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Gateways
    Route::get('/gateways', [GatewayController::class, 'index'])->name('gateways.index');

    // Canlı MQTT izleyici — tarayıcı doğrudan broker'a (WebSocket) bağlanır
    Route::get('/mqtt-monitor', function () {
        return Inertia::render('MqttMonitor', [
            'mqttHost'    => config('mqtt-client.connections.default.host'),
            'topicPrefix' => 'pigasoft/#',
        ]);
    })->name('mqtt-monitor');

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

    // Scenes
    Route::get('/scenes', [SceneController::class, 'index'])->name('scenes.index');
    Route::post('/scenes', [SceneController::class, 'store'])->name('scenes.store');
    Route::put('/scenes/{scene}', [SceneController::class, 'update'])->name('scenes.update');
    Route::delete('/scenes/{scene}', [SceneController::class, 'destroy'])->name('scenes.destroy');
    Route::post('/scenes/{scene}/run', [SceneController::class, 'run'])->name('scenes.run');

    // Schedules
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

    // Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    // Alerts
    Route::post('/alerts/{alert}/read', [AlertController::class, 'markRead'])->name('alerts.read');
    Route::post('/alerts/read-all', [AlertController::class, 'markAllRead'])->name('alerts.read-all');
});
