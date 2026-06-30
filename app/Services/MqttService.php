<?php

namespace App\Services;

use App\Events\DeviceStateUpdated;
use App\Models\Command;
use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

/**
 * Komutları gateway'e MQTT ile yayınlar.
 *
 * Röle (switch_control) ve LED (RGB/CT/DIM) komutları YENİ PIYA protokolüne
 * GatewayCommandBuilder ile çevrilir. Henüz migrate edilmemiş cihaz tipleri
 * eski düz protokolde (Command::buildPayload) kalır — kademeli geçiş.
 */
class MqttService
{
    public function sendCommand(Device $device, Command $command, array $params = [], ?int $userId = null): DeviceLog
    {
        $gatewayId = $device->gateway?->gateway_id;

        // Yeni PIYA protokolü: röle/LED komutlarını builder ile üret (varsa).
        $piyaPayload = $gatewayId ? $this->buildPiyaPayload($device, $command, $params) : null;

        if ($piyaPayload !== null) {
            $topic   = "pigasoft/{$gatewayId}/commands";
            $payload = $piyaPayload;
        } else {
            // ── ESKİ düz protokol (henüz migrate edilmemiş cihaz tipleri) ──
            // Cihazın ieee_addr'ini otomatik ekle (firmware için en güvenilir tanımlayıcı)
            if ($device->ieee_addr && !isset($params['ieee_addr'])) {
                $params['ieee_addr'] = $device->ieee_addr;
            }
            // Fallback: ieee_addr yoksa device_index kullan
            if (!isset($params['ieee_addr']) && !isset($params['device_index'])) {
                $params['device_index'] = $device->device_index ?? $device->id;
            }
            $topic = $gatewayId
                ? str_replace('{gateway_id}', $gatewayId, $command->mqtt_topic)
                : $command->mqtt_topic;
            $payload = $command->buildPayload($params);
        }

        $message = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $this->mqttPublish($topic, $message);

        $this->updateDeviceState($device, $command, $params);

        // Panele canlı yansıt (Reverb yoksa sessiz geç — komut gönderimini bozmasın)
        try {
            DeviceStateUpdated::dispatch($device);
        } catch (\Throwable $e) {
            Log::warning('DeviceStateUpdated broadcast başarısız: ' . $e->getMessage());
        }

        $log = DeviceLog::create([
            'device_id' => $device->id,
            'command_id' => $command->id,
            'dealer_user_id' => $userId,
            'source' => 'panel',
            'request_payload' => $payload,
            'status' => 'pending',
        ]);

        return $log;
    }

    /**
     * Eski slug bazlı komutu YENİ PIYA protokol zarfına çevirir (GatewayCommandBuilder).
     * Migrate edilmemiş slug/cihaz için null döner → çağıran eski protokole düşer.
     */
    private function buildPiyaPayload(Device $device, Command $command, array $params): ?array
    {
        $address = $device->ieee_addr;
        if (!$address) {
            return null; // PIYA için IEEE address zorunlu
        }

        $b        = new GatewayCommandBuilder();
        $endpoint = isset($params['endpoint']) ? (int) $params['endpoint'] : null;
        $type     = $device->deviceType?->slug;

        return match ($command->slug) {
            // ── Röle (switch_control) ──
            'relay_turn_on'  => $b->switchControl($address, [['endpoint' => $endpoint ?? 1, 'state' => 'on']], true),
            'relay_turn_off' => $b->switchControl($address, [['endpoint' => $endpoint ?? 1, 'state' => 'off']], true),

            // Tüm cihaz aç/kapat — yalnızca röle: tüm kanalları gönder
            'turn_on'  => $type === 'relay' ? $b->switchControl($address, $this->allRelayChannels($device, 'on'), true) : null,
            'turn_off' => $type === 'relay' ? $b->switchControl($address, $this->allRelayChannels($device, 'off'), true) : null,

            // Röle anlık durum sorgu
            'get_state' => $type === 'relay' ? $b->readSwitchState($address, $endpoint ?? 1) : null,

            // ── LED ──
            'brightness'        => $b->dim($address, [['endpoint' => $endpoint ?? 1, 'brightness' => (int) ($params['brightness'] ?? 0)]]),
            'color'             => $b->rgb($address, $params['color'] ?? 'rgb(0,0,0)', isset($params['brightness']) ? (int) $params['brightness'] : null, $endpoint),
            'color_brightness'  => $b->rgb($address, $params['color'] ?? 'rgb(0,0,0)', (int) ($params['brightness'] ?? 0), $endpoint),
            'color_temperature' => $b->colorTemperature($address, (int) ($params['temperature'] ?? 0), isset($params['brightness']) ? (int) $params['brightness'] : null, $endpoint),

            default => null, // migrate edilmemiş → eski protokol
        };
    }

