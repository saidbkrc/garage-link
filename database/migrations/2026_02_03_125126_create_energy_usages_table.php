<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('date');
            $table->tinyInteger('hour')->nullable(); // 0-23 (saatlik kayıt için)
            $table->decimal('power_watt', 10, 2)->default(0); // Anlık güç (W)
            $table->decimal('energy_kwh', 10, 4)->default(0); // Tüketim (kWh)
            $table->timestamps();

            $table->index(['dealer_id', 'date']);
            $table->index(['device_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_usages');
    }
};
