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
        Schema::table('leads_master', function (Blueprint $table) {
            if (!Schema::hasColumn('leads_master', 'flag_event')) {
                $table->string('flag_event')->nullable()->after('remarks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads_master', function (Blueprint $table) {
            if (Schema::hasColumn('leads_master', 'flag_event')) {
                $table->dropColumn('flag_event');
            }
        });
    }
};
