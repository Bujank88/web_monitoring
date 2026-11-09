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
        Schema::create('mitra_sbp', function (Blueprint $table) {
            $table->id();
            $table->string('email_myads')->index();
            $table->string('area')->nullable();
            $table->string('regional')->nullable();
            $table->string('remark')->nullable();
            $table->string('voucher')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mitra_sbp');
    }
};
