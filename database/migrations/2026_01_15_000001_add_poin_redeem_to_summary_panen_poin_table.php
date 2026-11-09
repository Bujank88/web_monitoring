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
            $table->integer('poin_redeem')->default(0)->after('poin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('summary_panen_poin', function (Blueprint $table) {
            $table->dropColumn('poin_redeem');
        });
    }
};
