<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Room;
use App\Models\Scene;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::guard('dealer')->user();
        $dealerId = $user->dealer_id;

        // İstatistikler
        $stats = [
            'total_devices' => Device::where('dealer_id', $dealerId)->count(),
            'online_devices' => Device::where('dealer_id', $dealerId)->where('is_online', true)->count(),
            'total_energy_today' => Device::where('dealer_id', $dealerId)->sum('energy_today'),
            'active_alerts' => Alert::where('dealer_id', $dealerId)->where('is_read', false)->count(),
        ];

        // Cihazlar
        $devices = Device::with(['deviceType', 'room'])
            ->where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'type' => $device->deviceType?->slug,
                    'category' => $device->deviceType?->category,
                    'icon' => $device->deviceType?->icon,
                    'room' => $device->room?->name,
                    'room_id' => $device->room_id,
                    'is_online' => $device->is_online,
                    'state' => $device->current_state ?? [],
                    'config' => $device->config ?? [],
                    'energy_today' => $device->energy_today,
                ];
            });

        // Odalar
        $rooms = Room::where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        // Senaryolar
        $scenes = Scene::where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->get();

        // Alarmlar
        $alerts = Alert::where('dealer_id', $dealerId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'devices' => $devices,
            'rooms' => $rooms,
            'scenes' => $scenes,
            'alerts' => $alerts,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'dealer_id' => $user->dealer_id,
                'dealer_name' => $user->dealer->name,
            ],
        ]);
    }
}
