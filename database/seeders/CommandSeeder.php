<?php

namespace Database\Seeders;

use App\Models\Command;
use Illuminate\Database\Seeder;

class CommandSeeder extends Seeder
{
    public function run(): void
    {
        $commands = [
            // TEMEL KOMUTLAR
            [
                'name' => 'Işığı Aç',
                'slug' => 'turn_on',
                'category' => 'basic',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['turn_on' => true, 'device_index' => '{device_index}'],
                'required_params' => ['device_index'],
                'description' => 'Belirtilen cihazı açar',
                'order' => 1,
            ],
            [
                'name' => 'Işığı Kapat',
                'slug' => 'turn_off',
                'category' => 'basic',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['turn_off' => true, 'device_index' => '{device_index}'],
                'required_params' => ['device_index'],
                'description' => 'Belirtilen cihazı kapatır',
                'order' => 2,
            ],
            [
                'name' => 'Toggle',
                'slug' => 'toggle',
                'category' => 'basic',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['toggle' => true, 'device_index' => '{device_index}'],
                'required_params' => ['device_index'],
                'description' => 'Cihazın durumunu değiştirir',
                'order' => 3,
            ],
            [
                'name' => 'Parlaklık Ayarla',
                'slug' => 'brightness',
                'category' => 'basic',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['brightness' => '{brightness}'],
                'required_params' => ['brightness'],
                'description' => 'Parlaklık seviyesi (0-100)',
                'order' => 4,
            ],

            // GRUP KOMUTLARI
            [
                'name' => 'Tümünü Aç',
                'slug' => 'group_all_on',
                'category' => 'group',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['group_all_on' => true],
                'description' => 'Tüm cihazları açar',
                'order' => 10,
            ],
            [
                'name' => 'Tümünü Kapat',
                'slug' => 'group_all_off',
                'category' => 'group',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['group_all_off' => true],
                'description' => 'Tüm cihazları kapatır',
                'order' => 11,
            ],
            [
                'name' => 'Tümüne Parlaklık',
                'slug' => 'group_all_brightness',
                'category' => 'group',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['group_all_brightness' => '{brightness}'],
                'required_params' => ['brightness'],
                'description' => 'Tüm cihazlara parlaklık gönderir',
                'order' => 12,
            ],

            // RENK KOMUTLARI
            [
                'name' => 'Renk Ayarla',
                'slug' => 'color',
                'category' => 'color',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['color' => ['r' => '{r}', 'g' => '{g}', 'b' => '{b}']],
                'required_params' => ['r', 'g', 'b'],
                'description' => 'RGB renk değeri gönderir',
                'order' => 20,
            ],
            [
                'name' => 'Renk Sıcaklığı',
                'slug' => 'color_temperature',
                'category' => 'color',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['color_temperature' => '{temperature}', 'device_index' => '{device_index}'],
                'required_params' => ['temperature'],
                'optional_params' => ['device_index'],
                'description' => 'Renk sıcaklığı (0-255)',
                'order' => 21,
            ],

            // SİSTEM KOMUTLARI
            [
                'name' => 'Saat Bilgisi Al',
                'slug' => 'get_time',
                'category' => 'system',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['get_time' => true],
                'response_topic' => 'pigasoft/data',
                'description' => 'Gateway sistem saatini döner',
                'order' => 30,
            ],
            [
                'name' => 'Health Bilgisi',
                'slug' => 'get_health',
                'category' => 'system',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['get_health' => true],
                'response_topic' => 'pigasoft/health',
                'description' => 'Sistem sağlık bilgisi',
                'order' => 31,
            ],

            // TARAMA KOMUTLARI
            [
                'name' => 'Cihaz Tara',
                'slug' => 'scan',
                'category' => 'scan',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['scan' => '{duration}'],
                'optional_params' => ['duration'],
                'response_topic' => 'pigasoft/scan_mode',
                'description' => 'Cihaz taraması başlatır',
                'order' => 40,
            ],
            [
                'name' => 'Permit Join',
                'slug' => 'permit_join',
                'category' => 'scan',
                'mqtt_topic' => 'pigasoft/commands',
                'payload_template' => ['permit_join' => '{duration}'],
                'optional_params' => ['duration'],
                'response_topic' => 'pigasoft/permitjoin',
                'description' => 'Cihaz eşleştirme izni',
                'order' => 41,
            ],
        ];

        foreach ($commands as $command) {
            Command::create($command);
        }
    }
}