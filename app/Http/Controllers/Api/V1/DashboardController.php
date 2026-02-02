<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $dealerId = $request->user()->dealer_id;

        $totalDevices = Device::where('dealer_id', $dealerId)->count();
        $onlineDevices = Device::where('dealer_id', $dealerId)->where('is_online', true)->count();
        $activeDevices = Device::where('dealer_id', $dealerId)->where('is_active', true)->count();

        $recentLogs = DeviceLog::whereHas('device', function ($query) use ($dealerId) {
            $query->where('dealer_id', $dealerId);
        })
            ->with(['device:id,name', 'command:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_devices' => $totalDevices,
                'online_devices' => $onlineDevices,
                'active_devices' => $activeDevices,
                'recent_logs' => $recentLogs,
            ],
        ]);
    }
}
