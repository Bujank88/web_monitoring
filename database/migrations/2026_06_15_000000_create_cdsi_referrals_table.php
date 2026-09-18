<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cdsi_referrals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('referral_code', 50)->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cdsi_referrals');
    }
};
