<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leads_powerhouse') || !Schema::hasColumn('leads_powerhouse', 'usecase')) {
            return;
        }

        DB::statement('ALTER TABLE leads_powerhouse MODIFY usecase TEXT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('leads_powerhouse') || !Schema::hasColumn('leads_powerhouse', 'usecase')) {
            return;
        }

        DB::statement('ALTER TABLE leads_powerhouse MODIFY usecase VARCHAR(255) NULL');
    }
};
