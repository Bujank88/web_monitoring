<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads_canvasser_sbp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referral_id')->nullable();
            $table->string('referral_code', 50);
            $table->string('referral_name');
            $table->string('company_name');
            $table->string('email_myads');
            $table->string('mobile_phone', 50);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('referral_id');
            $table->index('referral_code');
            $table->index('email_myads');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads_canvasser_sbp');
    }
};
