<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Gateway;
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

        // İstatistikler (sadece aktif cihazlar)
        $stats = [
            'total_devices' => Device::where('dealer_id', $dealerId)->where('is_active', true)->count(),
            'online_devices' => Device::where('dealer_id', $dealerId)->where('is_active', true)->where('is_online', true)->count(),
            'total_energy_today' => Device::where('dealer_id', $dealerId)->sum('energy_today'),
            'active_alerts' => Alert::where('dealer_id', $dealerId)->where('is_read', false)->count(),
        ];

        // Dashboard'da gösterilecek cihazlar (show_in_dashboard=true); eğer yoksa ilk 8 aktif cihaz
        $dashboardQuery = Device::with(['deviceType', 'room'])
            ->where('dealer_id', $dealerId)
            ->where('is_active', true);

        $hasPinned = (clone $dashboardQuery)->where('show_in_dashboard', true)->exists();

        $devices = ($hasPinned
            ? (clone $dashboardQuery)->where('show_in_dashboard', true)
            : (clone $dashboardQuery)->limit(8)
        )->get()
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

        // Sahiplenilmemiş online gateway'ler (banner için)
        $unclaimedGatewayCount = Gateway::whereNull('dealer_id')
            ->where('is_online', true)
            ->count();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'devices' => $devices,
            'rooms' => $rooms,
            'scenes' => $scenes,
            'alerts' => $alerts,
            'unclaimed_gateway_count' => $unclaimedGatewayCount,
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
