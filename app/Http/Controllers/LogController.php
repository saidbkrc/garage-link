<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $user     = Auth::guard('dealer')->user();
        $dealerId = $user->dealer_id;

        // Dealer'a ait cihaz ID'leri
        $deviceIds = Device::where('dealer_id', $dealerId)->pluck('id');

        $query = DeviceLog::with(['device', 'command', 'dealerUser'])
            ->whereIn('device_id', $deviceIds)
            ->latest();

        // Filtreleme
        if ($request->filled('device_id')) {
            $query->where('device_id', $request->device_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate(50)->through(function ($log) {
            return [
                'id'              => $log->id,
                'device_name'     => $log->device?->name ?? 'Silinmiş Cihaz',
                'command_slug'    => $log->command?->slug ?? '—',
                'command_name'    => $log->command?->name ?? '—',
                'source'          => $log->source,
                'status'          => $log->status,
                'request_payload' => $log->request_payload,
                'user_name'       => $log->dealerUser?->name ?? 'Sistem',
                'created_at'      => $log->created_at?->toISOString(),
            ];
        });

        // Filtre için cihaz listesi
        $devices = Device::where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return Inertia::render('Logs/Index', [
            'logs'    => $logs,
            'devices' => $devices,
            'filters' => $request->only(['device_id', 'status']),
            'user'    => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'dealer_name' => $user->dealer->name,
            ],
        ]);
    }
}
