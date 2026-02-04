<?php

namespace Database\Seeders;

use App\Models\Scene;
use Illuminate\Database\Seeder;

class SceneSeeder extends Seeder
{
    public function run(): void
    {
        $scenes = [
            [
                'name' => 'Eve Geldim',
                'icon' => 'home',
                'color' => '#10B981',
                'actions' => [
                    ['device_id' => 1, 'command' => 'turn_on', 'params' => ['brightness' => 80]],
                    ['device_id' => 3, 'command' => 'set_position', 'params' => ['position' => 50]],
                    ['device_id' => 15, 'command' => 'unlock', 'params' => []],
                ],
            ],
            [
                'name' => 'Evden Çıkıyorum',
                'icon' => 'directions_walk',
                'color' => '#F59E0B',
                'actions' => [
                    ['device_id' => 1, 'command' => 'turn_off', 'params' => []],
                    ['device_id' => 2, 'command' => 'turn_off', 'params' => []],
                    ['device_id' => 3, 'command' => 'set_position', 'params' => ['position' => 0]],
                    ['device_id' => 15, 'command' => 'lock', 'params' => []],
                    ['device_id' => 17, 'command' => 'arm', 'params' => []],
                ],
            ],
            [
                'name' => 'Uyku Modu',
                'icon' => 'bedtime',
                'color' => '#8B5CF6',
                'actions' => [
                    ['device_id' => 1, 'command' => 'turn_off', 'params' => []],
                    ['device_id' => 6, 'command' => 'turn_on', 'params' => ['brightness' => 10]],
                    ['device_id' => 8, 'command' => 'turn_on', 'params' => ['target_temp' => 22, 'mode' => 'cool']],
                    ['device_id' => 9, 'command' => 'set_position', 'params' => ['position' => 0]],
                ],
            ],
            [
                'name' => 'Film Modu',
                'icon' => 'movie',
                'color' => '#EF4444',
                'actions' => [
                    ['device_id' => 1, 'command' => 'turn_on', 'params' => ['brightness' => 20, 'color' => '#4338CA']],
                    ['device_id' => 3, 'command' => 'set_position', 'params' => ['position' => 0]],
                    ['device_id' => 4, 'command' => 'turn_on', 'params' => []],
                ],
            ],
        ];

        foreach ($scenes as $scene) {
            Scene::updateOrCreate(
                ['dealer_id' => 1, 'name' => $scene['name']],
                array_merge($scene, ['dealer_id' => 1])
            );
        }
    }
}
