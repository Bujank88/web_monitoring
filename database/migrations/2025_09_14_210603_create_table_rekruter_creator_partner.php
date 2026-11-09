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
        Schema::create('rekruter_kol', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->nullable(); // Nama
            $table->string('email')->nullable(); // Email
            $table->string('no_hp')->nullable(); // No. Telp
            $table->string('referral_code')->nullable(); // Referral Code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekruter_kol');
    }
};
