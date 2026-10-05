<?php

namespace App\Console\Commands;

use App\Services\TicketExcelImporter;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Throwable;

class ImportTickets extends Command
{
    protected $signature = 'ticketing:import {file : Path file Complaint Handling XLSX} {--dry-run : Periksa data tanpa menyimpan}';

    protected $description = 'Impor tiket Complaint Handling, menjaga nomor asli dan mencegah impor ulang';

    public function handle(TicketExcelImporter $importer): int
    {
        $path = $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error('File tidak ditemukan atau tidak dapat dibaca.');

            return self::FAILURE;
        }
        try {
            $stats = $importer->import($path, (bool) $this->option('dry-run'));
            $this->info($this->option('dry-run') ? 'Pemeriksaan selesai; database tidak diubah.' : 'Impor selesai.');
            $this->table(['Tiket baru', 'Sudah diimpor', 'Baris kosong', 'Nomor duplikat diberi akhiran'], [array_values($stats)]);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // Do not expose database credentials or raw complaint data in console output.
            $this->error($exception instanceof \RuntimeException && ! $exception instanceof QueryException
                ? $exception->getMessage()
                : 'Impor gagal; periksa koneksi database, migration, dan format file.');

            return self::FAILURE;
        }
    }
}
