<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fbm_sof', function (Blueprint $table) {
            $table->id();
            $table->string('sender_name');
            $table->string('nomor_wa', 50);
            $table->string('pic');
            $table->enum('verif_bisnis', ['Yes', 'On Progress', 'No']);
            $table->enum('credit_line', ['Yes', 'On Progress', 'No']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fbm_sof');
    }
};
