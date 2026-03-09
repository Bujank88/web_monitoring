<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan indexes penting untuk optimasi performa query leads_master
     * Tabel ini sering di-query dengan filter email, user_id, regional, dll
     */
    public function up(): void
    {
        Schema::table('leads_master', function (Blueprint $table) {
            // 1. Index untuk kolom email (PALING PENTING!)
            // Digunakan untuk:
            // - Join dengan report_balance_top_up (email_client)
            // - Join dengan data_registarsi_status_approveorreject
            // - WHERE email = ...
            // - Cek duplicate email
            if (!$this->indexExists('leads_master', 'idx_email')) {
                $table->index('email', 'idx_email');
            }

            // 2. Index untuk user_id (foreign key ke users)
            // Digunakan untuk:
            // - Filter canvasser di datatable
            // - Role-based access control (user_id = auth()->id())
            // - Join dengan users table
            if (!$this->indexExists('leads_master', 'idx_user_id')) {
                $table->index('user_id', 'idx_user_id');
            }

            // 3. Index untuk reg_id (link ke saldo_users.id_user)
            // Digunakan untuk:
            // - Join dengan saldo_users untuk get saldo_utama
            // - Sinkronisasi dengan data registrasi
            if (!$this->indexExists('leads_master', 'idx_reg_id')) {
                $table->index('reg_id', 'idx_reg_id');
            }

            // 4. Index untuk mobile_phone
            // Digunakan untuk:
            // - Cek duplicate phone number
            // - Search/filter nomor HP
            if (!$this->indexExists('leads_master', 'idx_mobile_phone')) {
                $table->index('mobile_phone', 'idx_mobile_phone');
            }

            // 5. Index untuk regional
            // Digunakan untuk:
            // - Filter regional di datatable
            // - Group by regional untuk reporting
            if (!$this->indexExists('leads_master', 'idx_regional')) {
                $table->index('regional', 'idx_regional');
            }

            // 6. Index untuk data_type
            // Digunakan untuk:
            // - Filter "Leads" vs "Eksisting Akun"
            // - Reporting dan analytics
            if (!$this->indexExists('leads_master', 'idx_data_type')) {
                $table->index('data_type', 'idx_data_type');
            }

            // 7. Index untuk created_at
            // Digunakan untuk:
            // - Filter date range di datatable
            // - Sorting by newest/oldest
            // - Reporting per periode
            if (!$this->indexExists('leads_master', 'idx_created_at')) {
                $table->index('created_at', 'idx_created_at');
            }

            // 8. Compound index untuk kombinasi filter yang sering digunakan
            // user_id + regional (untuk filter canvasser + regional sekaligus)
            if (!$this->indexExists('leads_master', 'idx_user_regional')) {
                $table->index(['user_id', 'regional'], 'idx_user_regional');
            }

            // 9. Compound index untuk email + data_type
            // Digunakan untuk query yang filter email dan cek tipe data sekaligus
            if (!$this->indexExists('leads_master', 'idx_email_data_type')) {
                $table->index(['email', 'data_type'], 'idx_email_data_type');
            }

            // 10. Index untuk source_id dan sector_id (foreign keys)
            if (!$this->indexExists('leads_master', 'idx_source_id')) {
                $table->index('source_id', 'idx_source_id');
            }
            if (!$this->indexExists('leads_master', 'idx_sector_id')) {
                $table->index('sector_id', 'idx_sector_id');
            }
        });

        // Setelah add indexes, update table statistics
        DB::statement('ANALYZE TABLE leads_master');
        
        echo "✅ Indexes berhasil ditambahkan ke leads_master\n";
        echo "⏱️  Query SELECT akan JAUH lebih cepat sekarang!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads_master', function (Blueprint $table) {
            // Drop indexes dalam urutan terbalik
            $indexes = [
                'idx_sector_id',
                'idx_source_id',
                'idx_email_data_type',
                'idx_user_regional',
                'idx_created_at',
                'idx_data_type',
                'idx_regional',
                'idx_mobile_phone',
                'idx_reg_id',
                'idx_user_id',
                'idx_email',
            ];

            foreach ($indexes as $index) {
                if ($this->indexExists('leads_master', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $index): bool
    {
        $connection = Schema::getConnection();
        $dbSchemaManager = $connection->getDoctrineSchemaManager();
        $doctrineTable = $dbSchemaManager->introspectTable($table);
        
        return $doctrineTable->hasIndex($index);
    }
};
