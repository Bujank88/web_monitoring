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
        Schema::table('presensi', function (Blueprint $table) {
            $table->boolean('is_sent_clock_in')->nullable()->after('foto_datang')->comment('Status pengiriman notifikasi clock in (null=belum, true=sukses, false=gagal)');
            $table->boolean('is_sent_clock_out')->nullable()->after('is_sent_clock_in')->comment('Status pengiriman notifikasi clock out (null=belum, true=sukses, false=gagal)');
            $table->boolean('is_sent_izin')->nullable()->after('is_sent_clock_out')->comment('Status pengiriman notifikasi izin/sakit (null=belum, true=sukses, false=gagal)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->dropColumn(['is_sent_clock_in', 'is_sent_clock_out', 'is_sent_izin']);
        });
    }
};
