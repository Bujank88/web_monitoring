<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin Utama',
                'nohp' => '08123456789',
                'email' => 'admin@telkomsel.co.id',
                'role' => 'Admin',
            ],
            [
                'name' => 'Tsel User',
                'nohp' => '08987654321',
                'email' => 'tsel@telkomsel.co.id',
                'role' => 'Tsel',
            ],
        ];

        foreach ($users as $user) {
            $nope = $user['nohp'];

            // konversi 08xxxx -> 62xxxx
            if (Str::startsWith($nope, '08')) {
                $nope = '62' . substr($nope, 1);
            }

            User::updateOrCreate(
                ['email' => $user['email']], // cek biar gak double
                [
                    'name' => $user['name'],
                    'nohp' => $nope,
                    'role' => $user['role'],
                    'password' => Hash::make('123456'),
                    'status' => 'Aktif',
                ]
            );
        }
    }
}
