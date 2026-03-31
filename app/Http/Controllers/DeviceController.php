<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DeviceController extends Controller
{
    /**
     * Cihaz listesi sayfası
     */
    public function index()
    {
        $user = Auth::guard('dealer')->user();
        $dealerId = $user->dealer_id;

        $devices = Device::with(['deviceType', 'room'])
            ->where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($device) {
                return [
                    'id'                 => $device->id,
                    'name'               => $device->name,
                    'type'               => $device->deviceType?->slug,
                    'category'           => $device->deviceType?->category,
                    'icon'               => $device->deviceType?->icon,
                    'device_type_id'     => $device->device_type_id,
                    'room'               => $device->room?->name,
                    'room_id'            => $device->room_id,
                    'is_online'          => $device->is_online,
                    'is_active'          => $device->is_active,
                    'show_in_dashboard'  => $device->show_in_dashboard,
                    'state'              => $device->current_state ?? [],
                    'config'             => $device->config ?? [],
                    'mac_address'        => $device->mac_address,
                    'ieee_addr'          => $device->ieee_addr,
                    'last_seen_at'       => $device->last_seen_at,
                ];
            });

        $rooms = Room::where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $deviceTypes = DeviceType::where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Devices/Index', [
            'devices'     => $devices,
            'rooms'       => $rooms,
            'deviceTypes' => $deviceTypes,
            'initialRoom' => request()->query('room', ''),
            'user'        => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'dealer_name' => $user->dealer->name,
            ],
        ]);
    }

    /**
     * Yeni cihaz ekle
     */
    public function store(Request $request)
    {
        $user = Auth::guard('dealer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_type_id' => 'required|exists:device_types,id',
            'room_id' => 'nullable|exists:rooms,id',
            'mac_address' => 'required|string|unique:devices,mac_address',
            'mqtt_topic' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Cihaz adı gerekli',
            'device_type_id.required' => 'Cihaz tipi seçin',
            'mac_address.required' => 'MAC adresi gerekli',
            'mac_address.unique' => 'Bu MAC adresi zaten kayıtlı',
        ]);

        $device = Device::create([
            'dealer_id' => $user->dealer_id,
            'device_type_id' => $validated['device_type_id'],
            'room_id' => $validated['room_id'],
            'name' => $validated['name'],
            'mac_address' => strtoupper($validated['mac_address']),
            'mqtt_topic' => $validated['mqtt_topic'],
            'is_online' => false,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Cihaz başarıyla eklendi');
    }

    /**
     * Cihaz güncelle
     */
    public function update(Request $request, Device $device)
    {
        $user = Auth::guard('dealer')->user();

        // Yetki kontrolü
        if ($device->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        $request->validate([
            'name'               => 'sometimes|string|max:255',
            'room_id'            => 'nullable|exists:rooms,id',
            'device_type_id'     => 'nullable|exists:device_types,id',
            'is_active'          => 'sometimes|boolean',
            'show_in_dashboard'  => 'sometimes|boolean',
            'channel_count'      => 'nullable|integer|min:1|max:64',
        ]);

        $updateData = $request->only([
            'name', 'room_id', 'device_type_id', 'is_active', 'show_in_dashboard',
        ]);

        // Kanal sayısı gönderildiyse config'e yaz
        if ($request->filled('channel_count')) {
            $config = $device->config ?? [];
            $config['channel_count'] = (int) $request->channel_count;
            // onoff_endpoints yoksa sıralı üret
            if (empty($config['onoff_endpoints'])) {
                $config['onoff_endpoints'] = range(1, (int) $request->channel_count);
            }
            $updateData['config'] = $config;
        }

        $device->update($updateData);

        return redirect()->back()->with('success', 'Cihaz güncellendi');
    }

    /**
     * Cihaz sil
     */
    public function destroy(Device $device)
    {
        $user = Auth::guard('dealer')->user();

        // Yetki kontrolü
        if ($device->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        $device->delete();

        return redirect()->back()->with('success', 'Cihaz silindi');
    }
}
