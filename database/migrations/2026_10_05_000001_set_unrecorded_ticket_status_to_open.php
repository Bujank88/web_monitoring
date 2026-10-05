<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tickets')->where(function ($query) {
            $query->whereNull('status')->orWhereRaw("TRIM(status) = ''");
        })->update(['status' => 'Open']);
    }

    public function down(): void
    {
        // Data normalization cannot be reversed without losing subsequent status changes.
    }
};
