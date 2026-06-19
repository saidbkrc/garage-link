<?php

namespace App\Console\Commands;

use App\Events\DeviceStateUpdated;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Gateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe';

    protected $description = 'MQTT broker\'a bağlanır ve cihaz mesajlarını dinler (sürekli çalışır, otomatik yeniden bağlanır)';

    private array $topics = [
        'pigasoft/+/gateway',       // Gateway keşfi
        'pigasoft/+/connectionpub', // Cihaz online/offline
        'pigasoft/+/data',          // Cihaz durumu güncellemeleri
        'pigasoft/+/devicelist',    // Cihaz listesi
        'pigasoft/+/health',        // Sistem sağlığı
        'pigasoft/+/scan_mode',     // Tarama sonuçları
        'pigasoft/+/commands',      // Panel'den gönderilen komutlar (loglama)
    ];

    public function handle(): void
    {
        $this->info('MQTT subscriber başlatılıyor...');
        $this->info('Çıkmak için Ctrl+C');

        // Sonsuz döngü: bağlantı koparsa otomatik yeniden bağlan
        while (true) {
            try {
                $this->runLoop();
            } catch (\Exception $e) {
                $this->error('[MQTT] Bağlantı kesildi: ' . $e->getMessage());
                $this->warn('[MQTT] 5 saniye sonra yeniden bağlanılıyor...');
                sleep(5);
            }
        }
    }

    /**
     * MQTT'ye bağlan, subscribe et ve loop'a gir.
     * Herhangi bir hata fırlatıldığında handle() yeniden çağırır.
     */
    private function runLoop(): void
    {
        $host     = config('mqtt-client.connections.default.host');
        $port     = (int) config('mqtt-client.connections.default.port', 1883);
        $clientId = config('mqtt-client.connections.default.client_id', 'garagelink_sub_001');

        $settings = (new ConnectionSettings())
            ->setConnectTimeout(30)
            ->setSocketTimeout(10)
            ->setKeepAliveInterval(20); // 20 saniyede bir ping → broker idle-timeout'u önler

        $mqtt = new MqttClient($host, $port, $clientId, MqttClient::MQTT_3_1);
        $mqtt->connect($settings, true); // clean session = true

        $this->info('[MQTT] Bağlandı: ' . $host . ':' . $port . ' (client: ' . $clientId . ')');

        foreach ($this->topics as $topic) {
            $mqtt->subscribe($topic, function (string $topic, string $message) {
                $this->handleMessage($topic, $message);
            }, 1);
        }

        $this->info('[MQTT] Topicler subscribe edildi. Mesajlar bekleniyor...');

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

        // Her mesajda gateway'i DB'de güncelle (30s throttle — gereksiz DB yazımını önler)
        // Bu sayede gateway sadece 'health' veya 'data' atsa bile yakalanır
        $cacheKey = "mqtt:gw_seen:{$gatewayId}";
        if (!Cache::has($cacheKey)) {
            Gateway::updateOrCreate(
                ['gateway_id' => $gatewayId],
                ['is_online' => true, 'last_seen_at' => now()]
            );
            Cache::put($cacheKey, true, 30);
        }

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
            'commands'      => $this->handleCommandLog($gatewayId, $payload),
            default         => null,
        };
    }

    /**
     * Panel'den gateway'e gönderilen komutları loglar.
     * pigasoft/+/commands — kendi publish ettiğimiz mesajları dinleyerek terminalde gösterir.
     */
    protected function handleCommandLog(string $gatewayId, array $payload): void
    {
        $prettyPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->line("<fg=cyan>[→ KOMUT]</> {$gatewayId}: {$prettyPayload}");
    }

    /**
     * Gateway ilk bağlantıda kendini tanıtır.
     * pigasoft/+/gateway
     * dealer_id NULL bırakılır — bayi panelden "Bu gateway benim" diyerek sahiplenir.
     */
    protected function handleGateway(string $gatewayId, array $payload): void
    {
        // Payload'dan metadata çıkar (varsa güncelle)
        // Payload örneği: {"type":"gateway_hello","gateway_id":"gw_XXX","project":"...","fw":"1.0.0","ip":"..."}
        $updateData = [
            'is_online'    => true,
            'last_seen_at' => now(),
        ];

        if (!empty($payload['ip'])) {
            $updateData['ip_address'] = $payload['ip'];
        }

        if (!empty($payload['fw'])) {
            $updateData['firmware_version'] = $payload['fw'];
        }

        $gateway = Gateway::updateOrCreate(
            ['gateway_id' => $gatewayId],
            $updateData
            // dealer_id NULL kalır — claim sistemi ile bayi atanacak
        );

        // İsim yoksa 'project' alanından ata (kullanıcı sonradan değiştirebilir)
        if (empty($gateway->name) && !empty($payload['project'])) {
            $gateway->update(['name' => $payload['project']]);
        }

        $status = $gateway->dealer_id ? "bayi#{$gateway->dealer_id}" : 'SAHİPSİZ';
        $ip     = $payload['ip'] ?? '?';
        $fw     = $payload['fw'] ?? '?';
        $this->line("<fg=green>[Gateway]</> Keşfedildi/güncellendi: {$gatewayId} | IP: {$ip} | FW: {$fw} | {$status}");
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

        $device = Device::where('ieee_addr', $ieeeAddr)->first();

        if (!$device) {
            return;
        }

        $device->update([
            'is_online'    => $isOnline,
            'last_seen_at' => $isOnline ? now() : null,
        ]);

        $status = $isOnline ? 'online' : 'offline';
        $this->line("[Bağlantı] {$ieeeAddr} → {$status}");

        // Panele canlı yayınla (online/offline rozeti anında güncellensin)
        DeviceStateUpdated::dispatch($device);
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

        // Panele canlı yayınla
        DeviceStateUpdated::dispatch($device);
    }

    /**
     * Gateway'den cihaz bilgisi geldi.
     * pigasoft/+/devicelist
     *
     * Firmware her cihazı AYRI bir mesaj olarak gönderir (array değil, tek obje).
     * Payload örneği:
     *   {
     *     "index": 2,
     *     "ieee_addr": "58DEC2FEFFEEF648",
     *     "name": "",
     *     "device_state": "removed",
     *     "valid": true,
     *     "endpoints": [{"endpoint":1,"has_onoff":true,"in_clusters":[0,3,4,5,6],...}, ...]
     *   }
     *
     * Yeni cihazlar is_active=false ile kaydedilir — bayi panel'den isim verip aktifleştirir.
     */
    protected function handleDeviceList(string $gatewayId, array $payload): void
    {
        // Firmware "index" alanını kullanıyor (bizim önceki "device_index" değil)
        $ieeeAddr       = $payload['ieee_addr'] ?? null;
        $deviceIndex    = $payload['index'] ?? null;
        $rawName        = $payload['name'] ?? '';
        $deviceName     = $payload['device_name'] ?? null;
        $onoffEndpoints = $payload['onoff_endpoints'] ?? [];
        $endpoints      = $payload['endpoints'] ?? [];

        if (!$ieeeAddr) {
            return;
        }

        $gateway = Gateway::where('gateway_id', $gatewayId)->first();
        if (!$gateway) {
            return;
        }

        $existing = Device::where('ieee_addr', $ieeeAddr)->first();

        if ($existing) {
            // Var olan cihazı güncelle — tip ve config da yenilenir (relay kanal değişikliği vb.)
            $updatedTypeId = $this->resolveDeviceTypeFromEndpoints($endpoints, $deviceName);

            $updateData = [
                'device_index'  => $deviceIndex,
                'gateway_db_id' => $gateway->id,
                'is_online'     => true,
                'last_seen_at'  => now(),
            ];

            // device_type çözümlendiyse güncelle
            if ($updatedTypeId) {
                $updateData['device_type_id'] = $updatedTypeId;
            }

            // Röle ise kanal bilgisini config'e yaz (sayı değişmiş olabilir)
            if ($deviceName && str_starts_with($deviceName, 'relay_')) {
                $channelCount = count($onoffEndpoints) ?: (int) filter_var($deviceName, FILTER_SANITIZE_NUMBER_INT);
                $updateData['config'] = ['channel_count' => $channelCount, 'onoff_endpoints' => $onoffEndpoints];
            }

            $existing->update($updateData);
            $this->line("[Cihaz] Güncellendi: {$ieeeAddr} (index: {$deviceIndex})");
        } elseif ($gateway->dealer_id) {
            // Yeni cihaz — sadece sahiplenilmiş gateway'ler için oluştur
            $defaultName  = !empty($rawName) ? $rawName : "Cihaz " . ($deviceIndex ?? '?');
            $deviceTypeId = $this->resolveDeviceTypeFromEndpoints($endpoints, $deviceName);

            // Röle ise kanal bilgisini config'e kaydet
            $config = null;
            if ($deviceName && str_starts_with($deviceName, 'relay_')) {
                $channelCount = count($onoffEndpoints) ?: (int) filter_var($deviceName, FILTER_SANITIZE_NUMBER_INT);
                $config = ['channel_count' => $channelCount, 'onoff_endpoints' => $onoffEndpoints];
            }

            Device::create([
                'dealer_id'      => $gateway->dealer_id,
                'gateway_db_id'  => $gateway->id,
                'ieee_addr'      => $ieeeAddr,
                'mac_address'    => $ieeeAddr,  // Zigbee ieee_addr = donanım MAC adresi
                'device_index'   => $deviceIndex,
                'name'           => $defaultName,
                'device_type_id' => $deviceTypeId,
                'config'         => $config,
                'is_active'      => false,      // Bayi yapılandırana kadar pasif
                'is_online'      => true,
                'last_seen_at'   => now(),
            ]);
            $typeName = $deviceTypeId
                ? (DeviceType::find($deviceTypeId)?->name ?? 'Bilinmiyor')
                : 'Bilinmiyor';
            $this->line("<fg=green>[Cihaz]</> Yeni bulundu: {$ieeeAddr} | index: {$deviceIndex} | tip: {$typeName}");
        }
    }

    /**
     * Zigbee endpoint kümesinden cihaz tipini çıkar.
     *
     * Zigbee cluster ID'leri:
     *   6   = On/Off
     *   8   = Level Control (parlaklık)
     *   768 = Color Control (renk)
     *   513 = Thermostat
     *   1026 = Temperature Measurement
     *   1030 = Occupancy Sensing (hareket)
     */
    private function resolveDeviceTypeFromEndpoints(array $endpoints, ?string $deviceName = null): ?int
    {
        // device_name kontrolü ÖNCE yapılır — röle kartları cluster bazlı tespite girmeden yakalanır
        if ($deviceName && str_starts_with($deviceName, 'relay_')) {
            return DeviceType::where('slug', 'relay')->first()?->id;
        }

        // Tüm endpointlerin in_clusters'larını birleştir
        $allClusters = [];
        foreach ($endpoints as $ep) {
            foreach ($ep['in_clusters'] ?? [] as $cluster) {
                $allClusters[] = $cluster;
            }
        }
        $allClusters = array_unique($allClusters);

        // Cluster varlığına göre cihaz tipini belirle
        $hasColor   = in_array(768, $allClusters);   // Color Control
        $hasLevel   = in_array(8, $allClusters);     // Level Control
        $hasOnOff   = in_array(6, $allClusters);     // On/Off
        $hasTherm   = in_array(513, $allClusters);   // Thermostat
        $hasTemp    = in_array(1026, $allClusters);  // Temperature Measurement
        $hasOccup   = in_array(1030, $allClusters);  // Occupancy Sensing (hareket)

        $slug = null;

        if ($hasColor && $hasLevel) {
            $slug = 'led_strip';     // RGB LED
        } elseif ($hasLevel && !$hasColor) {
            $slug = 'bulb';          // Dimmable bulb
        } elseif ($hasOnOff && !$hasLevel) {
            $slug = 'switch';        // On/Off röle/anahtar
        } elseif ($hasTherm) {
            $slug = 'climate_ac';    // Termostat / klima
        } elseif ($hasTemp) {
            $slug = 'sensor_temperature';
        } elseif ($hasOccup) {
            $slug = 'sensor_motion';
        }

        if (!$slug) {
            return null;
        }

        return DeviceType::where('slug', $slug)->first()?->id;
    }

    /**
     * Firmware'den gelen device type string'ini DB'deki DeviceType kaydıyla eşleştir.
     * (Eski yöntem — string tabanlı eşleştirme)
     */
    private function resolveDeviceType(?string $type): ?int
    {
        if (!$type) {
            return null;
        }

        $deviceType = DeviceType::where('slug', $type)
            ->orWhere('name', $type)
            ->first();

        return $deviceType?->id;
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
