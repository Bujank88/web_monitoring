<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'leads_id')) {
                $table->unsignedBigInteger('leads_id')->nullable()->after('warna');
                $table->index('leads_id');
            }

            if (!Schema::hasColumn('bookings', 'type')) {
                $table->string('type', 50)->nullable()->after('leads_id');
                $table->index('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'type')) {
                $table->dropIndex(['type']);
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('bookings', 'leads_id')) {
                $table->dropIndex(['leads_id']);
                $table->dropColumn('leads_id');
            }
        });
    }
};
