<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan index pada table data_registarsi_status_approveorreject
     * untuk meningkatkan performa query terutama pada:
     * - JOIN dengan leads_master, report_balance_top_up (email)
     * - WHERE status = 'APPROVE'
     * - WHERE/BETWEEN tanggal_approval_aktivasi
     * - JOIN regional mapping (provinsi)
     */
    public function up(): void
    {
        Schema::table('data_registarsi_status_approveorreject', function (Blueprint $table) {
            // Index untuk kolom email (paling sering di-JOIN)
            $table->index('email', 'idx_email');
            
            // Index untuk kolom status (sering di-filter WHERE status = 'APPROVE')
            $table->index('status', 'idx_status');
            
            // Index untuk kolom tanggal_approval_aktivasi (sering di-filter dengan date range)
            $table->index('tanggal_approval_aktivasi', 'idx_tanggal_approval');
            
            // Index untuk kolom provinsi (digunakan untuk mapping regional)
            $table->index('provinsi', 'idx_provinsi');
            
            // Composite index untuk query yang menggunakan kombinasi email + status + tanggal
            // Ini akan sangat membantu query di RefreshRegionalCanvasserSummary dan BackController
            $table->index(['email', 'status', 'tanggal_approval_aktivasi'], 'idx_email_status_tanggal');
            
            // Composite index untuk user_id + status (jika ada query group by user_id)
            $table->index(['user_id', 'status'], 'idx_user_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_registarsi_status_approveorreject', function (Blueprint $table) {
            $table->dropIndex('idx_email');
            $table->dropIndex('idx_status');
            $table->dropIndex('idx_tanggal_approval');
            $table->dropIndex('idx_provinsi');
            $table->dropIndex('idx_email_status_tanggal');
            $table->dropIndex('idx_user_status');
        });
    }
};
