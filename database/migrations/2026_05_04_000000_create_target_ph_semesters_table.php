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
        Schema::create('target_ph_semesters', function (Blueprint $table) {
            $table->id();
            $table->string('team_powerhouse');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('semester');
            $table->decimal('target', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['team_powerhouse', 'year', 'semester'], 'target_ph_semesters_team_year_semester_unique');
            $table->index(['year', 'semester'], 'target_ph_semesters_year_semester_index');
            $table->index('team_powerhouse', 'target_ph_semesters_team_powerhouse_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_ph_semesters');
    }
};
