<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DealerSeeder::class,      // ← Önce bu çalışsın
            DeviceTypeSeeder::class,
            RoomSeeder::class,
            DeviceSeeder::class,
            SceneSeeder::class,
        ]);
    }
}
