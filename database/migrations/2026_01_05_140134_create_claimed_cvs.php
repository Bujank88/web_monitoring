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
        Schema::create('claimed_cvs', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('regional')->nullable();
            $table->string('area')->nullable();
            $table->string('canvasser');
            $table->string('nama')->nullable();
            $table->string('no_telp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimed_cvs');
    }
};
