<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogbookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = storage_path('app/logbook.csv');

        if (!file_exists($path)) {
            $this->command->error('File logbook.csv tidak ditemukan');
            return;
        }

        $file = fopen($path, 'r');

        // Header CSV
        $header = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {

            $data = array_combine($header, $row);

            // Cari leads_master berdasarkan email
            $leads = DB::table('leads_master')
                ->where('email', trim($data['EMAIL']))
                ->first();

            // Jika email tidak ditemukan → skip
            if (!$leads) {
                $this->command->warn('Email tidak ditemukan: ' . $data['EMAIL']);
                continue;
            }

            // Plan min topup (hapus koma)
            $planMinTopup = (int) str_replace(',', '', $data['PLAN MIN TOP UP']);

            DB::table('logbook')->insert([
                'leads_master_id' => $leads->id,
                'komitmen'        => $data['KOMITMEN'] ?? 'New Leads',
                'plan_min_topup'  => $planMinTopup ?: 100000,
                'status'          => 'Prospect',
                'bulan'           => 1, // contoh: Oktober
                'tahun'           => 2026,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        fclose($file);
    }
}
