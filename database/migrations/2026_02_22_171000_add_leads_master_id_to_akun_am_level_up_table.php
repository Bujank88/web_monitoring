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
        if (!Schema::hasColumn('akun_am_level_up', 'leads_master_id')) {
            Schema::table('akun_am_level_up', function (Blueprint $table) {
                $table->unsignedBigInteger('leads_master_id')->nullable()->after('user_id');
                $table->foreign('leads_master_id')->references('id')->on('leads_master')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('akun_am_level_up', 'leads_master_id')) {
            Schema::table('akun_am_level_up', function (Blueprint $table) {
                $table->dropForeign(['leads_master_id']);
                $table->dropColumn('leads_master_id');
            });
        }
    }
};

