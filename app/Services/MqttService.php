<?php

namespace App\Services;

use App\Models\Command;
use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Support\Facades\Cache;
use PhpMqtt\Client\Facades\MQTT;

class MqttService
{
    public function sendCommand(Device $device, Command $command, array $params = [], ?int $userId = null): DeviceLog
    {
        $params['device_index'] = $params['device_index'] ?? $device->id;

        $payload = $command->buildPayload($params);

        $topic = $command->mqtt_topic;
        $message = json_encode($payload);

        MQTT::connection()->publish($topic, $message);

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

    public function sendGroupCommand(Command $command, array $params = []): void
    {
        $payload = $command->buildPayload($params);
        $message = json_encode($payload);

        MQTT::connection()->publish($command->mqtt_topic, $message);
    }

    protected function updateDeviceState(Device $device, Command $command, array $params): void
    {
        $state = Cache::get("device:{$device->id}:state", []);

        if ($command->slug === 'turn_on') {
            $state['power'] = 'on';
        } elseif ($command->slug === 'turn_off') {
            $state['power'] = 'off';
        } elseif ($command->slug === 'toggle') {
            $state['power'] = ($state['power'] ?? 'off') === 'on' ? 'off' : 'on';
        } elseif ($command->slug === 'brightness' && isset($params['brightness'])) {
            $state['brightness'] = $params['brightness'];
        } elseif ($command->slug === 'color' && isset($params['r'])) {
            $state['color'] = ['r' => $params['r'], 'g' => $params['g'], 'b' => $params['b']];
        }

        $state['last_command'] = $command->slug;
        $state['last_updated'] = now()->toDateTimeString();

        Cache::put("device:{$device->id}:state", $state, 86400);
    }

    public function getDeviceState(Device $device): array
    {
        return Cache::get("device:{$device->id}:state", []);
    }

    public function publishRaw(string $topic, string $message): void
    {
        MQTT::connection()->publish($topic, $message);
    }
}
