<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Command;
use App\Services\GatewayCommandBuilder;
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

    /**
     * Henüz aktifleştirilmemiş (is_active=false) ama gateway'e bağlı cihazları listeler.
     * "Cihaz Ekle" modalında kullanılır.
     */
    public function pending(Request $request)
    {
        $dealerId = $request->user()->dealer_id;

        $devices = Device::with(['deviceType', 'gateway'])
            ->where('dealer_id', $dealerId)
            ->where('is_active', false)
            ->whereNotNull('gateway_db_id')
            ->get()
            ->map(fn($d) => [
                'id'        => $d->id,
                'ieee_addr' => $d->ieee_addr,
                'name'      => $d->name,
                'type_id'   => $d->device_type_id,
                'type_name' => $d->deviceType?->name,
                'gateway'   => $d->gateway?->gateway_id,
                'gateway_name' => $d->gateway?->name,
            ]);

        return response()->json(['success' => true, 'data' => $devices]);
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

        // is_active=false cihazları da güncelleyebilmek için filtre kaldırıldı
        $device = Device::where('dealer_id', $dealerId)->findOrFail($id);

        $request->validate([
            'name'           => 'sometimes|string|max:255',
            'device_type_id' => 'sometimes|nullable|exists:device_types,id',
            'is_active'      => 'sometimes|boolean',
            'mac_address'    => 'sometimes|string|unique:devices,mac_address,' . $device->id,
        ]);

        $device->update($request->only([
            'name', 'mac_address', 'mqtt_topic', 'settings',
            'is_active', 'device_type_id',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Cihaz güncellendi',
            'data'    => $device->fresh()->load('deviceType'),
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

    /**
     * Tüm aktif cihazların anlık state'ini döner (polling için).
     * GET /api/v1/devices/states
     */
    public function states(Request $request)
    {
        $dealerId = $request->user()->dealer_id;

        $devices = Device::where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->get(['id', 'current_state', 'is_online', 'last_seen_at', 'state_updated_at']);

        $result = [];
        foreach ($devices as $device) {
            // Redis cache öncelikli, yoksa DB
            $cached = $this->mqttService->getDeviceState($device);
            $result[$device->id] = [
                'state'            => empty($cached) ? ($device->current_state ?? []) : $cached,
                'is_online'        => $device->is_online,
                'last_seen_at'     => $device->last_seen_at,
                'state_updated_at' => $device->state_updated_at,
            ];
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Tüm aktif cihazlara get_state MQTT komutu gönderir.
     * POST /api/v1/devices/sync
     */
    public function sync(Request $request)
    {
        $dealerId = $request->user()->dealer_id;

        $devices = Device::with('gateway')
            ->where('dealer_id', $dealerId)
            ->where('is_active', true)
            ->whereNotNull('ieee_addr')
            ->get();

        $sent = 0;
        foreach ($devices as $device) {
            try {
                $this->mqttService->sendCommandBySlug($device, 'get_state', [], null);
                $sent++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Sync failed for device #{$device->id}: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'queried' => $sent]);
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

    /**
     * Cihazı gateway'ine temiz yeniden eşleştir (PIYA transfer prosedürü).
     * Gateway'i scan_mode'a alır + cihaza piya_factory_reset gönderir → cihaz sıfırlanıp yeniden katılır.
     * "removed"/kirli eşleşme durumundaki cihazları düzeltmek için.
     * POST /api/v1/devices/{id}/repair
     */
    public function repair(Request $request, $id)
    {
        $dealerId = $request->user()->dealer_id;
        $device = Device::with('gateway')->where('dealer_id', $dealerId)->findOrFail($id);

        $gatewayId = $device->gateway?->gateway_id;
        if (!$gatewayId || !$device->ieee_addr) {
            return response()->json([
                'success' => false,
                'message' => 'Cihazın gateway veya IEEE bilgisi eksik; yeniden eşleştirilemiyor.',
            ], 422);
        }

        $b = new GatewayCommandBuilder();
        $topic = "pigasoft/{$gatewayId}/commands";

        // 1) Hedef gateway'i tarama moduna al (yeni katılımı kabul etsin)
        $this->mqttService->publishRaw($topic, json_encode($b->scanMode(60), JSON_UNESCAPED_SLASHES));
        // 2) Cihazı fabrika ayarına döndür → kirli eşleşmeyi sıfırlar, yeniden katılır
        $this->mqttService->publishRaw($topic, json_encode($b->factoryReset($device->ieee_addr), JSON_UNESCAPED_SLASHES));

        return response()->json([
            'success' => true,
            'message' => 'Yeniden eşleştirme başlatıldı: gateway 60 sn tarama modunda, cihaz sıfırlanıyor. Birkaç saniye içinde yeniden katılır.',
        ]);
    }
}
