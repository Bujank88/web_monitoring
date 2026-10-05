<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportInternCanvassers extends Command
{
    protected $signature = 'canvassers:import-interns';

    protected $description = 'Buat lima akun canvasser magang; akun yang sudah ada tidak diubah';

    public const ROSTER = [
        ['Armayati Primadani', '081374050233', 'armayatiprimadani@gmail.com'],
        ['Dhea Veriska', '085178570101', 'dheaveriska20@gmail.com'],
        ['Aura Zahwa', '085181378737', 'aura.zahwa12@gmail.com'],
        ['Sawitri', '081364204775', 'awitt2104@gmail.com'],
        ['Siti Zaharah Herlambang', '087745380834', 'szahrah.stz@gmail.com'],
    ];

    public function handle(): int
    {
        $pending = [];
        foreach (self::ROSTER as [$name, $phone, $email]) {
            $existing = User::where('email', $email)->first();
            if ($existing) {
                if ($existing->role !== 'cvsr') {
                    $this->error("Akun {$email} memiliki role lain. Selesaikan melalui admin sebelum impor.");

                    return self::FAILURE;
                }
                $this->info("Dilewati (sudah ada): {$email}");
                continue;
            }
            $password = '123456';
            $pending[] = ['name' => $name, 'nohp' => $phone, 'email' => $email, 'password' => $password, 'role' => 'cvsr', 'status' => 'Aktif'];
        }
        DB::transaction(function () use ($pending) {
            foreach ($pending as $attributes) {
                User::create($attributes);
            }
        });
        $this->info(count($pending).' akun dibuat. Atur PIC melalui menu Tim Canvasser.');

        return self::SUCCESS;
    }
}
