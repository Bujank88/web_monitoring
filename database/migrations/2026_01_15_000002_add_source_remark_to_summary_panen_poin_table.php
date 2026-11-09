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
        Schema::table('summary_panen_poin', function (Blueprint $table) {
            $table->string('source', 50)->default('leads_master')->after('email_client')->comment('user_panen_poin or leads_master');
            $table->string('remark', 50)->nullable()->after('poin_redeem')->comment('Rookie, Rising Star, Champion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('summary_panen_poin', function (Blueprint $table) {
            $table->dropColumn(['source', 'remark']);
        });
    }
};
