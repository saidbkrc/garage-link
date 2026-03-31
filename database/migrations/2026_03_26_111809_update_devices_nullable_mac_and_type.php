<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * mac_address ve device_type_id sütunlarını nullable yap.
     *
     * Neden?
     *   MQTT üzerinden auto-discover edilen cihazlar mac_address bilgisi olmadan
     *   gelir (ieee_addr kullanılır). device_type_id ise resolveDeviceType() ile
     *   çözülür ama eşleşme bulunamazsa null döner. NOT NULL kısıtlamaları
     *   auto-creation'ı engelliyor.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('mac_address')->nullable()->change();
            $table->foreignId('device_type_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('mac_address')->nullable(false)->change();
            $table->foreignId('device_type_id')->nullable(false)->change();
        });
    }
};
