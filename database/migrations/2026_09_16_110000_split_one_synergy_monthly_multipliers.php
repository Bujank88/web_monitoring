<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('one_synergy_monthly_multipliers', function (Blueprint $table) {
            $table->renameColumn('multiplier', 'sms_multiplier');
        });
        Schema::table('one_synergy_monthly_multipliers', function (Blueprint $table) {
            $table->decimal('waba_multiplier', 15, 2)->nullable();
        });
        DB::table('one_synergy_monthly_multipliers')->update(['waba_multiplier' => DB::raw('sms_multiplier')]);
    }

    public function down(): void
    {
        Schema::table('one_synergy_monthly_multipliers', function (Blueprint $table) {
            $table->dropColumn('waba_multiplier');
            $table->renameColumn('sms_multiplier', 'multiplier');
        });
    }
};
