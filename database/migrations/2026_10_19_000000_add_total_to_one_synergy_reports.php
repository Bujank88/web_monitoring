<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('one_synergy_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('total')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('one_synergy_reports', function (Blueprint $table) {
            $table->dropColumn('total');
        });
    }
};
