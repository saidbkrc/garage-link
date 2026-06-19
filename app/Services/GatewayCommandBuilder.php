<?php

namespace App\Services;

/**
 * PIYA gateway MQTT komut zarflarını üreten tek otorite.
 *
 * Tüm komutlar `pigasoft/<gateway_id>/commands` topic'ine yayınlanır.
 * Bu sınıf yalnızca payload (dizi/JSON) üretir; yayınlamayı MqttService yapar.
 *
 * Referans: "PIYA döküman" — firmware komut protokolü.
 * Not: Cihaz tanımlayıcısı kontrol komutlarında `address` (IEEE 16 hex);
 * yönetim komutlarında (`set_device_name`, `piya_factory_reset`) `ieee_addr`.
 */
class GatewayCommandBuilder
{
    // ─── Aydınlatma ──────────────────────────────────────────────────────────

    /**
     * RGB led kontrolü.
     * { "RGB_control": { "address", "color", "brightness"?, "endpoint"? } }
     */
    public function rgb(string $address, string $color, ?int $brightness = null, ?int $endpoint = null): array
    {
        $body = ['address' => $address, 'color' => $color];
        if ($brightness !== null) {
            $body['brightness'] = $brightness;
        }
        if ($endpoint !== null) {
            $body['endpoint'] = $endpoint;
        }

        return ['RGB_control' => $body];
    }

    /**
     * Renk sıcaklığı kontrolü.
     * { "CT_control": { "address", "color_temperature", "brightness"?, "endpoint"? } }
     *
     * color_temperature: 0..255 (legacy, 0=soğuk 255=sıcak) veya 153..500 (mired, 153=en soğuk 500=en sıcak)
     */
    public function colorTemperature(string $address, int $colorTemperature, ?int $brightness = null, ?int $endpoint = null): array
    {
        $body = ['address' => $address, 'color_temperature' => $colorTemperature];
        if ($brightness !== null) {
            $body['brightness'] = $brightness;
        }
        if ($endpoint !== null) {
            $body['endpoint'] = $endpoint;
        }

        return ['CT_control' => $body];
    }

    /**
     * Dimmer kontrolü — çok kanallı parlaklık.
     * { "DIM_control": { "address", "commands": [ { "endpoint", "brightness" }, ... ] } }
     *
     * @param array<int, array{endpoint:int, brightness:int}> $commands
     */
    public function dim(string $address, array $commands): array
    {
        return [
            'DIM_control' => [
                'address'  => $address,
                'commands' => array_values($commands),
            ],
        ];
    }

    // ─── Röle / Anahtar ──────────────────────────────────────────────────────

    /**
     * Röle (switch) kontrolü — çok kanallı on/off.
     * { "switch_control": { "address", "read_status"?, "commands": [ { "endpoint", "state" }, ... ] } }
     *
     * @param array<int, array{endpoint:int, state:string}> $commands
     */
    public function switchControl(string $address, array $commands, bool $readStatus = false): array
    {
        $body = ['address' => $address];
        if ($readStatus) {
            $body['read_status'] = true;
        }
        $body['commands'] = array_values($commands);

        return ['switch_control' => $body];
    }

    /**
     * Tek kanal aç/kapat kısayolu.
     */
    public function switchOne(string $address, int $endpoint, bool $on, bool $readStatus = false): array
    {
        return $this->switchControl(
            $address,
            [['endpoint' => $endpoint, 'state' => $on ? 'on' : 'off']],
            $readStatus
        );
    }

    /**
     * Bir rölenin anlık durumunu oku.
     * { "read_switch_state": { "address", "endpoint" } }
     */
    public function readSwitchState(string $address, int $endpoint): array
    {
        return [
            'read_switch_state' => [
                'address'  => $address,
                'endpoint' => $endpoint,
            ],
        ];
    }

    // ─── Zamanlayıcı (firmware tarafı) ───────────────────────────────────────

    /**
     * Zamanlayıcı ekle.
     * { "SCHED_control": { "address", "commands": [ { "hour","minute","endpoint"?,"action","name"?,"enabled"? }, ... ] } }
     *
     * @param array<int, array<string, mixed>> $commands
     */
    public function schedule(string $address, array $commands): array
    {
        return [
            'SCHED_control' => [
                'address'  => $address,
                'commands' => array_values($commands),
            ],
        ];
    }

    /**
     * Zamanlayıcı sil. { "scheduler_remove": <id> }
     */
    public function schedulerRemove(int $id): array
    {
        return ['scheduler_remove' => $id];
    }

    /**
     * Zamanlayıcı enable/disable. { "scheduler_enable": { "id", "enabled" } }
     */
    public function schedulerEnable(int $id, bool $enabled): array
    {
        return ['scheduler_enable' => ['id' => $id, 'enabled' => $enabled]];
    }

    // ─── Gateway yönetimi ────────────────────────────────────────────────────

    /**
     * Gateway bilgi komutları (toplu veya tekil gönderilebilir).
     * { "GATEWAY_control": { "get_gateway_info"?, "get_ip"?, "get_health"?, "get_time"? } }
     *
     * @param array<string, bool> $flags
     */
    public function gatewayControl(array $flags): array
    {
        return ['GATEWAY_control' => $flags];
    }

    /**
     * Gateway saatini ayarla.
     * { "set_time": { "hour","minute","second","day","month","year" } }
     */
    public function setTime(int $hour, int $minute, int $second, int $day, int $month, int $year): array
    {
        return ['set_time' => compact('hour', 'minute', 'second', 'day', 'month', 'year')];
    }

    // ─── Tarama / cihaz yönetimi ─────────────────────────────────────────────

    /** { "scan_mode": <saniye> } */
    public function scanMode(int $seconds): array
    {
        return ['scan_mode' => $seconds];
    }

    /** { "get_scan_results": true } */
    public function getScanResults(): array
    {
        return ['get_scan_results' => true];
    }

    /** { "add_scanned_device": "<16hexIEEE>" } */
    public function addScannedDevice(string $ieeeAddr): array
    {
        return ['add_scanned_device' => $ieeeAddr];
    }

    /** { "add_all_scanned_devices": true } */
    public function addAllScannedDevices(): array
    {
        return ['add_all_scanned_devices' => true];
    }

    /** { "get_devicelist": true } */
    public function getDeviceList(): array
    {
        return ['get_devicelist' => true];
    }

    /**
     * Cihazı başka gateway'e taşımak için fabrika ayarına döndür.
     * { "piya_factory_reset": true, "ieee_addr": "<16hex>" }
     */
    public function factoryReset(string $ieeeAddr): array
    {
        return ['piya_factory_reset' => true, 'ieee_addr' => $ieeeAddr];
    }

    /**
     * Cihaza isim ver.
     * { "set_device_name": { "ieee_addr", "name" } }
     */
    public function setDeviceName(string $ieeeAddr, string $name): array
    {
        return ['set_device_name' => ['ieee_addr' => $ieeeAddr, 'name' => $name]];
    }
}
