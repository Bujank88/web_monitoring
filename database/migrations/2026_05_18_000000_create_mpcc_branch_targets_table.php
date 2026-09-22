<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpcc_branch_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('area')->nullable();
            $table->string('branch');
            $table->decimal('target_revenue_cluster_billion', 12, 2)->default(0);
            $table->decimal('target_revenue_branch_billion', 12, 2)->default(0);
            $table->unsignedInteger('target_visit')->default(0);
            $table->unsignedInteger('target_leads')->default(0);
            $table->unsignedInteger('target_registrasi')->default(0);
            $table->unsignedInteger('target_topup')->default(0);
            $table->timestamps();

            $table->unique(['year', 'month', 'branch'], 'mpcc_branch_targets_year_month_branch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpcc_branch_targets');
    }
};

