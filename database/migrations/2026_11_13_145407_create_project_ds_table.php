<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_ds')) {
            return;
        }

        Schema::create('project_ds', function (Blueprint $table) {
            $table->id();
            $table->string('kode_ds', 50)->nullable();
            $table->string('nama');
            $table->string('city')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('email');
            $table->enum('status_akun', ['Aktif', 'Belum Aktif'])->default('Aktif');
            $table->string('kode_referal')->nullable();
            $table->string('no_rek')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ds');
    }
};