<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MpccVoucherOwnerHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $defaultStart = '2026-01-01';

        $rows = [
            ['voucher_code' => 'HEBAT1',  'owner_name' => 'Rudy tanriaman',          'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT2',  'owner_name' => 'Goldfried edo sinambela', 'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT3',  'owner_name' => 'Aji irtiawan',            'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT4',  'owner_name' => 'Rizky fadhilah',          'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT5',  'owner_name' => 'Triya hadiyan',           'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT6',  'owner_name' => 'Wildan setiawan',         'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT7',  'owner_name' => 'Lia puji mulyati',        'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT8',  'owner_name' => 'Nur arief hidayatullah',  'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT9',  'owner_name' => 'Gusria setiani',          'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT10', 'owner_name' => 'Indra wijayanto',         'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT11', 'owner_name' => 'Bayu setiawan A',         'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT12', 'owner_name' => 'Rery prawika',            'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT13', 'owner_name' => 'Ahmad muzakki',           'effective_from' => $defaultStart, 'effective_to' => null],
            ['voucher_code' => 'HEBAT14', 'owner_name' => 'Dony Luditio',            'effective_from' => $defaultStart, 'effective_to' => null],
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
