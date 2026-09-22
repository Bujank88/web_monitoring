<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads_powerhouse') && !Schema::hasColumn('leads_powerhouse', 'solusi')) {
            Schema::table('leads_powerhouse', function (Blueprint $table) {
                $table->text('solusi')->nullable()->after('usecase');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leads_powerhouse') && Schema::hasColumn('leads_powerhouse', 'solusi')) {
            Schema::table('leads_powerhouse', function (Blueprint $table) {
                $table->dropColumn('solusi');
            });
        }
    }
};
