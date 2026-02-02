<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Command;
use App\Services\MqttService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    protected $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    public function index(Request $request)
    {
        $dealerId = $request->user()->dealer_id;

        $devices = Device::with('deviceType')
            ->where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->get()
            ->map(function ($device) {
                $device->state = $this->mqttService->getDeviceState($device);
                return $device;
            });

        return response()->json([
            'success' => true,
            'data' => $devices,
        ]);
    }

    public function show(Request $request, $id)
    {
        $dealerId = $request->user()->dealer_id;

        $device = Device::with(['deviceType', 'logs' => function ($query) {
            $query->latest()->limit(10);
        }])
            ->where('dealer_id', $dealerId)
            ->findOrFail($id);

        $device->state = $this->mqttService->getDeviceState($device);

        return response()->json([
            'success' => true,
            'data' => $device,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_type_id' => 'required|exists:device_types,id',
            'name' => 'required|string|max:255',
            'mac_address' => 'required|string|unique:devices,mac_address',
        ]);

        $device = Device::create([
            'dealer_id' => $request->user()->dealer_id,
            'device_type_id' => $request->device_type_id,
            'name' => $request->name,
            'mac_address' => $request->mac_address,
            'mqtt_topic' => $request->mqtt_topic,
            'settings' => $request->settings,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cihaz eklendi',
            'data' => $device,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $dealerId = $request->user()->dealer_id;

        $device = Device::where('dealer_id', $dealerId)->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'mac_address' => 'sometimes|string|unique:devices,mac_address,' . $device->id,
        ]);

        $device->update($request->only(['name', 'mac_address', 'mqtt_topic', 'settings']));

        return response()->json([
            'success' => true,
            'message' => 'Cihaz güncellendi',
            'data' => $device,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $dealerId = $request->user()->dealer_id;

        $device = Device::where('dealer_id', $dealerId)->findOrFail($id);
        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cihaz silindi',
        ]);
    }

    public function state(Request $request, $id)
    {
        $dealerId = $request->user()->dealer_id;

        $device = Device::where('dealer_id', $dealerId)->findOrFail($id);
        $state = $this->mqttService->getDeviceState($device);

        return response()->json([
            'success' => true,
            'data' => $state,
        ]);
    }

    public function sendCommand(Request $request, $id)
    {
        $request->validate([
            'command_slug' => 'required|string|exists:commands,slug',
            'params' => 'sometimes|array',
        ]);

        $dealerId = $request->user()->dealer_id;
        $device = Device::where('dealer_id', $dealerId)->findOrFail($id);

        $log = $this->mqttService->sendCommandBySlug(
            $device,
            $request->command_slug,
            $request->params ?? [],
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Komut gönderildi',
            'data' => [
                'log_id' => $log->id,
                'command' => $request->command_slug,
                'payload' => $log->request_payload,
            ],
        ]);
    }
}
