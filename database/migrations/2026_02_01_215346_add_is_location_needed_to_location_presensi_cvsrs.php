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
        Schema::table('location_presensi_cvsr', function (Blueprint $table) {
            $table->boolean('is_location_needed')->default(true)->after('keterangan')->comment('Status apakah pengecekan lokasi diperlukan saat presensi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_presensi_cvsr', function (Blueprint $table) {
            $table->dropColumn('is_location_needed');
        });
    }
};
