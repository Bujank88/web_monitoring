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
        Schema::table('detail_leads_summary', function (Blueprint $table) {
            if (!Schema::hasColumn('detail_leads_summary', 'saldo_utama')) {
                $table->decimal('saldo_utama', 15, 2)->default(0)->after('total_settlement_klien');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_leads_summary', function (Blueprint $table) {
            if (Schema::hasColumn('detail_leads_summary', 'saldo_utama')) {
                $table->dropColumn('saldo_utama');
            }
        });
    }
};
