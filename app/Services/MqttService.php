<?php

namespace App\Services;

use App\Models\Command;
use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Support\Facades\Cache;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

/**
 * NOT: Burası ESKİ düz protokolü (ieee_addr + Command::buildPayload) yayınlar — şu an canlı olan bu.
 * Yeni PIYA protokolü App\Services\GatewayCommandBuilder'da hazır ama henüz bağlı değil
 * (entegrasyon firmware cevabı bekliyor — bkz. görev #6/#7).
 */
class MqttService
{
    public function sendCommand(Device $device, Command $command, array $params = [], ?int $userId = null): DeviceLog
    {
        // Cihazın ieee_addr'ini otomatik ekle (firmware için en güvenilir tanımlayıcı)
        if ($device->ieee_addr && !isset($params['ieee_addr'])) {
            $params['ieee_addr'] = $device->ieee_addr;
        }

        // Fallback: ieee_addr yoksa device_index kullan
        if (!isset($params['ieee_addr']) && !isset($params['device_index'])) {
            $params['device_index'] = $device->device_index ?? $device->id;
        }

        // Gateway ID'yi topic'e ekle: pigasoft/{gateway_id}/commands
        $gatewayId = $device->gateway?->gateway_id;
        $topic = $gatewayId
            ? str_replace('{gateway_id}', $gatewayId, $command->mqtt_topic)
            : $command->mqtt_topic;

        $payload = $command->buildPayload($params);
        $message = json_encode($payload);

        $this->mqttPublish($topic, $message);

        $this->updateDeviceState($device, $command, $params);

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
