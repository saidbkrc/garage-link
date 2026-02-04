<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RoomController extends Controller
{
    /**
     * Oda listesi sayfası
     */
    public function index()
    {
        $user = Auth::guard('dealer')->user();
        $dealerId = $user->dealer_id;

        $rooms = Room::where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->withCount(['devices', 'devices as online_count' => function ($query) {
                $query->where('is_online', true);
            }])
            ->orderBy('order')
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'name' => $room->name,
                    'icon' => $room->icon,
                    'color' => $room->color ?? 'blue',
                    'order' => $room->order,
                    'device_count' => $room->devices_count,
                    'online_count' => $room->online_count,
                ];
            });

        $devices = Device::with('deviceType')
            ->where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'room_id' => $device->room_id,
                    'icon' => $device->deviceType?->icon,
                    'is_online' => $device->is_online,
                ];
            });

        return Inertia::render('Rooms/Index', [
            'rooms' => $rooms,
            'devices' => $devices,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'dealer_name' => $user->dealer->name,
            ],
        ]);
    }

    /**
     * Yeni oda ekle
     */
    public function store(Request $request)
    {
        $user = Auth::guard('dealer')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ], [
            'name.required' => 'Oda adı gerekli',
        ]);

        // Get max order
        $maxOrder = Room::where('dealer_id', $user->dealer_id)->max('order') ?? 0;

        Room::create([
            'dealer_id' => $user->dealer_id,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? 'meeting_room',
            'color' => $validated['color'] ?? 'blue',
            'order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Oda başarıyla eklendi');
    }

    /**
     * Oda güncelle
     */
    public function update(Request $request, Room $room)
    {
        $user = Auth::guard('dealer')->user();

        // Yetki kontrolü
        if ($room->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $room->update($validated);

        return redirect()->back()->with('success', 'Oda güncellendi');
    }

    /**
     * Oda sil
     */
    public function destroy(Room $room)
    {
        $user = Auth::guard('dealer')->user();

        // Yetki kontrolü
        if ($room->dealer_id !== $user->dealer_id) {
            abort(403);
        }

        // Odadaki cihazları "atanmamış" yap
        Device::where('room_id', $room->id)->update(['room_id' => null]);

        $room->delete();

        return redirect()->back()->with('success', 'Oda silindi');
    }
}
