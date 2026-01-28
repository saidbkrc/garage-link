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
        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_type_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');                          // Görünen isim: "Işığı Aç"
            $table->string('slug')->unique();                // Benzersiz kod: "turn_on"
            $table->string('category');                      // Kategori: "basic", "group", "scheduler", "scene", "color", "system"
            $table->string('mqtt_topic')->default('pigasoft/commands');  // Gönderilecek topic
            $table->json('payload_template');                // JSON şablon: {"turn_on": true, "device_index": "{device_index}"}
            $table->json('required_params')->nullable();     // Zorunlu parametreler: ["device_index"]
            $table->json('optional_params')->nullable();     // Opsiyonel parametreler: ["brightness"]
            $table->string('response_topic')->nullable();    // Cevap topic'i: "pigasoft/data"
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};
