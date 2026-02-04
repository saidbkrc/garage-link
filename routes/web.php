<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
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

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
