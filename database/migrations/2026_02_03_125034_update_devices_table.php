<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->json('capabilities')->nullable()->after('current_state');
            $table->json('config')->nullable()->after('capabilities');
            $table->decimal('energy_today', 10, 2)->default(0)->after('config');
            $table->decimal('energy_total', 10, 2)->default(0)->after('energy_today');
            $table->timestamp('state_updated_at')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropColumn(['room_id', 'current_state', 'capabilities', 'config', 'energy_today', 'energy_total', 'state_updated_at']);
        });
    }
};
