<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PowerHouseVoucherOwnerHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $defaultStart = '2026-01-01';

        $rows = [
            ['voucher_code' => 'SUPER1', 'owner_name' => 'Angga Satria Gusti',        'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'SUPER2', 'owner_name' => 'Abdul Halim',               'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'SUPER3', 'owner_name' => 'Raden Agie S. Akbar',       'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'SUPER4', 'owner_name' => 'Sony Widjaya',              'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'SUPER5', 'owner_name' => 'Deni Setiawan',             'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'SUPER6', 'owner_name' => 'Muhammad Arief Syahbana',   'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'SUPER7', 'owner_name' => 'Naqsyabandi',               'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'SUPER8', 'owner_name' => 'Ikrar Dharmawan',           'effective_from' => $defaultStart, 'effective_to' => null],
        ];

        foreach ($rows as $row) {
            DB::table('voucher_owner_history')->updateOrInsert(
                [
                    'voucher_code' => $row['voucher_code'],
                    'effective_from' => $row['effective_from'],
                ],
                [
                    'owner_name' => $row['owner_name'],
                    'effective_to' => $row['effective_to'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
