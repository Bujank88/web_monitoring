<?php

use App\Models\LeadSpectrum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (new LeadSpectrum)->getTable();

        if (! Schema::hasColumn($tableName, 'user_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tableName = (new LeadSpectrum)->getTable();

        if (Schema::hasColumn($tableName, 'user_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};