<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['name' => 'Salon', 'icon' => 'living', 'color' => '#3B82F6', 'order' => 1],
            ['name' => 'Yatak Odası', 'icon' => 'bed', 'color' => '#8B5CF6', 'order' => 2],
            ['name' => 'Mutfak', 'icon' => 'kitchen', 'color' => '#F59E0B', 'order' => 3],
            ['name' => 'Banyo', 'icon' => 'bathroom', 'color' => '#06B6D4', 'order' => 4],
            ['name' => 'Ofis', 'icon' => 'desktop_windows', 'color' => '#10B981', 'order' => 5],
            ['name' => 'Koridor', 'icon' => 'hallway', 'color' => '#6B7280', 'order' => 6],
            ['name' => 'Garaj', 'icon' => 'garage', 'color' => '#EF4444', 'order' => 7],
        ];

        foreach ($rooms as $room) {
            Room::updateOrCreate(
                ['dealer_id' => 1, 'name' => $room['name']],
                array_merge($room, ['dealer_id' => 1])
            );
        }
    }
}
