<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshSummarySimpatiTiktok extends Command
{
    /**
     * The name and signature of the console command.
     *
     * php artisan refresh:simpati-tiktok
     */
    protected $signature = 'refresh:simpati-tiktok';

    /**
     * The console command description.
     */
    protected $description = 'Refresh summary data Simpati Tiktok ke tabel summary_simpati_tiktok';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Proses refresh summary simpati_tiktok dimulai...");

        $data = DB::table('simpati_tiktok as st')
            ->select(
                'st.email',
                DB::raw('MAX(st.id) as simpati_tiktok_id'),
                DB::raw('MAX(st.no_hp) as no_hp'),
                DB::raw('MAX(st.nama_lengkap) as nama_lengkap'),
                DB::raw('COUNT(r.id) as jumlah_topup'),
                DB::raw('COALESCE(SUM(r.saldo_utama), 0) as total_saldo_utama'),
                DB::raw('COALESCE(SUM(r.saldo_bonus), 0) as total_saldo_bonus'),
                DB::raw('COALESCE(SUM(r.total), 0) as total_topup'),
                DB::raw('MIN(st.created_at) as created_at'),
                DB::raw('MAX(st.updated_at) as updated_at')
            )
            ->leftJoin('manajemen_user_register as mur', DB::raw('st.email COLLATE utf8mb4_unicode_ci'), '=', DB::raw('mur.email COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('revenue as r', DB::raw('mur.reg_id COLLATE utf8mb4_unicode_ci'), '=', DB::raw('r.id_klien COLLATE utf8mb4_unicode_ci'))
            ->where('r.status', 'PAID')
            ->groupBy('st.email')
            ->get();

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $row) {
            DB::table('summary_simpati_tiktok')->updateOrInsert(
                ['email' => $row->email],
                [
                    'simpati_tiktok_id' => $row->simpati_tiktok_id,
                    'no_hp'             => $row->no_hp,
                    'nama_lengkap'      => $row->nama_lengkap,
                    'jumlah_topup'      => $row->jumlah_topup,
                    'total_saldo_utama' => $row->total_saldo_utama,
                    'total_saldo_bonus' => $row->total_saldo_bonus,
                    'total_topup'       => $row->total_topup,
                    'created_at'        => $row->created_at,
                    'updated_at'        => now(),
                ]
            );
            $bar->advance();
        }

        $bar->finish();

        $this->newLine();
        $this->info("✅ Proses refresh summary simpati_tiktok selesai.");
    }
}
