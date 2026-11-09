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
        Schema::create('region_target', function (Blueprint $table) {
            $table->id();
            $table->string('region_name');
            $table->string('pic');
            $table->date('date');
            $table->integer('target_amount');
            $table->enum('data_type', ['PowerHouse', 'Mitra SBP', 'Agency', 'Internal']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('region_target');
    }
};
