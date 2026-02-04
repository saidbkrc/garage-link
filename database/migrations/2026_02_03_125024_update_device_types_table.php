<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_types', function (Blueprint $table) {
            $table->string('category')->after('slug')->default('other'); // lighting, climate, curtain, sensor, security, plug
            $table->json('capabilities')->nullable()->after('icon'); // ["power", "brightness", "color", "temperature"]
            $table->json('default_state')->nullable()->after('capabilities'); // Varsayılan durum
            $table->json('config_schema')->nullable()->after('default_state'); // Ayar şeması
        });
    }

    public function down(): void
    {
        Schema::table('device_types', function (Blueprint $table) {
            $table->dropColumn(['category', 'capabilities', 'default_state', 'config_schema']);
        });
    }
};
