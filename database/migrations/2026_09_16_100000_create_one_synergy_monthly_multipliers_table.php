<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('one_synergy_monthly_multipliers', function (Blueprint $table) {
            $table->id();
            $table->char('month', 7)->unique();
            $table->decimal('multiplier', 15, 2);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('one_synergy_monthly_multipliers');
    }
};
