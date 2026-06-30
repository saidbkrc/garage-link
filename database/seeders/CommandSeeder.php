<?php

namespace Database\Seeders;

use App\Models\Command;
use Illuminate\Database\Seeder;

class CommandSeeder extends Seeder
{
    public function run(): void
    {
        // NOT: Bu komutlar ESKİ düz protokol formatında (turn_on/brightness/color + ieee_addr).
        // Yeni PIYA protokolüne (GatewayCommandBuilder) geçildiğinde yeniden yazılacak
        // (entegrasyon firmware cevabı bekliyor — bkz. görev #6/#7).
        $commands = [
            // TEMEL KOMUTLAR
            // topic: pigasoft/{gateway_id}/commands — gateway_id runtime'da MqttService tarafından eklenir
            [
                'name'             => 'Durum Sorgula',
                'slug'             => 'get_state',
                'category'         => 'basic',
                'mqtt_topic'       => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['get_state' => true, 'ieee_addr' => '{ieee_addr}'],
                'required_params'  => ['ieee_addr'],
                'description'      => 'Cihazın gerçek anlık durumunu sorgular; cihaz data topiğiyle cevap verir',
                'order'            => 0,
            ],
            [
                'name' => 'Işığı Aç',
                'slug' => 'turn_on',
                'category' => 'basic',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['turn_on' => true, 'ieee_addr' => '{ieee_addr}'],
                'required_params' => ['ieee_addr'],
                'description' => 'Belirtilen cihazı açar',
                'order' => 1,
            ],
            [
                'name' => 'Işığı Kapat',
                'slug' => 'turn_off',
                'category' => 'basic',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['turn_off' => true, 'ieee_addr' => '{ieee_addr}'],
                'required_params' => ['ieee_addr'],
                'description' => 'Belirtilen cihazı kapatır',
                'order' => 2,
            ],
            [
                'name' => 'Toggle',
                'slug' => 'toggle',
                'category' => 'basic',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['toggle' => true, 'ieee_addr' => '{ieee_addr}'],
                'required_params' => ['ieee_addr'],
                'description' => 'Cihazın durumunu değiştirir',
                'order' => 3,
            ],
            [
                'name' => 'Parlaklık Ayarla',
                'slug' => 'brightness',
                'category' => 'basic',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['brightness' => '{brightness}', 'ieee_addr' => '{ieee_addr}'],
                'required_params' => ['brightness', 'ieee_addr'],
                'description' => 'Parlaklık seviyesi (0-100)',
                'order' => 4,
            ],

            // GRUP KOMUTLARI
            [
                'name' => 'Tümünü Aç',
                'slug' => 'group_all_on',
                'category' => 'group',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['group_all_on' => true],
                'description' => 'Tüm cihazları açar',
                'order' => 10,
            ],
            [
                'name' => 'Tümünü Kapat',
                'slug' => 'group_all_off',
                'category' => 'group',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['group_all_off' => true],
                'description' => 'Tüm cihazları kapatır',
                'order' => 11,
            ],
            [
                'name' => 'Tümüne Parlaklık',
                'slug' => 'group_all_brightness',
                'category' => 'group',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
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
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                // firmware "rgb(R, G, B)" string bekliyor
                'payload_template' => ['color' => '{color}', 'ieee_addr' => '{ieee_addr}'],
                'required_params' => ['color', 'ieee_addr'],
                'description' => 'RGB renk değeri gönderir — "rgb(255, 0, 0)" formatında',
                'order' => 20,
            ],
            [
                'name' => 'Renk + Parlaklık',
                'slug' => 'color_brightness',
                'category' => 'color',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['color' => '{color}', 'brightness' => '{brightness}', 'ieee_addr' => '{ieee_addr}'],
                'required_params' => ['color', 'brightness', 'ieee_addr'],
                'description' => 'Renk ve parlaklık birlikte ayarlar',
                'order' => 21,
            ],
            [
                'name' => 'Renk Sıcaklığı',
                'slug' => 'color_temperature',
                'category' => 'color',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['color_temperature' => '{temperature}', 'ieee_addr' => '{ieee_addr}'],
                'required_params' => ['temperature', 'ieee_addr'],
                'description' => 'Renk sıcaklığı (0-255)',
                'order' => 22,
            ],

            // SİSTEM KOMUTLARI
            [
                'name' => 'Health Bilgisi',
                'slug' => 'get_health',
                'category' => 'system',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['get_health' => true],
                'response_topic' => 'pigasoft/+/health',
                'description' => 'Sistem sağlık bilgisi',
                'order' => 30,
            ],
            [
                'name' => 'Cihaz Listesi Al',
                'slug' => 'get_devicelist',
                'category' => 'system',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['get_devicelist' => true],
                'response_topic' => 'pigasoft/+/devicelist',
                'description' => 'Gateway bağlı cihaz listesini döner',
                'order' => 31,
            ],

            // TARAMA KOMUTLARI
            [
                'name' => 'Permit Join',
                'slug' => 'permit_join',
                'category' => 'scan',
                'mqtt_topic' => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['permit_join' => '{duration}'],
                'optional_params' => ['duration'],
                'response_topic' => 'pigasoft/+/permitjoin',
                'description' => 'Cihaz eşleştirme izni (saniye, varsayılan 60)',
                'order' => 40,
            ],

            // RÖLE KOMUTLARI
            [
                'name'             => 'Röle Kanalı Aç',
                'slug'             => 'relay_turn_on',
                'category'         => 'relay',
                'mqtt_topic'       => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['turn_on' => true, 'ieee_addr' => '{ieee_addr}', 'endpoint' => '{endpoint}'],
                'required_params'  => ['ieee_addr', 'endpoint'],
                'description'      => 'Belirtilen röle kanalını (endpoint) açar',
                'order'            => 50,
            ],
            [
                'name'             => 'Röle Kanalı Kapat',
                'slug'             => 'relay_turn_off',
                'category'         => 'relay',
                'mqtt_topic'       => 'pigasoft/{gateway_id}/commands',
                'payload_template' => ['turn_off' => true, 'ieee_addr' => '{ieee_addr}', 'endpoint' => '{endpoint}'],
                'required_params'  => ['ieee_addr', 'endpoint'],
                'description'      => 'Belirtilen röle kanalını (endpoint) kapatır',
                'order'            => 51,
            ],
        ];

        foreach ($commands as $command) {
            Command::updateOrCreate(['slug' => $command['slug']], $command);
        }
    }
}