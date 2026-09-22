<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sbp_referrals')) {
            return;
        }

        Schema::table('sbp_referrals', function (Blueprint $table) {
            if (!Schema::hasColumn('sbp_referrals', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('sbp_referrals', 'user_email')) {
                $table->string('user_email')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('sbp_referrals')) {
            return;
        }

        Schema::table('sbp_referrals', function (Blueprint $table) {
            if (Schema::hasColumn('sbp_referrals', 'user_email')) {
                $table->dropColumn('user_email');
            }

            if (Schema::hasColumn('sbp_referrals', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
