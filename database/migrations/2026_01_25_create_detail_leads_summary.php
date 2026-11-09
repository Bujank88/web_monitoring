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
        if (!Schema::hasTable('detail_leads_summary')) {
            Schema::create('detail_leads_summary', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('leads_master_id')->unique()->indexed();
                
                // Data dari leads_master
                $table->integer('user_id');
                $table->string('user_name')->nullable();
                $table->integer('source_id')->nullable();
                $table->integer('sector_id');
                $table->string('regional')->nullable();
                $table->string('company_name');
                $table->string('mobile_phone');
                $table->string('email')->nullable();
                $table->integer('status')->default(0);
                $table->string('nama')->nullable();
                $table->text('address')->nullable();
                $table->string('myads_account')->nullable();
                $table->string('data_type');
                $table->decimal('komitmen', 5, 2)->default(0);
                $table->integer('plan_min_topup')->default(0);
                $table->text('remarks')->nullable();
                
                // Data dari report_balance_top_up (bulan/tahun sekarang)
                $table->decimal('total_settlement_klien', 15, 2)->default(0);
                
                // Timestamps
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
                
                // Indexes untuk query yang sering dipakai
                $table->index('user_id');
                $table->index('regional');
                $table->index('email');
                $table->index('created_at');
                $table->index('data_type');
                $table->index('total_settlement_klien');
                
                // Foreign key
                $table->foreign('leads_master_id')->references('id')->on('leads_master')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_leads_summary');
    }
};
