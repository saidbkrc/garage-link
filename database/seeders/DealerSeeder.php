<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\DealerUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DealerSeeder extends Seeder
{
    public function run(): void
    {
        // Demo Bayi oluştur
        $dealer = Dealer::updateOrCreate(
            ['email' => 'demo@garagelink.com'],
            [
                'name' => 'Demo Bayi',
                'email' => 'demo@garagelink.com',
                'phone' => '0555 123 4567',
                'address' => 'İstanbul, Türkiye',
                'is_active' => true,
            ]
        );

        // Demo kullanıcı oluştur
        DealerUser::updateOrCreate(
            ['email' => 'admin@garagelink.com'],
            [
                'dealer_id' => $dealer->id,
                'name' => 'Admin User',
                'email' => 'admin@garagelink.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
    }
}
