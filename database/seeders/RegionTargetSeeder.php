<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegionTargetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagsel',
            'pic'           => 'Angga Satria Gusti',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 278850000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagteng',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 198000000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagut',
            'pic'           => 'Abdul Halim',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 282150000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jabar',
            'pic'           => 'Raden Agie Satria Akbar',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 297000000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jabodetabek',
            'pic'           => 'Sony Widjaya',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 1353000000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jateng DIY',
            'pic'           => 'Deni Setiawan',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 140250000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jatim',
            'pic'           => 'Muhammad Arief Syahbana',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 214500000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Bali Nusra',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 66000000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Kalimantan',
            'pic'           => 'Naqsyabandi',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 198000000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Papua Maluku',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 41250000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sulawesi',
            'pic'           => 'Ikrar Dharmawan',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 231000000,
            'data_type'    => 'PowerHouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        // Mitra SBP
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagsel',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 58278162,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagteng',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 65343502,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagut',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 52981336,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Eastern Jabotabek',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 14918850,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jabar',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 19891800,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jakarta Banten',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 20444350,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jateng DIY',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 21691044,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jatim',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 39119023,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Bali Nusra',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 24211932,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Kalimantan',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 30789810,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Papua Maluku',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 14886565,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sulawesi',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 35770625,
            'data_type'    => 'Mitra SBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);




        


        // Agency
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagsel',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 8583838,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagteng',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 9624498,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagut',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 7803664,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Eastern Jabotabek',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 6060024,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jabar',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 6894003,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jakarta Banten',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 4729473,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jateng DIY',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 4548456,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jatim',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 8202977,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Bali Nusra',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 5077068,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Kalimantan',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 6127190,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Papua Maluku',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 2962435,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sulawesi',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 7118375,
            'data_type'    => 'Agency',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

         


        // Internal
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagsel',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 25500000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagteng',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 12420000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sumbagut',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 14580000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Eastern Jabotabek',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 22350000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jabar',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 19770000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jakarta Banten',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 23130000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jateng DIY',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 16750000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Jatim',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 16500000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Bali Nusra',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 9500000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Kalimantan',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 14870000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Papua Maluku',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 9800000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('region_target')->insert([
            'region_name'   => 'Sulawesi',
            'pic'           => '-',
            'date'          => Carbon::create(2026, 1, 1)->format('Y-m-d'),
            'target_amount' => 15675000,
            'data_type'    => 'Internal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
