<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpcc_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('area')->nullable();
            $table->string('branch')->nullable();
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'year', 'month'], 'mpcc_targets_user_year_month_unique');
            $table->index(['year', 'month'], 'mpcc_targets_year_month_index');
            $table->index('branch', 'mpcc_targets_branch_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpcc_targets');
    }
};
