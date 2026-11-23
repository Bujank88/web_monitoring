<?php

namespace App\Services;

use App\Models\LeadSpectrum;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SpectrumLeadImporter
{
    public function read(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            if (! $reader instanceof Xlsx && ! $reader instanceof Xls) {
                $this->fail('Format file harus Excel .xlsx atau .xls.');
            }
            $book = $reader->load($path);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->fail('File Excel tidak dapat dibaca. Pastikan file tidak rusak atau dilindungi password.');
        }

        $previousCalendar = Date::getExcelCalendar();
        Date::setExcelCalendar($book->getExcelCalendar());
        try {
            $sheet = null;
            $columns = [];
            // 13 kolom inti selalu ada di format lama (JAN-SEP 2026) maupun baru (OKT-NOV 2026)
            $coreHeaders = array_diff_key(LeadSpectrum::REQUIRED_HEADERS, ['fu' => true, 'response' => true]);

            foreach ($book->getAllSheets() as $candidate) {
                $found = [];
                foreach ($candidate->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
                    $label = $this->normalize((string) $cell->getValue());
                    if ($label === '') {
                        continue;
                    }

                    // Tangani 2 kolom kembar di Excel: Reason Call & Sub Reason Call
                    if ($label === 'reason call') {
                        $targetField = ! isset($found['reason_call']) ? 'reason_call' : 'reason_call_fu';
                        if (isset($found[$targetField])) {
                            $this->fail("Header Reason Call tercatat lebih dari dua kali pada sheet {$candidate->getTitle()}.");
                        }
                        $found[$targetField] = $cell->getColumn();
                        continue;
                    }
                    if ($label === 'sub reason call') {
                        $targetField = ! isset($found['sub_reason_call']) ? 'sub_reason_call' : 'sub_reason_call_fu';
                        if (isset($found[$targetField])) {
                            $this->fail("Header Sub Reason Call tercatat lebih dari dua kali pada sheet {$candidate->getTitle()}.");
                        }
                        $found[$targetField] = $cell->getColumn();
                        continue;
                    }

                    foreach (LeadSpectrum::HEADERS as $field => $header) {
                        if ($label === $this->normalize($header)) {
                            if (isset($found[$field])) {
                                $this->fail("Header {$header} tercatat dua kali pada sheet {$candidate->getTitle()}.");
                            }
                            $found[$field] = $cell->getColumn();
                        }
                    }
                }

                if (array_diff_key($coreHeaders, $found) === []) {
                    if ($sheet !== null) {
                        $this->fail('Gunakan satu sheet data dengan header Spectrum agar data yang diimpor jelas.');
                    }
                    $sheet = $candidate;
                    $columns = $found;
                }
            }

            if ($sheet === null) {
                $this->fail('Header pada baris pertama harus memuat: '.implode(', ', $coreHeaders).'.');
            }
            if ($sheet->getHighestDataRow() > 10001) {
                $this->fail('Maksimal 10.000 baris leads per upload.');
            }

            $dateFields = ['create_date', 'share_date', 'tgl_dispatch'];
            $textFields = ['description', 'fu', 'response', 'notes', 'alamat', 'detail_kebutuhan'];

            $rows = [];
            for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                $data = [];
                foreach (LeadSpectrum::HEADERS as $field => $header) {
                    if (! isset($columns[$field])) {
                        $data[$field] = null;
                        continue;
                    }
                    $cell = $sheet->getCell($columns[$field].$row);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA || $cell->getDataType() === DataType::TYPE_ERROR) {
                        $this->fail("Baris {$row}, {$header}: gunakan nilai biasa, bukan rumus atau error Excel.");
                    }
                    $value = $cell->getValue();
                    $text = trim((string) $value);
                    if ($text === '') {
                        $data[$field] = null;
                    } elseif (in_array($field, $dateFields, true)) {
                        $data[$field] = $this->date($value, $row, $header);
                    } else {
                        // Jika Tgl Call 1st / Tgl Call FU di Excel berupa serial tanggal numerik, ubah ke Y-m-d
                        if (in_array($field, ['tgl_call_1st', 'tgl_call_fu'], true) && is_numeric($value) && (float) $value >= 1 && (float) $value <= 2958465) {
                            $text = Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
                        }
                        $limit = in_array($field, $textFields, true) ? 65535 : 255;
                        if (($limit === 255 ? mb_strlen($text) : strlen($text)) > $limit) {
                            $this->fail("Baris {$row}, {$header}: isi terlalu panjang.");
                        }
                        $data[$field] = $text;
                    }
                }
                if (count(array_filter($data, fn ($value) => $value !== null)) > 0) {
                    $rows[] = $data;
                }
            }

            if ($rows === []) {
                $this->fail('File tidak berisi data leads.');
            }

            return $rows;
        } finally {
            Date::setExcelCalendar($previousCalendar);
            $book->disconnectWorksheets();
        }
    }

    public function import(string $path, string $filename, int $userId): array
    {
        // Validate the whole workbook before saving any row.
        $rows = $this->read($path);
        $baseKeys = array_keys(LeadSpectrum::REQUIRED_HEADERS);

        return DB::transaction(function () use ($rows, $filename, $userId, $baseKeys) {
            $created = 0;
            foreach ($rows as $data) {
                $fingerprint = $data;
                // Pertahankan fingerprint identik untuk file lama (15 kolom) agar deteksi duplikat tetap akurat
                foreach ($fingerprint as $key => $val) {
                    if (! in_array($key, $baseKeys, true) && $val === null) {
                        unset($fingerprint[$key]);
                    }
                }

                $lead = LeadSpectrum::query()->createOrFirst(
                    ['import_hash' => hash('sha256', json_encode($fingerprint, JSON_THROW_ON_ERROR))],
                    $data + ['import_filename' => mb_substr(basename($filename), 0, 255), 'uploaded_by' => $userId],
                );
                $created += (int) $lead->wasRecentlyCreated;
            }

            return ['created' => $created, 'duplicates' => count($rows) - $created];
        });
    }

    private function date(mixed $value, int $row, string $header): string
    {
        if (is_numeric($value) && (float) $value >= 1 && (float) $value <= 2958465) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }
        foreach (['!Y-m-d', '!n/j/Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, trim((string) $value));
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }
        $this->fail("Baris {$row}, {$header}: tanggal harus tanggal Excel, YYYY-MM-DD, atau M/D/YYYY.");
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)));
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}