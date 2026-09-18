<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maxim_reports', function (Blueprint $table) {
            $table->id();
            $table->string('id_iklan')->unique();
            $table->date('tgl_tayang')->nullable();
            $table->string('judul_pesan_iklan')->nullable();
            $table->string('operator_seluler')->nullable();
            $table->string('kategori_iklan')->nullable();
            $table->string('tipe_kanal')->nullable();
            $table->text('detil_status')->nullable();
            $table->unsignedBigInteger('sukses')->default(0);
            $table->unsignedBigInteger('gagal')->default(0);
            $table->unsignedBigInteger('total_harga')->default(0);
            $table->string('source_file_name')->nullable();
            $table->string('upload_batch')->nullable();
            $table->timestamps();

            $table->index('tgl_tayang');
            $table->index('kategori_iklan');
            $table->index('tipe_kanal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maxim_reports');
    }
};
