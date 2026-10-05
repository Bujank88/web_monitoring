<?php

namespace App\Services;

use App\Models\Ticket;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use RuntimeException;

class TicketExcelImporter
{
    private const HEADERS = [
        'A' => 'Queue', 'B' => 'User', 'C' => 'Time of Request', 'D' => 'Aging',
        'E' => 'Request Type', 'F' => 'Complaint Type', 'G' => 'Channel',
        'H' => 'Method', 'I' => 'Account or Campaign ID', 'J' => 'Diagnosis Issue',
        'K' => 'Picture / Evidence', 'L' => 'Update', 'M' => 'Status',
        'N' => 'Time of Resolution', 'O' => 'Priority', 'P' => 'Length of Resolution',
    ];

    public function import(string $path, bool $dryRun = true): array
    {
        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['ROW']);
        $workbook = $reader->load($path);

        try {
            $sheet = $workbook->getSheetByName('ROW');
            if (! $sheet) {
                throw new RuntimeException('Sheet ROW tidak ditemukan.');
            }
            $headerRow = null;
            for ($row = 1; $row <= min(30, $sheet->getHighestDataRow()); $row++) {
                if (trim((string) $sheet->getCell('A'.$row)->getValue()) === 'Queue') {
                    $headerRow = $row;
                    break;
                }
            }
            if (! $headerRow) {
                throw new RuntimeException('Header Queue tidak ditemukan.');
            }
            foreach (self::HEADERS as $column => $header) {
                if (trim((string) $sheet->getCell($column.$headerRow)->getValue()) !== $header) {
                    throw new RuntimeException('Header '.$column.' harus '.$header.'.');
                }
            }

            $records = [];
            $occurrences = [];
            $stats = ['imported' => 0, 'already_imported' => 0, 'empty_rows' => 0, 'duplicate_numbers' => 0];
            $maxNumber = 0;
            for ($row = $headerRow + 1; $row <= $sheet->getHighestDataRow(); $row++) {
                $raw = [];
                foreach (range('A', 'R') as $column) {
                    $cell = $sheet->getCell($column.$row);
                    // Preserve cached spreadsheet formulas; never evaluate workbook content.
                    $raw[$column] = $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();
                }
                $queue = $this->text($raw['A']);
                if (! $queue || ! preg_match('/^T(\d+)$/', $queue, $match)) {
                    continue; // Documentation and the example are not ticket records.
                }
                $hasData = false;
                foreach (['B', 'C', 'E', 'F', 'G', 'H', 'I', 'J', 'L', 'M', 'N', 'O', 'R'] as $column) {
                    if ($this->text($raw[$column]) !== null) {
                        $hasData = true;
                        break;
                    }
                }
                if (! $hasData) {
                    $stats['empty_rows']++;

                    continue;
                }
                $maxNumber = max($maxNumber, (int) $match[1]);
                $occurrences[$queue] = ($occurrences[$queue] ?? 0) + 1;
                $number = $queue;
                if ($occurrences[$queue] > 1) {
                    $number .= '-'.$occurrences[$queue];
                    $stats['duplicate_numbers']++;
                }
                $requestedAt = $this->date($raw['C'], $row, 'C', $workbook->getExcelCalendar());
                if (! $requestedAt) {
                    throw new RuntimeException('Baris '.$row.': waktu pengajuan kosong. Impor dibatalkan.');
                }
                $status = $this->text($raw['M']) ?? 'Open';
                if ($status === 'In Prograss') {
                    $status = 'In Progress';
                }
                $records[] = [
                    'ticket_number' => $number,
                    'original_ticket_number' => $queue,
                    'user_name' => $this->text($raw['B']),
                    'requested_at' => $requestedAt,
                    'request_type' => $this->text($raw['E']),
                    'complaint_type' => $this->text($raw['F']),
                    'channel' => $this->text($raw['G']),
                    'method' => $this->text($raw['H']),
                    'account_campaign_id' => $this->text($raw['I']),
                    'diagnosis_issue' => $this->text($raw['J']),
                    'evidence_reference' => $this->text($raw['K']),
                    'resolution_update' => $this->text($raw['L']),
                    'status' => $status,
                    'resolved_at' => $this->date($raw['N'], $row, 'N', $workbook->getExcelCalendar()),
                    'priority' => $this->text($raw['O']),
                    'handling_level' => $this->text($raw['R']),
                    'import_source_key' => hash('sha256', json_encode(['ROW', $row, $raw], JSON_THROW_ON_ERROR)),
                    'import_data' => ['sheet' => 'ROW', 'row' => $row, 'cells' => $raw],
                ];
            }
            if (! $records) {
                throw new RuntimeException('Tidak ditemukan data tiket untuk diimpor.');
            }

            return DB::transaction(function () use ($records, $stats, $maxNumber, $dryRun) {
                // Use the same lock as manual input so imports cannot race with new tickets.
                $sequence = DB::table('ticket_sequences')->where('id', 1)->lockForUpdate()->first();
                if (! $sequence) {
                    throw new RuntimeException('Jalankan migration ticketing terlebih dahulu.');
                }
                $existingSources = array_fill_keys(Ticket::whereIn('import_source_key', array_column($records, 'import_source_key'))
                    ->pluck('import_source_key')->all(), true);
                $existingNumbers = array_fill_keys(Ticket::whereIn('ticket_number', array_column($records, 'ticket_number'))
                    ->pluck('ticket_number')->all(), true);
                $inserts = [];
                $timestamp = now()->format('Y-m-d H:i:s');
                foreach ($records as $record) {
                    if (isset($existingSources[$record['import_source_key']])) {
                        $stats['already_imported']++;

                        continue;
                    }
                    if (isset($existingNumbers[$record['ticket_number']])) {
                        throw new RuntimeException('Nomor '.$record['ticket_number'].' sudah digunakan oleh data lain. Tidak ada data yang diubah.');
                    }
                    if (! $dryRun) {
                        $record['import_data'] = json_encode($record['import_data'], JSON_THROW_ON_ERROR);
                        $record['created_at'] = $timestamp;
                        $record['updated_at'] = $timestamp;
                        $inserts[] = $record;
                    }
                    $stats['imported']++;
                }
                if (! $dryRun) {
                    foreach (array_chunk($inserts, 100) as $chunk) {
                        Ticket::insert($chunk);
                    }
                    DB::table('ticket_sequences')->where('id', 1)
                        ->update(['next_number' => max($sequence->next_number, $maxNumber + 1)]);
                }

                return $stats;
            });
        } finally {
            $workbook->disconnectWorksheets();
        }
    }

    private function text(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function date(mixed $value, int $row, string $column, int $calendar): ?string
    {
        if ($this->text($value) === null) {
            return null;
        }
        if (is_numeric($value)) {
            if ((float) $value < 1 || (float) $value > 2958465) {
                throw new RuntimeException('Baris '.$row.' kolom '.$column.': serial tanggal tidak valid.');
            }
            $previousCalendar = Date::getExcelCalendar();
            try {
                Date::setExcelCalendar($calendar);

                return Date::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
            } finally {
                Date::setExcelCalendar($previousCalendar);
            }
        }
        // Excel contains one US-formatted timestamp: 12/13/2025 08.00.00.
        foreach (['!d/m/Y H:i:s', '!m/d/Y H.i.s', '!d/m/Y H.i.s', '!Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, trim((string) $value));
            $errors = DateTimeImmutable::getLastErrors();
            if ($date && (! $errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        throw new RuntimeException('Baris '.$row.' kolom '.$column.': format tanggal tidak dikenali. Impor dibatalkan.');
    }
}
