<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Room;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $dealerId = 1;

        // Odaları al
        $salon = Room::where('dealer_id', $dealerId)->where('name', 'Salon')->first();
        $yatakOdasi = Room::where('dealer_id', $dealerId)->where('name', 'Yatak Odası')->first();
        $mutfak = Room::where('dealer_id', $dealerId)->where('name', 'Mutfak')->first();
        $banyo = Room::where('dealer_id', $dealerId)->where('name', 'Banyo')->first();
        $ofis = Room::where('dealer_id', $dealerId)->where('name', 'Ofis')->first();
        $koridor = Room::where('dealer_id', $dealerId)->where('name', 'Koridor')->first();
        $garaj = Room::where('dealer_id', $dealerId)->where('name', 'Garaj')->first();

        // Cihaz tiplerini al
        $ledStrip = DeviceType::where('slug', 'led_strip')->first();
        $bulb = DeviceType::where('slug', 'bulb')->first();
        $switch = DeviceType::where('slug', 'switch')->first();
        $climateAc = DeviceType::where('slug', 'climate_ac')->first();
        $curtain = DeviceType::where('slug', 'curtain')->first();
        $plug = DeviceType::where('slug', 'plug')->first();
        $sensorTemp = DeviceType::where('slug', 'sensor_temperature')->first();
        $sensorMotion = DeviceType::where('slug', 'sensor_motion')->first();
        $lock = DeviceType::where('slug', 'lock')->first();
        $garage = DeviceType::where('slug', 'garage')->first();
        $alarm = DeviceType::where('slug', 'alarm')->first();

        $devices = [
            // Salon
            [
                'name' => 'TV Arkası LED',
                'device_type_id' => $ledStrip?->id,
                'room_id' => $salon?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:01',
                'is_online' => true,
                'current_state' => ['power' => true, 'brightness' => 85, 'color' => '#A855F7'],
            ],
            [
                'name' => 'Salon Klima',
                'device_type_id' => $climateAc?->id,
                'room_id' => $salon?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:02',
                'is_online' => true,
                'current_state' => ['power' => true, 'current_temp' => 26, 'target_temp' => 22, 'mode' => 'cool'],
            ],
            [
                'name' => 'Salon Perde',
                'device_type_id' => $curtain?->id,
                'room_id' => $salon?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:03',
                'is_online' => true,
                'current_state' => ['position' => 75],
            ],
            [
                'name' => 'TV Prizi',
                'device_type_id' => $plug?->id,
                'room_id' => $salon?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:04',
                'is_online' => true,
                'current_state' => ['power' => true],
                'energy_today' => 1.2,
                'config' => ['connected_device' => 'TV + Soundbar'],
            ],
            [
                'name' => 'Salon Sıcaklık',
                'device_type_id' => $sensorTemp?->id,
                'room_id' => $salon?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:05',
                'is_online' => true,
                'current_state' => ['temperature' => 24.5, 'humidity' => 45],
            ],

            // Yatak Odası
            [
                'name' => 'Tavan Lambası',
                'device_type_id' => $bulb?->id,
                'room_id' => $yatakOdasi?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:06',
                'is_online' => true,
                'current_state' => ['power' => true, 'brightness' => 70, 'temperature' => 25],
            ],
            [
                'name' => 'Yatak Odası Anahtar',
                'device_type_id' => $switch?->id,
                'room_id' => $yatakOdasi?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:07',
                'is_online' => true,
                'current_state' => ['channels' => [true, false, true, false]],
                'config' => ['channel_names' => ['Ana Işık', 'Abajur', 'Spot', 'Gece']],
            ],
            [
                'name' => 'Yatak Odası Klima',
                'device_type_id' => $climateAc?->id,
                'room_id' => $yatakOdasi?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:08',
                'is_online' => false,
                'current_state' => ['power' => false, 'current_temp' => 24, 'target_temp' => 23, 'mode' => 'auto'],
            ],
            [
                'name' => 'Yatak Odası Perde',
                'device_type_id' => $curtain?->id,
                'room_id' => $yatakOdasi?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:09',
                'is_online' => true,
                'current_state' => ['position' => 0],
            ],

            // Mutfak
            [
                'name' => 'Mutfak Tezgah LED',
                'device_type_id' => $ledStrip?->id,
                'room_id' => $mutfak?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:10',
                'is_online' => true,
                'current_state' => ['power' => true, 'brightness' => 100, 'color' => '#67E8F9'],
            ],

            // Ofis
            [
                'name' => 'Çalışma Lambası',
                'device_type_id' => $bulb?->id,
                'room_id' => $ofis?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:11',
                'is_online' => true,
                'current_state' => ['power' => false, 'brightness' => 50, 'temperature' => 80],
            ],
            [
                'name' => 'Bilgisayar Prizi',
                'device_type_id' => $plug?->id,
                'room_id' => $ofis?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:12',
                'is_online' => true,
                'current_state' => ['power' => true],
                'energy_today' => 2.8,
                'config' => ['connected_device' => 'PC + Monitör'],
            ],

            // Banyo
            [
                'name' => 'Nem Sensörü',
                'device_type_id' => $sensorTemp?->id,
                'room_id' => $banyo?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:13',
                'is_online' => true,
                'current_state' => ['temperature' => 22, 'humidity' => 65],
            ],

            // Koridor
            [
                'name' => 'Hareket Sensörü',
                'device_type_id' => $sensorMotion?->id,
                'room_id' => $koridor?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:14',
                'is_online' => true,
                'current_state' => ['motion' => false, 'last_motion' => '3 dakika önce'],
            ],

            // Güvenlik
            [
                'name' => 'Ana Kapı',
                'device_type_id' => $lock?->id,
                'room_id' => null,
                'mac_address' => 'AA:BB:CC:DD:EE:15',
                'is_online' => true,
                'current_state' => ['locked' => true, 'last_activity' => '14:32'],
            ],
            [
                'name' => 'Garaj Kapısı',
                'device_type_id' => $garage?->id,
                'room_id' => $garaj?->id,
                'mac_address' => 'AA:BB:CC:DD:EE:16',
                'is_online' => true,
                'current_state' => ['position' => 0, 'last_activity' => '09:15'],
            ],
            [
                'name' => 'Ev Alarmı',
                'device_type_id' => $alarm?->id,
                'room_id' => null,
                'mac_address' => 'AA:BB:CC:DD:EE:17',
                'is_online' => true,
                'current_state' => ['armed' => false, 'last_activity' => '08:00'],
            ],
        ];

        foreach ($devices as $device) {
            Device::updateOrCreate(
                ['dealer_id' => $dealerId, 'mac_address' => $device['mac_address']],
                array_merge($device, ['dealer_id' => $dealerId, 'is_active' => true])
            );
        }
    }
}
