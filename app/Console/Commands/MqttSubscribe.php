<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Gateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';

    protected $description = 'MQTT broker\'a bağlanır ve cihaz mesajlarını dinler (sürekli çalışır)';

    public function handle(): void
    {
        $this->info('MQTT subscriber başlatılıyor...');

        $topics = [
            'pigasoft/+/gateway',      // Gateway keşfi — önce bunu al
            'pigasoft/+/connectionpub', // Cihaz online/offline
            'pigasoft/+/data',          // Cihaz durumu güncellemeleri
            'pigasoft/+/devicelist',    // Cihaz listesi
            'pigasoft/+/health',        // Sistem sağlığı
            'pigasoft/+/scan_mode',     // Tarama sonuçları
        ];

        $mqtt = MQTT::connection();

        foreach ($topics as $topic) {
            $mqtt->subscribe($topic, function (string $topic, string $message) {
                $this->handleMessage($topic, $message);
            }, 1);
        }

        $this->info('Topicler subscribe edildi. Mesajlar bekleniyor...');
        $this->info('Çıkmak için Ctrl+C');

        // Sürekli döngü — mesajları işle
        $mqtt->loop(true);
    }

    protected function handleMessage(string $topic, string $message): void
    {
        // topic: pigasoft/{gateway_id}/{suffix}
        $parts = explode('/', $topic);
        if (count($parts) < 3) {
            return;
        }

        $gatewayId = $parts[1]; // gw_04D9C2FEFFEEF648
        $suffix    = $parts[2]; // gateway, data, connectionpub, vb.

        $payload = json_decode($message, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning("MQTT: Geçersiz JSON [{$topic}]: {$message}");
            return;
        }

        match ($suffix) {
            'gateway'       => $this->handleGateway($gatewayId, $payload),
            'connectionpub' => $this->handleConnectionPub($gatewayId, $payload),
            'data'          => $this->handleData($gatewayId, $payload),
            'devicelist'    => $this->handleDeviceList($gatewayId, $payload),
            'health'        => $this->handleHealth($gatewayId, $payload),
            default         => null,
        };
    }

    /**
     * Gateway ilk bağlantıda kendini tanıtır.
     * pigasoft/+/gateway
     */
    protected function handleGateway(string $gatewayId, array $payload): void
    {
        $gateway = Gateway::updateOrCreate(
            ['gateway_id' => $gatewayId],
            ['is_online' => true, 'last_seen_at' => now()]
        );

        $this->line("[Gateway] Keşfedildi/güncellendi: {$gatewayId}");
    }

    /**
     * Cihaz online/offline olayları.
     * pigasoft/+/connectionpub
     * Payload: {"event": "device_online", "ieee_addr": "CCD8...", ...}
     */
    protected function handleConnectionPub(string $gatewayId, array $payload): void
    {
        $event    = $payload['event'] ?? null;
        $ieeeAddr = $payload['ieee_addr'] ?? null;

        if (!$event || !$ieeeAddr) {
            return;
        }

        $isOnline = $event === 'device_online';

        $updated = Device::where('ieee_addr', $ieeeAddr)->update([
            'is_online'    => $isOnline,
            'last_seen_at' => $isOnline ? now() : null,
        ]);

        if ($updated) {
            $status = $isOnline ? 'online' : 'offline';
            $this->line("[Bağlantı] {$ieeeAddr} → {$status}");
        }
    }

    /**
     * Cihazdan gelen durum verisi.
     * pigasoft/+/data
     * Payload: {"ieee_addr": "CCD8...", "brightness": 80, "color": "rgb(255,0,0)", ...}
     */
    protected function handleData(string $gatewayId, array $payload): void
    {
        $ieeeAddr = $payload['ieee_addr'] ?? null;

        if (!$ieeeAddr) {
            return;
        }

        $device = Device::where('ieee_addr', $ieeeAddr)->first();

        if (!$device) {
            return;
        }

        // Payload'dan durum bilgilerini çıkar
        $stateUpdate = [];

        if (isset($payload['power']))      $stateUpdate['power']      = $payload['power'];
        if (isset($payload['brightness'])) $stateUpdate['brightness'] = $payload['brightness'];
        if (isset($payload['color']))      $stateUpdate['color']      = $payload['color'];
        if (isset($payload['temperature'])) $stateUpdate['temperature'] = $payload['temperature'];
        if (isset($payload['humidity']))   $stateUpdate['humidity']   = $payload['humidity'];

        if (!empty($stateUpdate)) {
            $device->updateState($stateUpdate);

            // Redis cache'i de güncelle
            $cacheKey = "device:{$device->id}:state";
            $state = Cache::get($cacheKey, []);
            $state = array_merge($state, $stateUpdate);
            $state['last_updated'] = now()->toDateTimeString();
            Cache::put($cacheKey, $state, 86400);

            $this->line("[Veri] {$ieeeAddr} durumu güncellendi");
        }

        // Cihazı online işaretle
        $device->update(['is_online' => true, 'last_seen_at' => now()]);
    }

    /**
     * Gateway'den cihaz listesi geldi.
     * pigasoft/+/devicelist
     * Payload: {"gateway_id": "gw_...", "devices": [{ieee_addr, device_index, type, name, ...}]}
     */
    protected function handleDeviceList(string $gatewayId, array $payload): void
    {
        $devices = $payload['devices'] ?? [];

        if (empty($devices)) {
            return;
        }

        $gateway = Gateway::where('gateway_id', $gatewayId)->first();

        foreach ($devices as $deviceData) {
            $ieeeAddr    = $deviceData['ieee_addr'] ?? null;
            $deviceIndex = $deviceData['device_index'] ?? null;

            if (!$ieeeAddr) {
                continue;
            }

            // ieee_addr ile eşleşen cihazın index ve gateway bilgisini güncelle
            $update = ['device_index' => $deviceIndex];
            if ($gateway) {
                $update['gateway_db_id'] = $gateway->id;
            }

            Device::where('ieee_addr', $ieeeAddr)->update($update);
        }

        $count = count($devices);
        $this->line("[Cihaz Listesi] {$gatewayId}: {$count} cihaz");
    }

    /**
     * Gateway sağlık bilgisi.
     * pigasoft/+/health
     */
    protected function handleHealth(string $gatewayId, array $payload): void
    {
        Gateway::where('gateway_id', $gatewayId)->update([
            'is_online'    => true,
            'last_seen_at' => now(),
        ]);

        $this->line("[Health] {$gatewayId}: uptime=" . ($payload['uptime_seconds'] ?? '?') . 's');
    }
}