    /**
     * Röle kartının tüm kanalları için {endpoint, state} listesi (config'ten).
     *
     * @return array<int, array{endpoint:int, state:string}>
     */
    private function allRelayChannels(Device $device, string $state): array
    {
        $eps = $device->config['onoff_endpoints'] ?? null;
        if (empty($eps)) {
            $count = (int) ($device->config['channel_count'] ?? 1);
            $eps   = range(1, max(1, $count));
        }

        return array_map(fn ($ep) => ['endpoint' => (int) $ep, 'state' => $state], $eps);
    }

    public function sendCommandBySlug(Device $device, string $slug, array $params = [], ?int $userId = null): DeviceLog
    {
        $command = Command::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return $this->sendCommand($device, $command, $params, $userId);
    }

    public function sendGroupCommand(Command $command, array $params = [], ?string $gatewayId = null): void
    {
        $topic = $gatewayId
            ? str_replace('{gateway_id}', $gatewayId, $command->mqtt_topic)
            : $command->mqtt_topic;

        $payload = $command->buildPayload($params);
        $message = json_encode($payload);

        $this->mqttPublish($topic, $message);
    }

    protected function updateDeviceState(Device $device, Command $command, array $params): void
    {
        $state = Cache::get("device:{$device->id}:state", []);

        if ($command->slug === 'turn_on') {
            $state['power'] = true;
        } elseif ($command->slug === 'turn_off') {
            $state['power'] = false;
        } elseif ($command->slug === 'toggle') {
            $state['power'] = !($state['power'] ?? false);
        } elseif ($command->slug === 'brightness' && isset($params['brightness'])) {
            $state['brightness'] = (int) $params['brightness'];
            $state['power'] = true;
        } elseif (in_array($command->slug, ['color', 'color_brightness']) && isset($params['color'])) {
            // firmware "rgb(R, G, B)" string formatı
            $state['color'] = $params['color'];
            $state['power'] = true;
            if (isset($params['brightness'])) {
                $state['brightness'] = (int) $params['brightness'];
            }
        }

        // Röle kanalları (yeni protokol) — gateway gerçek state yaymadığı için OPTİMİSTİK izlenir.
        // state['channels'] = { "1": "on", "2": "off", ... }
        if (in_array($command->slug, ['relay_turn_on', 'relay_turn_off'], true) && isset($params['endpoint'])) {
            $channels = $state['channels'] ?? [];
            $channels[(string) (int) $params['endpoint']] = $command->slug === 'relay_turn_on' ? 'on' : 'off';
            $state['channels'] = $channels;
        } elseif (in_array($command->slug, ['turn_on', 'turn_off'], true) && $device->deviceType?->slug === 'relay') {
            $eps = $device->config['onoff_endpoints'] ?? range(1, (int) ($device->config['channel_count'] ?? 1));
            $channels = $state['channels'] ?? [];
            foreach ($eps as $ep) {
                $channels[(string) (int) $ep] = $command->slug === 'turn_on' ? 'on' : 'off';
            }
            $state['channels'] = $channels;
        }

        $state['last_command'] = $command->slug;
        $state['last_updated'] = now()->toDateTimeString();

        Cache::put("device:{$device->id}:state", $state, 86400);

        // Veritabanını da güncelle
        $device->updateState($state);
    }

    public function getDeviceState(Device $device): array
    {
        return Cache::get("device:{$device->id}:state", []);
    }

    public function publishRaw(string $topic, string $message): void
    {
        $this->mqttPublish($topic, $message);
    }

    /**
     * Her publish için yeni bir bağlantı açar (unique client_id).
     * Bu sayede mqtt:subscribe bağlantısını kesmez.
     */
    private function mqttPublish(string $topic, string $message, int $qos = 1): void
    {
        $host     = config('mqtt-client.connections.default.host');
        $port     = (int) config('mqtt-client.connections.default.port', 1883);
        $clientId = 'glink_pub_' . substr(uniqid(), -8);

        $settings = (new ConnectionSettings())
            ->setConnectTimeout(10)
            ->setSocketTimeout(5);

        $mqtt = new MqttClient($host, $port, $clientId, MqttClient::MQTT_3_1);
        $mqtt->connect($settings, true);
        $mqtt->publish($topic, $message, $qos);
        $mqtt->disconnect();
    }
}
