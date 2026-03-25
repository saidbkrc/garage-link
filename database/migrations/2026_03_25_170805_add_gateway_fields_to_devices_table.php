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
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('gateway_db_id')->nullable()->constrained('gateways')->nullOnDelete()->after('dealer_id');
            $table->string('ieee_addr')->nullable()->unique()->after('mac_address'); // CCD8C2FEFFEEF648
            $table->integer('device_index')->nullable()->after('ieee_addr');         // Gateway içindeki sıra no
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['gateway_db_id']);
            $table->dropColumn(['gateway_db_id', 'ieee_addr', 'device_index']);
        });
    }
};
