<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_type_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('mac_address')->unique();
            $table->string('mqtt_topic')->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->json('settings')->nullable();
            $table->json('current_state')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
