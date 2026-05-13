<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automatech_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('read')->default(0)->after('refunded');
        });

        Schema::table('maxim_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('read')->default(0)->after('refunded');
        });
    }

    public function down(): void
    {
        Schema::table('automatech_reports', function (Blueprint $table) {
            $table->dropColumn('read');
        });

        Schema::table('maxim_reports', function (Blueprint $table) {
            $table->dropColumn('read');
        });
    }
};
