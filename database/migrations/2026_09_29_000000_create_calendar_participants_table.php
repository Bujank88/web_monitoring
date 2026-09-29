<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_participants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 7)->nullable();
            $table->timestamps();
        });

        // Preserve the original calendar roster and its event colors as database records.
        // PH and MPCC accounts are read directly from users, including future additions.
        $participants = [
            'Robert J. Nandjong' => '#20c997',
            'Luky Ghazali' => '#0d6efd',
            'Fauzia Noviyanti' => '#6610f2',
            'Nopranda Dirzan' => '#fd7e14',
            'Angga Satria Gusti' => '#198754',
            'Abdul Halim' => '#dc3545',
            'Raden Agie Satria Akbar' => '#6f42c1',
            'Sony Widjaya' => '#17a2b8',
            'Deni Setiawan' => '#e83e8c',
            'Muhammad Arief Syahbana' => '#0dcaf0',
            'Naqsyabandi' => '#adb5bd',
            'Ikrar Dharmawan' => '#795548',
        ];

        $now = now();
        foreach ($participants as $name => $color) {
            DB::table('calendar_participants')->insert([
                'name' => $name,
                'color' => $color,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_participants');
    }
};
