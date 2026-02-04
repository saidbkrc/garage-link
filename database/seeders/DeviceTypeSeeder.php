<?php

namespace Database\Seeders;

use App\Models\DeviceType;
use Illuminate\Database\Seeder;

class DeviceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Aydınlatma
            [
                'name' => 'RGB LED Şerit',
                'slug' => 'led_strip',
                'category' => 'lighting',
                'icon' => 'light',
                'capabilities' => ['power', 'brightness', 'color'],
                'default_state' => ['power' => false, 'brightness' => 100, 'color' => '#FFFFFF'],
            ],
            [
                'name' => 'Akıllı Ampul',
                'slug' => 'bulb',
                'category' => 'lighting',
                'icon' => 'lightbulb',
                'capabilities' => ['power', 'brightness', 'temperature'],
                'default_state' => ['power' => false, 'brightness' => 100, 'temperature' => 50],
            ],
            [
                'name' => 'Duvar Anahtarı',
                'slug' => 'switch',
                'category' => 'lighting',
                'icon' => 'toggle_on',
                'capabilities' => ['power', 'channels'],
                'default_state' => ['power' => false, 'channels' => []],
                'config_schema' => ['channel_count' => 4],
            ],

            // İklimlendirme
            [
                'name' => 'Klima',
                'slug' => 'climate_ac',
                'category' => 'climate',
                'icon' => 'ac_unit',
                'capabilities' => ['power', 'temperature', 'mode', 'fan_speed'],
                'default_state' => ['power' => false, 'target_temp' => 22, 'mode' => 'cool', 'fan_speed' => 'auto'],
                'config_schema' => ['min_temp' => 16, 'max_temp' => 30],
            ],

            // Perde & Motor
            [
                'name' => 'Perde Motoru',
                'slug' => 'curtain',
                'category' => 'curtain',
                'icon' => 'blinds',
                'capabilities' => ['position', 'open', 'close', 'stop'],
                'default_state' => ['position' => 0],
            ],

            // Priz
            [
                'name' => 'Akıllı Priz',
                'slug' => 'plug',
                'category' => 'plug',
                'icon' => 'power',
                'capabilities' => ['power', 'energy_monitoring'],
                'default_state' => ['power' => false],
            ],

            // Sensörler
            [
                'name' => 'Sıcaklık Sensörü',
                'slug' => 'sensor_temperature',
                'category' => 'sensor',
                'icon' => 'thermostat',
                'capabilities' => ['temperature', 'humidity'],
                'default_state' => ['temperature' => 0, 'humidity' => 0],
            ],
            [
                'name' => 'Hareket Sensörü',
                'slug' => 'sensor_motion',
                'category' => 'sensor',
                'icon' => 'motion_sensor_active',
                'capabilities' => ['motion', 'occupancy'],
                'default_state' => ['motion' => false],
            ],

            // Güvenlik
            [
                'name' => 'Kapı Kilidi',
                'slug' => 'lock',
                'category' => 'security',
                'icon' => 'lock',
                'capabilities' => ['lock', 'unlock'],
                'default_state' => ['locked' => true],
            ],
            [
                'name' => 'Garaj Kapısı',
                'slug' => 'garage',
                'category' => 'security',
                'icon' => 'garage',
                'capabilities' => ['open', 'close', 'position'],
                'default_state' => ['position' => 0],
            ],
            [
                'name' => 'Alarm Sistemi',
                'slug' => 'alarm',
                'category' => 'security',
                'icon' => 'crisis_alert',
                'capabilities' => ['arm', 'disarm', 'trigger'],
                'default_state' => ['armed' => false],
            ],
        ];

        foreach ($types as $type) {
            DeviceType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
