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
        Schema::table('logbook_daily', function (Blueprint $table) {
            // Cek kolom yang sudah ada, hanya tambahkan yang belum
            $columns = Schema::getColumnListing('logbook_daily');
            
            // Kolom untuk tracking WA notification
            if (!in_array('is_sent_logbook', $columns)) {
                $table->boolean('is_sent_logbook')->default(false)->after('status');
            }
            
            if (!in_array('last_send_attempt', $columns)) {
                $table->timestamp('last_send_attempt')->nullable()->after('is_sent_logbook');
            }
            
            // Kolom untuk data realisasi - hanya tambah jika belum ada
            if (!in_array('realisasi_method', $columns)) {
                $table->enum('realisasi_method', ['online', 'offline'])->nullable()->after('last_send_attempt');
            }
            
            if (!in_array('realisasi_discus', $columns)) {
                $table->text('realisasi_discus')->nullable()->after('realisasi_method');
            }
            
            if (!in_array('realisasi_photo', $columns)) {
                $table->string('realisasi_photo')->nullable()->after('realisasi_discus');
            }
            
            if (!in_array('realisasi_at', $columns)) {
                $table->timestamp('realisasi_at')->nullable()->after('realisasi_photo');
            }
            
            // Kolom untuk tracking realisasi topup
            if (!in_array('realisasi_topup', $columns)) {
                $table->decimal('realisasi_topup', 15, 2)->default(0)->after('realisasi_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logbook_daily', function (Blueprint $table) {
            $columns = Schema::getColumnListing('logbook_daily');
            
            $columnsToDelete = [
                'is_sent_logbook',
                'last_send_attempt',
                'realisasi_method',
                'realisasi_discus',
                'realisasi_photo',
                'realisasi_at',
                'realisasi_topup',
            ];
            
            foreach ($columnsToDelete as $column) {
                if (in_array($column, $columns)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
