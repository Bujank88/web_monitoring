<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('project_ds', 'user_id')) {
            Schema::table('project_ds', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('project_ds', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};