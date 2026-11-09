<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClaimedCvsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $filePath = storage_path('app/cvs/cvs.csv');

        if (!file_exists($filePath)) {
            echo "CSV file not found at $filePath";
            return;
        }

        $handle = fopen($filePath, 'r');

        // Skip header
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            [$email, $regional, $area, $canvasser, $nama, $no_telp] = array_pad($row, 6, null);

            if (!$email || !$canvasser) continue;

            if (\DB::table('claimed_cvs')->where('email', $email)->exists()) continue;

            \DB::table('claimed_cvs')->insert([
                'email' => $email,
                'regional' => $regional,
                'area' => $area,
                'canvasser' => $canvasser,
                'nama' => $nama,
                'no_telp' => $no_telp,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);

        echo "CSV import complete!";
    }

}
