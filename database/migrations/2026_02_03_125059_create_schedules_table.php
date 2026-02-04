<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->onDelete('cascade');
            $table->foreignId('device_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('scene_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->time('time'); // 07:00
            $table->json('days'); // ["mon", "tue", "wed", "thu", "fri"] veya ["everyday"] veya ["weekend"]
            $table->string('command_slug')->nullable(); // turn_on, turn_off
            $table->json('params')->nullable(); // {"brightness": 50}
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
