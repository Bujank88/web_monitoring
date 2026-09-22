<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $originalSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode as mode')->mode ?? '';

        DB::statement("SET SESSION sql_mode = ''");

        try {
            Schema::table('detail_leads_summary', function (Blueprint $table) {
                if (!Schema::hasColumn('detail_leads_summary', 'flag_event')) {
                    $table->string('flag_event')->nullable()->after('remarks');
                    $table->index('flag_event');
                }
            });

            if (
                Schema::hasColumn('detail_leads_summary', 'flag_event') &&
                Schema::hasColumn('leads_master', 'flag_event')
            ) {
                DB::statement('
                    UPDATE detail_leads_summary dls
                    INNER JOIN leads_master lm ON lm.id = dls.leads_master_id
                    SET dls.flag_event = lm.flag_event
                ');
            }
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$originalSqlMode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_leads_summary', function (Blueprint $table) {
            if (Schema::hasColumn('detail_leads_summary', 'flag_event')) {
                $table->dropIndex(['flag_event']);
                $table->dropColumn('flag_event');
            }
        });
    }
};
