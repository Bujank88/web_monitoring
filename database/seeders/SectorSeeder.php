<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = [
            'Transportasi',
            'Lain - Lain',
            'Otomotif',
            'Fashion',
            'Finansial',
            'Kesehatan',
            'Gadget, Teknologi & IT',
            'Toko Online',
            'Pendidikan',

            // Tambahan sektor lebih banyak
            'F&B (Food & Beverage)',
            'Properti & Real Estate',
            'Perbankan',
            'Asuransi',
            'Telekomunikasi',
            'Energi & Pertambangan',
            'Pertanian & Perkebunan',
            'Perikanan',
            'Manufaktur',
            'Retail & Wholesale',
            'Logistik & Warehouse',
            'Travel & Hospitality',
            'Pemerintahan / Instansi Publik',
            'Media & Entertainment',
            'Konstruksi',
            'Perhotelan',
            'Event & Organizer',
            'Startup',
            'E-Commerce',
            'Gaming & Esports',
            'Jasa Profesional',
            'Hukum & Notaris',
            'Akuntansi & Konsultan',
            'Beauty & Personal Care',
            'Peralatan Rumah Tangga',
            'Keamanan (Security)',
            'Pet Care & Hewan',
            'Non-Profit / Yayasan',
            'Layanan Kebersihan',
            'Layanan Kurir & Ekspedisi',
            'Fintech',
            'Marketplace B2B',
            'Marketplace B2C',
            'Perpustakaan & Edukasi',
            'Photography & Videography',
            'Human Resources / HR Services',
        ];

        foreach ($sectors as $name) {
            DB::table('sectors')->insert([
                'name'       => $name,
                'description'=> null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
