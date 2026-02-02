<?php

use Illuminate\Support\Facades\Route;
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

Route::get('/panel', function () {
    $dealerId = 1;

    $devices = Device::with('deviceType')
        ->where('dealer_id', $dealerId)
        ->get()
        ->map(function ($device) {
            $mqttService = app(\App\Services\MqttService::class);
            $device->state = $mqttService->getDeviceState($device);
            return $device;
        });

    $stats = [
        'total_devices' => $devices->count(),
        'online_devices' => $devices->where('is_online', true)->count(),
        'active_devices' => $devices->where('is_active', true)->count(),
        'today_commands' => \App\Models\DeviceLog::whereDate('created_at', today())->count(),
    ];

    return Inertia::render('Dashboard', [
        'stats' => $stats,
        'devices' => $devices,
        'user' => [
            'name' => 'Test Kullanıcı',
            'role' => 'admin',
            'dealer_name' => 'Test Bayi',
            'token' => 'BURAYA_TOKEN_YAZIN',
        ],
    ]);
});
