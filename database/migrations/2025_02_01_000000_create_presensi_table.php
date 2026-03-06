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
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('tanggal');
            $table->time('jam_datang')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->enum('status_datang', ['Hadir', 'Izin', 'Sakit', 'Terlambat'])->default('Hadir');
            $table->enum('status_pulang', ['Tepat Waktu', 'Pulang Awal', null])->nullable();
            $table->decimal('latitude_datang', 10, 8)->nullable();
            $table->decimal('longitude_datang', 11, 8)->nullable();
            $table->decimal('latitude_pulang', 10, 8)->nullable();
            $table->decimal('longitude_pulang', 11, 8)->nullable();
            $table->string('foto_datang')->nullable();
            $table->string('foto_pulang')->nullable();
            $table->text('keterangan')->nullable();
            $table->time('jam_kerja_awal')->default('08:00:00'); // Bisa dikonfigurasi
            $table->time('jam_kerja_akhir')->default('17:00:00');
            $table->timestamps();
            
            // Index untuk query yang lebih cepat
            $table->unique(['user_id', 'tanggal']);
            $table->index('tanggal');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
