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
        // Index untuk table users
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_role_index')) {
                $table->index('role', 'users_role_index');
            }
        });

        // Index untuk table leads_master
        Schema::table('leads_master', function (Blueprint $table) {
            if (!$this->indexExists('leads_master', 'leads_master_user_id_index')) {
                $table->index('user_id', 'leads_master_user_id_index');
            }
            if (!$this->indexExists('leads_master', 'leads_master_email_index')) {
                $table->index('email', 'leads_master_email_index');
            }
            if (!$this->indexExists('leads_master', 'leads_master_data_type_index')) {
                $table->index('data_type', 'leads_master_data_type_index');
            }
            if (!$this->indexExists('leads_master', 'leads_master_regional_index')) {
                $table->index('regional', 'leads_master_regional_index');
            }
        });

        // Index untuk table logbook
        Schema::table('logbook', function (Blueprint $table) {
            if (!$this->indexExists('logbook', 'logbook_user_id_index')) {
                $table->index('user_id', 'logbook_user_id_index');
            }
            if (!$this->indexExists('logbook', 'logbook_leads_master_id_index')) {
                $table->index('leads_master_id', 'logbook_leads_master_id_index');
            }
            if (!$this->indexExists('logbook', 'logbook_bulan_tahun_index')) {
                $table->index(['bulan', 'tahun'], 'logbook_bulan_tahun_index');
            }
        });

        // Index untuk table report_balance_top_up
        Schema::table('report_balance_top_up', function (Blueprint $table) {
            if (!$this->indexExists('report_balance_top_up', 'report_balance_top_up_email_client_index')) {
                $table->index('email_client', 'report_balance_top_up_email_client_index');
            }
            if (!$this->indexExists('report_balance_top_up', 'report_balance_top_up_tgl_transaksi_index')) {
                $table->index('tgl_transaksi', 'report_balance_top_up_tgl_transaksi_index');
            }
        });

        // Index untuk table data_registarsi_status_approveorreject
        Schema::table('data_registarsi_status_approveorreject', function (Blueprint $table) {
            if (!$this->indexExists('data_registarsi_status_approveorreject', 'data_reg_email_index')) {
                $table->index('email', 'data_reg_email_index');
            }
            if (!$this->indexExists('data_registarsi_status_approveorreject', 'data_reg_status_index')) {
                $table->index('status', 'data_reg_status_index');
            }
            if (!$this->indexExists('data_registarsi_status_approveorreject', 'data_reg_tanggal_approval_index')) {
                $table->index('tanggal_approval_aktivasi', 'data_reg_tanggal_approval_index');
            }
        });

        // Index untuk table target_canvaser
        Schema::table('target_canvaser', function (Blueprint $table) {
            if (!$this->indexExists('target_canvaser', 'target_canvaser_user_id_bulan_index')) {
                $table->index(['user_id', 'bulan'], 'target_canvaser_user_id_bulan_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
        });

        Schema::table('leads_master', function (Blueprint $table) {
            $table->dropIndex('leads_master_user_id_index');
            $table->dropIndex('leads_master_email_index');
            $table->dropIndex('leads_master_data_type_index');
            $table->dropIndex('leads_master_regional_index');
        });

        Schema::table('logbook', function (Blueprint $table) {
            $table->dropIndex('logbook_user_id_index');
            $table->dropIndex('logbook_leads_master_id_index');
            $table->dropIndex('logbook_bulan_tahun_index');
        });

        Schema::table('report_balance_top_up', function (Blueprint $table) {
            $table->dropIndex('report_balance_top_up_email_client_index');
            $table->dropIndex('report_balance_top_up_tgl_transaksi_index');
        });

        Schema::table('data_registarsi_status_approveorreject', function (Blueprint $table) {
            $table->dropIndex('data_reg_email_index');
            $table->dropIndex('data_reg_status_index');
            $table->dropIndex('data_reg_tanggal_approval_index');
        });

        Schema::table('target_canvaser', function (Blueprint $table) {
            $table->dropIndex('target_canvaser_user_id_bulan_index');
        });
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $index): bool
    {
        $connection = Schema::getConnection();
        $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
        $doctrineTable = $doctrineSchemaManager->listTableDetails($table);
        return $doctrineTable->hasIndex($index);
    }
};
