<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TargetCanvaserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bulan = '2026-01';
        
        // Data target berdasarkan nama user
        $targets = [
            'Maria' => 653100000,
            'Meisya' => 139950000,
            'Amanah' => 171050000,
            'Hardi' => 93300000,
            'Intan' => 93300000,
            'Bustomi' => 93300000,
            'Indah' => 93300000,
            'Maiph' => 62200000,
            'Riva' => 62200000,
            'Akbar' => 31100000,
            'Hika' => 31100000,
            'Rizky' => 15550000,
            'Fanny' => 15550000,
        ];

        foreach ($targets as $nama => $target) {
            // Cari user berdasarkan nama (case insensitive)
            $user = DB::table('users')
                ->whereRaw('LOWER(name) = ?', [strtolower($nama)])
                ->first();

            if ($user) {
                // Insert atau update target
                DB::table('target_canvaser')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'bulan' => $bulan
                    ],
                    [
                        'target' => $target,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );

                $this->command->info("Target untuk {$nama} (ID: {$user->id}) berhasil ditambahkan.");
            } else {
                $this->command->warn("User dengan nama '{$nama}' tidak ditemukan.");
            }
        }

        $this->command->info('Seeder target_canvaser selesai!');
    }
}
