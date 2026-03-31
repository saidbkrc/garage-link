<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dealer_id nullable yap + gateway hello payload metadata sütunları ekle.
     *
     * Neden nullable?
     *   Claim sistemi: MQTT'de yeni gateway tespit edildiğinde dealer_id olmadan
     *   kaydedilir (NULL). Bayi panelden sahiplenir. Önceki migration'da nullable()
     *   unutulmuştu → yeni gateway INSERT denerken NOT NULL constraint violation
     *   fırlatıyor, subscriber crash ediyordu.
     */
    public function up(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            // dealer_id → NULL izni ver
            $table->foreignId('dealer_id')->nullable()->change();

            // Gateway hello payload'dan gelen metadata
            $table->string('ip_address', 45)->nullable()->after('name');
            $table->string('firmware_version', 50)->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'firmware_version']);
            $table->foreignId('dealer_id')->nullable(false)->change();
        });
    }
};
