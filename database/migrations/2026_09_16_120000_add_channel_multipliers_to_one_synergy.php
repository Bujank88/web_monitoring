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
            $table->decimal('sms_multiplier', 15, 2)->nullable()->change();
            foreach (['sms', 'waba'] as $category) {
                foreach (['lba', 'broadcast', 'targeted'] as $channel) {
                    $table->decimal($category . '_' . $channel . '_multiplier', 15, 2)->nullable();
                }
            }
        });
        $values = [];
        foreach (['sms', 'waba'] as $category) {
            foreach (['lba', 'broadcast', 'targeted'] as $channel) {
                $values[$category . '_' . $channel . '_multiplier'] = DB::raw($category . '_multiplier');
            }
        }
        DB::table('one_synergy_monthly_multipliers')->update($values);
    }

    public function down(): void
    {
        Schema::table('one_synergy_monthly_multipliers', function (Blueprint $table) {
            foreach (['sms', 'waba'] as $category) {
                foreach (['lba', 'broadcast', 'targeted'] as $channel) {
                    $table->dropColumn($category . '_' . $channel . '_multiplier');
                }
            }
        });
    }
};
