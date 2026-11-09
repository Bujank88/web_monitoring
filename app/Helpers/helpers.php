<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

if (!function_exists('logUserLogin')) {
    function logUserLogin()
    {
        $user = Auth::user();

        if (!$user) return;

        $now = now();
        $existing = DB::table('loglogin')
            ->where('user_id', $user->id)
            ->where('tgl', date('Y-m-d'))
            ->first();

        if ($existing) {
            // Sudah ada, update + updated_at
            DB::table('loglogin')
                ->where('user_id', $user->id)
                ->where('tgl', date('Y-m-d'))
                ->update([
                    'nama'       => $user->name,
                    'role'       => $user->role,
                    'email'      => $user->email,
                    'updated_at' => $now,
                ]);
        } else {
            // Belum ada, insert + created_at & updated_at
            DB::table('loglogin')->insert([
                'user_id'    => $user->id,
                'tgl'        => date('Y-m-d'),
                'nama'       => $user->name,
                'role'       => $user->role,
                'email'      => $user->email,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
