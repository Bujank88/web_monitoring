<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cdsi_referrals', function (Blueprint $table) {
            if (!Schema::hasColumn('cdsi_referrals', 'status')) {
                $table->string('status', 20)->default('active')->after('referral_code');
                $table->index('status');
            }
        });

        DB::table('cdsi_referrals')
            ->whereNull('status')
            ->update([
                'status' => 'active',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('cdsi_referrals', function (Blueprint $table) {
            if (Schema::hasColumn('cdsi_referrals', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};
