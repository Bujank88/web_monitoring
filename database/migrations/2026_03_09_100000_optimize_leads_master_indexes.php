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
        // Gunakan raw SQL untuk create index (jika sudah ada akan di-skip)
        $indexes = [
            // Format: [index_name, column(s), description]
            ['idx_email', 'email', 'JOIN dengan report_balance_top_up, cek duplicate'],
            ['idx_user_id', 'user_id', 'Filter canvasser, role-based access control'],
            ['idx_reg_id', 'reg_id', 'JOIN dengan saldo_users'],
            ['idx_mobile_phone', 'mobile_phone', 'Cek duplicate phone, search'],
            ['idx_regional', 'regional', 'Filter regional, reporting'],
            ['idx_data_type', 'data_type', 'Filter Leads vs Eksisting Akun'],
            ['idx_created_at', 'created_at', 'Filter date range, sorting'],
            ['idx_user_regional', 'user_id, regional', 'Compound: filter canvasser + regional'],
            ['idx_email_data_type', 'email, data_type', 'Compound: email + tipe data'],
            ['idx_source_id', 'source_id', 'Foreign key ke leads_source'],
            ['idx_sector_id', 'sector_id', 'Foreign key ke sectors'],
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($indexes as [$indexName, $columns, $description]) {
            try {
                // Cek apakah index sudah ada menggunakan raw query
                if (!$this->indexExists('leads_master', $indexName)) {
                    DB::statement("CREATE INDEX {$indexName} ON leads_master ({$columns})");
                    echo "✅ Created index: {$indexName} ({$description})\n";
                    $createdCount++;
                } else {
                    echo "⏭️  Skipped: {$indexName} (already exists)\n";
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                // Jika error (mungkin index sudah ada), skip saja
                echo "⚠️  Skipped: {$indexName} - {$e->getMessage()}\n";
                $skippedCount++;
            }
        }

        // Update table statistics untuk optimize query planner
        try {
            DB::statement('ANALYZE TABLE leads_master');
            echo "\n📊 Table statistics updated\n";
        } catch (\Exception $e) {
            echo "⚠️  Warning: Could not analyze table - {$e->getMessage()}\n";
        }
        
        echo "\n✅ Migration completed!\n";
        echo "   - Created: {$createdCount} indexes\n";
        echo "   - Skipped: {$skippedCount} indexes\n";
        echo "⏱️  Query SELECT akan JAUH lebih cepat sekarang!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
            try {
                if ($this->indexExists('leads_master', $index)) {
                    DB::statement("DROP INDEX {$index} ON leads_master");
                    echo "🗑️  Dropped index: {$index}\n";
                }
            } catch (\Exception $e) {
                echo "⚠️  Could not drop {$index}: {$e->getMessage()}\n";
            }
        }
    }

    /**
     * Check if index exists menggunakan raw query
     * Kompatibel dengan semua versi Laravel dan MySQL
     */
    private function indexExists($table, $index): bool
    {
        try {
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            return count($result) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};
