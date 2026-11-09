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
        Schema::create('referral_champion_am', function (Blueprint $table) {
            $table->id();
            $table->string('nama_tele_am')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('email')->nullable();
            $table->string('username_company_myads')->nullable();
            $table->string('username')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_champion_am');
    }
};
