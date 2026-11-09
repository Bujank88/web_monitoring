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
        Schema::create('creator_partner', function (Blueprint $table) {
            $table->id();
            $table->string('area')->nullable();
            $table->string('regional')->nullable();
            $table->string('jenis_kol')->nullable();
            $table->string('nama_kol')->nullable();
            $table->string('email_kol')->nullable();
            $table->string('no_hp_kol')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_partner');
    }
};
