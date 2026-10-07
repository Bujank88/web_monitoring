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
            foreach ($book->getAllSheets() as $candidate) {
                $found = [];
                foreach ($candidate->getRowIterator(1, 1)->current()->getCellIterator() as $cell) {
                    $label = $this->normalize((string) $cell->getValue());
                    foreach (LeadSpectrum::HEADERS as $field => $header) {
                        if ($label === $this->normalize($header)) {
                            if (isset($found[$field])) {
                                $this->fail("Header {$header} tercatat dua kali pada sheet {$candidate->getTitle()}.");
                            }
                            $found[$field] = $cell->getColumn();
                        }
                    }
                }
                if (count($found) === count(LeadSpectrum::HEADERS)) {
                    if ($sheet !== null) {
                        $this->fail('Gunakan satu sheet data dengan header Spectrum agar data yang diimpor jelas.');
                    }
                    $sheet = $candidate;
                    $columns = $found;
                }
            }
            if ($sheet === null) {
                $this->fail('Header pada baris pertama harus memuat: '.implode(', ', LeadSpectrum::HEADERS).'.');
            }
            if ($sheet->getHighestDataRow() > 10001) {
                $this->fail('Maksimal 10.000 baris leads per upload.');
            }
            $rows = [];
            for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
                $data = [];
                foreach (LeadSpectrum::HEADERS as $field => $header) {
                    $cell = $sheet->getCell($columns[$field].$row);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA || $cell->getDataType() === DataType::TYPE_ERROR) {
                        $this->fail("Baris {$row}, {$header}: gunakan nilai biasa, bukan rumus atau error Excel.");
                    }
                    $value = $cell->getValue();
                    $text = trim((string) $value);
                    if ($text === '') {
                        $data[$field] = null;
                    } elseif (in_array($field, ['create_date', 'share_date'], true)) {
                        $data[$field] = $this->date($value, $row, $header);
                    } else {
                        $limit = in_array($field, ['description', 'fu', 'response'], true) ? 65535 : 255;
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

        return DB::transaction(function () use ($rows, $filename, $userId) {
            $created = 0;
            foreach ($rows as $data) {
                $lead = LeadSpectrum::query()->createOrFirst(
                    ['import_hash' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR))],
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
