<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automatech_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('refunded')->default(0)->after('gagal');
            $table->unsignedBigInteger('click')->default(0)->after('refunded');
        });

        Schema::table('maxim_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('refunded')->default(0)->after('gagal');
            $table->unsignedBigInteger('click')->default(0)->after('refunded');
        });
    }

    public function down(): void
    {
        Schema::table('automatech_reports', function (Blueprint $table) {
            $table->dropColumn(['refunded', 'click']);
        });

        Schema::table('maxim_reports', function (Blueprint $table) {
            $table->dropColumn(['refunded', 'click']);
        });
    }
};
