<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegionalProvincesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            // SUMBAGSEL - AREA 1
            ['regional' => 'SUMBAGSEL', 'province' => 'Sumatera Selatan', 'area' => 'AREA 1'],
            ['regional' => 'SUMBAGSEL', 'province' => 'Jambi', 'area' => 'AREA 1'],
            ['regional' => 'SUMBAGSEL', 'province' => 'Bengkulu', 'area' => 'AREA 1'],
            ['regional' => 'SUMBAGSEL', 'province' => 'Lampung', 'area' => 'AREA 1'],
            ['regional' => 'SUMBAGSEL', 'province' => 'Bangka Belitung', 'area' => 'AREA 1'],

            // SUMBAGTENG - AREA 1
            ['regional' => 'SUMBAGTENG', 'province' => 'Sumatera Barat', 'area' => 'AREA 1'],
            ['regional' => 'SUMBAGTENG', 'province' => 'Riau', 'area' => 'AREA 1'],
            ['regional' => 'SUMBAGTENG', 'province' => 'Kepulauan Riau', 'area' => 'AREA 1'],

            // SUMBAGUT - AREA 1
            ['regional' => 'SUMBAGUT', 'province' => 'Sumatera Utara', 'area' => 'AREA 1'],
            ['regional' => 'SUMBAGUT', 'province' => 'Nangroe Aceh', 'area' => 'AREA 1'],

            // JABODETABEK - AREA 2
            ['regional' => 'JABODETABEK', 'province' => 'DKI Jakarta', 'area' => 'AREA 2'],
            ['regional' => 'JABODETABEK', 'province' => 'Banten', 'area' => 'AREA 2'],

            // JABAR - AREA 2
            ['regional' => 'JABAR', 'province' => 'Jawa Barat', 'area' => 'AREA 2'],

            // JATENG DIY - AREA 3
            ['regional' => 'JATENG DIY', 'province' => 'Jawa Tengah', 'area' => 'AREA 3'],
            ['regional' => 'JATENG DIY', 'province' => 'Yogyakarta', 'area' => 'AREA 3'],

            // JATIM - AREA 3
            ['regional' => 'JATIM', 'province' => 'Jawa Timur', 'area' => 'AREA 3'],

            // BALI NUSRA - AREA 3
            ['regional' => 'BALI NUSRA', 'province' => 'Bali', 'area' => 'AREA 3'],
            ['regional' => 'BALI NUSRA', 'province' => 'NTB', 'area' => 'AREA 3'],
            ['regional' => 'BALI NUSRA', 'province' => 'NTT', 'area' => 'AREA 3'],

            // KALIMANTAN - AREA 4
            ['regional' => 'KALIMANTAN', 'province' => 'Kalimantan Tengah', 'area' => 'AREA 4'],
            ['regional' => 'KALIMANTAN', 'province' => 'Kalimantan Barat', 'area' => 'AREA 4'],
            ['regional' => 'KALIMANTAN', 'province' => 'Kalimantan Utara', 'area' => 'AREA 4'],
            ['regional' => 'KALIMANTAN', 'province' => 'Kalimantan Timur', 'area' => 'AREA 4'],
            ['regional' => 'KALIMANTAN', 'province' => 'Kalimantan Selatan', 'area' => 'AREA 4'],

            // SULAWESI - AREA 4
            ['regional' => 'SULAWESI', 'province' => 'Sulawesi Utara', 'area' => 'AREA 4'],
            ['regional' => 'SULAWESI', 'province' => 'Sulawesi Tengah', 'area' => 'AREA 4'],
            ['regional' => 'SULAWESI', 'province' => 'Gorontalo', 'area' => 'AREA 4'],
            ['regional' => 'SULAWESI', 'province' => 'Sulawesi Tenggara', 'area' => 'AREA 4'],
            ['regional' => 'SULAWESI', 'province' => 'Sulawesi Selatan', 'area' => 'AREA 4'],
            ['regional' => 'SULAWESI', 'province' => 'Maluku Utara', 'area' => 'AREA 4'],

            // PAPUA MALUKU - AREA 4
            ['regional' => 'Papua Maluku', 'province' => 'Maluku', 'area' => 'AREA 4'],
            ['regional' => 'Papua Maluku', 'province' => 'Papua Barat', 'area' => 'AREA 4'],
            ['regional' => 'Papua Maluku', 'province' => 'Papua', 'area' => 'AREA 4'],
        ];

        DB::table('regional_provinces')->insert(
            array_map(function ($item) use ($now) {
                return array_merge($item, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }, $data)
        );
    }
}
