<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('project_area3_members', 'reg_id')) {
            Schema::table('project_area3_members', function (Blueprint $table) {
                $table->string('reg_id')->nullable()->after('lokasi_kerja');
            });
        }
    }

    public function down(): void
    {
        Schema::table('project_area3_members', function (Blueprint $table) {
            $table->dropColumn('reg_id');
        });
    }
};