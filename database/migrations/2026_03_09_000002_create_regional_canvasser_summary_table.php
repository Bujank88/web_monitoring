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
        Schema::create('regional_canvasser_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('canvasser_name');
            $table->string('regional')->nullable()->index();
            $table->string('bulan', 7)->index(); // Format: Y-m (e.g., 2026-03)
            
            // Metrics
            $table->integer('leads')->default(0);
            $table->integer('existing_akun')->default(0);
            $table->integer('new_akun')->default(0);
            $table->integer('top_up_new_akun_count')->default(0);
            $table->integer('top_up_existing_akun_count')->default(0);
            $table->decimal('top_up_new_akun_rp', 15, 2)->default(0);
            $table->decimal('top_up_existing_akun_rp', 15, 2)->default(0);
            $table->decimal('total_top_up_rp', 15, 2)->default(0);
            $table->decimal('target', 15, 2)->default(0);
            $table->decimal('achievement_percent', 8, 4)->default(0);
            $table->decimal('gap', 15, 2)->default(0);
            $table->decimal('gap_daily', 15, 2)->default(0);
            $table->integer('remaining_days')->default(0);
            
            // MoM Data
            $table->decimal('mom_prev_partial', 15, 2)->default(0);
            $table->decimal('mom_current_partial', 15, 2)->default(0);
            $table->decimal('mom_prev_remaining', 15, 2)->default(0);
            $table->decimal('mom_gap', 15, 2)->default(0);
            
            $table->timestamps();
            
            // Composite unique index untuk menghindari duplikasi
            $table->unique(['user_id', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regional_canvasser_summary');
    }
};
