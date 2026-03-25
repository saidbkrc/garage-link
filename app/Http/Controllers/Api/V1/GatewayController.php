<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Command;
use App\Models\Device;
use App\Models\Gateway;
use App\Services\MqttService;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    protected $mqttService;

    public function __construct(MqttService $mqttService)
    {
        $this->mqttService = $mqttService;
    }

    /**
     * Dealer'ın kendi gateway'leri + sahiplenilmemiş (online) gateway'ler.
     * Sahiplenilmemiş olanlar claim için gösterilir.
     */
    public function index(Request $request)
    {
        $dealerId = $request->user()->dealer_id;

        // Bu dealer'ın gateway'leri (cihaz sayısı ile birlikte)
        $myGateways = Gateway::where('dealer_id', $dealerId)
            ->withCount('devices')
            ->orderByDesc('is_online')
            ->orderByDesc('last_seen_at')
            ->get();

        // Sahiplenilmemiş ama online görünen gateway'ler (claim için)
        $unclaimedGateways = Gateway::whereNull('dealer_id')
            ->where('is_online', true)
            ->orderByDesc('last_seen_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'my_gateways'       => $myGateways,
                'unclaimed_gateways' => $unclaimedGateways,
            ],
        ]);
    }

    /**
     * Sahiplenilmemiş bir gateway'i bu dealer'a ata.
     * Atama sonrası cihaz listesi için get_devicelist komutu gönderir.
     */
    public function claim(Request $request, string $gatewayId)
    {
        $dealerId = $request->user()->dealer_id;

        $gateway = Gateway::where('gateway_id', $gatewayId)
            ->whereNull('dealer_id')  // Sadece sahipsiz gateway'ler
            ->firstOrFail();

        $gateway->update(['dealer_id' => $dealerId]);

        // Cihaz listesini hemen iste
        $this->triggerDeviceListScan($gatewayId);

        return response()->json([
            'success' => true,
            'message' => 'Gateway başarıyla sahiplenildi. Cihazlar taranıyor...',
            'data'    => $gateway->fresh(),
        ]);
    }

    /**
     * Bu dealer'ın gateway'ine cihaz listesi taraması başlat.
     * get_devicelist MQTT komutu gönderir.
     */
    public function scan(Request $request, string $gatewayId)
    {
        $dealerId = $request->user()->dealer_id;

        $gateway = Gateway::where('gateway_id', $gatewayId)
            ->where('dealer_id', $dealerId)
            ->firstOrFail();

        $this->triggerDeviceListScan($gatewayId);

        return response()->json([
            'success' => true,
            'message' => 'Cihaz taraması başlatıldı. Birkaç saniye içinde cihazlar güncellenecek.',
        ]);
    }

    /**
     * Gateway'e bağlı cihazları listele (aktif ve pasif tüm cihazlar).
     */
    public function devices(Request $request, string $gatewayId)
    {
        $dealerId = $request->user()->dealer_id;

        $gateway = Gateway::where('gateway_id', $gatewayId)
            ->where('dealer_id', $dealerId)
            ->firstOrFail();

        $devices = Device::with('deviceType')
            ->where('gateway_db_id', $gateway->id)
            ->orderBy('device_index')
            ->get()
            ->map(function ($device) {
                return [
                    'id'           => $device->id,
                    'name'         => $device->name,
                    'ieee_addr'    => $device->ieee_addr,
                    'device_index' => $device->device_index,
                    'type'         => $device->deviceType?->slug ?? null,
                    'type_name'    => $device->deviceType?->name ?? 'Bilinmiyor',
                    'is_active'    => $device->is_active,
                    'is_online'    => $device->is_online,
                    'last_seen_at' => $device->last_seen_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => [
                'gateway' => $gateway,
                'devices' => $devices,
            ],
        ]);
    }

    /**
     * get_devicelist MQTT komutunu gateway'e gönder.
     */
    private function triggerDeviceListScan(string $gatewayId): void
    {
        $command = Command::where('slug', 'get_devicelist')
            ->where('is_active', true)
            ->first();

        if ($command) {
            $this->mqttService->sendGroupCommand($command, [], $gatewayId);
        }
    }
}
