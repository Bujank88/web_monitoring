<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateProductUser extends Command
{
    protected $signature = 'users:create-product {email=product@telkomsel.co.id} {--name=Product}';

    protected $description = 'Create one active Product report account without modifying existing users';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email tidak valid.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Email sudah terdaftar. Akun yang ada tidak diubah.');

            return self::FAILURE;
        }

        $password = '123456';
        User::create([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => $password,
            'role' => 'product',
            'status' => 'Aktif',
        ]);

        $this->info('Akun Product berhasil dibuat.');
        $this->line('Email: '.$email);
        $this->line('Password: '.$password);

        return self::SUCCESS;
    }
}
