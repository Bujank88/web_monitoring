<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads_spectrum', function (Blueprint $table) {
            $table->string('utm_source', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leads_spectrum', function (Blueprint $table) {
            $table->dropColumn('utm_source');
        });
    }
};
