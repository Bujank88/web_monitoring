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
        Schema::create('sultam_racing', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_akun')->nullable();
            $table->string('nama_akun')->nullable();
            $table->string('area')->nullable();
            $table->string('email')->nullable();
            $table->string('nama_am')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sultam_racing');
    }
};
