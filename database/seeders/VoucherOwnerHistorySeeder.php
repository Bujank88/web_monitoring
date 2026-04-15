<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class VoucherOwnerHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $defaultStart = '2026-01-01';

        $rows = [
            ['voucher_code' => 'EXTRA1',  'owner_name' => 'Amanah',            'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA2',  'owner_name' => 'Indah',             'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA3',  'owner_name' => 'Maria',             'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA4',  'owner_name' => 'Meisya',            'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA5',  'owner_name' => 'Hardi',             'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA6',  'owner_name' => 'Bustomi',           'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA7',  'owner_name' => 'Intan',             'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA8',  'owner_name' => 'Hika Rochmah',      'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA9',  'owner_name' => 'Akbar Zikron',      'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA10', 'owner_name' => 'Riva',              'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA11', 'owner_name' => 'Fanni',             'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA12', 'owner_name' => 'Maiph',             'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA14', 'owner_name' => 'Afan',              'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'EXTRA15', 'owner_name' => 'Herman',            'effective_from' => $defaultStart, 'effective_to' => null],

            // EXTRA13 history: Jan-Mar Rizky, Apr+ Nyayu
            ['voucher_code' => 'EXTRA13', 'owner_name' => 'Rizky',             'effective_from' => '2026-01-01',  'effective_to' => '2026-03-31'],
            ['voucher_code' => 'EXTRA13', 'owner_name' => 'Nyayu Z. Septianita','effective_from' => '2026-04-01',  'effective_to' => null],
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
