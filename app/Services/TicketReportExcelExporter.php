<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketReportExcelExporter
{
    private const SHEETS = [
        'channel' => ['Channel - Jumlah', 'Channel - Rata-rata', 'Channel - Penanganan'],
        'complaint' => ['Complaint - Jumlah', 'Complaint - Rata-rata', 'Complaint - Penanganan'],
        'summary' => ['Bulanan - Kategori', 'Bulanan - Rata-rata', 'Bulanan - Status', 'Bulanan - Persentase'],
    ];

    public function build(array $report, array $filters, string $type, ?int $pivot = null): Spreadsheet
    {
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);
        $book->getProperties()->setCreator('Tracers')->setTitle('Report Ticketing');
        if ($type === 'all') {
            $this->overview($book, $report, $filters);
        }
        foreach ($type === 'all' ? array_keys(self::SHEETS) : [$type] as $group) {
            foreach ($report[$group] as $index => $table) {
                if ($pivot !== null && $pivot !== $index) {
                    continue;
                }
                $sheet = $this->sheet($book, self::SHEETS[$group][$index], $table['title'], $filters);
                $sheet->setCellValue('A4', $group === 'summary'
                    ? 'Cakupan: tahun dan rentang tanggal; bulan terpilih diperinci per minggu.'
                    : 'Cakupan: tahun, bulan, dan rentang tanggal terpilih.');
                $headers = ['Kategori', ...$table['columns'], 'Grand Total'];
                $sheet->fromArray($headers, null, 'A6');
                $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
                foreach ($table['rows'] as $offset => $row) {
                    $line = $offset + 7;
                    // Treat database labels as literal text, including labels starting with =, +, or @.
                    $sheet->setCellValueExplicit('A'.$line, $row['label'], DataType::TYPE_STRING);
                    $sheet->getStyle('A'.$line)->getAlignment()->setIndent($row['depth']);
                    foreach ($row['cells'] as $column => $value) {
                        $coordinate = Coordinate::stringFromColumnIndex($column + 2).$line;
                        if ($value !== null) {
                            $sheet->setCellValueExplicit($coordinate, $table['metric'] === 'rate' ? $value / 100 : $value, DataType::TYPE_NUMERIC);
                        }
                    }
                    if ($row['subtotal']) {
                        $sheet->getStyle('A'.$line.':'.$lastColumn.$line)->getFont()->setBold(true);
                        $sheet->getStyle('A'.$line.':'.$lastColumn.$line)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFEAF4F6');
                    }
                }
                $lastRow = count($table['rows']) + 6;
                $format = match ($table['metric']) {
                    'rate' => '0.00%', 'average' => '#,##0.00', default => '#,##0',
                };
                $sheet->getStyle('B7:'.$lastColumn.$lastRow)->getNumberFormat()->setFormatCode($format);
                $this->style($sheet, $lastColumn, $lastRow);
            }
        }
        $book->setActiveSheetIndex(0);

        return $book;
    }

    private function sheet(Spreadsheet $book, string $name, string $title, array $filters): Worksheet
    {
        $sheet = new Worksheet($book, $name);
        $book->addSheet($sheet);
        $sheet->setCellValue('A1', $title);
        $sheet->setCellValue('A2', sprintf('Tahun: %d | Bulan: %02d | Pengajuan dari: %s | Sampai: %s',
            $filters['year'], $filters['month'], $filters['date_from'] ?? '-', $filters['date_to'] ?? '-'));
        $sheet->setCellValue('A3', 'Week 1: 1-7; Week 2: 8-14; Week 3: 15-21; Week 4: 22-akhir bulan. Durasi dalam menit.');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->freezePane('B7');
        $sheet->getColumnDimension('A')->setWidth(42);
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(6, 6);

        return $sheet;
    }

    private function style(Worksheet $sheet, string $lastColumn, int $lastRow): void
    {
        $sheet->getStyle('A6:'.$lastColumn.'6')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A6:'.$lastColumn.'6')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF17A2B8');
        $sheet->getStyle('A6:'.$lastColumn.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD9E2E3');
        for ($column = 2; $column <= Coordinate::columnIndexFromString($lastColumn); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(22);
        }
    }

    private function overview(Spreadsheet $book, array $report, array $filters): void
    {
        $sheet = $this->sheet($book, 'Ringkasan', 'Kartu ringkasan: filter tahun, bulan, dan rentang tanggal', $filters);
        $sheet->fromArray(['Indikator', 'Nilai'], null, 'A6');
        foreach ([
            ['Total tiket', $report['filtered_total']], ['Closed', $report['closed']],
            ['Sedang berlangsung', $report['ongoing']], ['Resolution rate', $report['resolution_rate'] === null ? null : $report['resolution_rate'] / 100],
            ['Rata-rata penyelesaian (menit)', $report['average_minutes']], ['Periksa waktu', $report['invalid_times']],
        ] as $offset => $row) {
            $sheet->fromArray($row, null, 'A'.($offset + 7), true);
        }
        $sheet->getStyle('B10')->getNumberFormat()->setFormatCode('0.00%');
        $sheet->getStyle('B11')->getNumberFormat()->setFormatCode('#,##0.00');
        $this->style($sheet, 'B', 12);
        $sheet = $this->sheet($book, 'Tren Bulanan', 'Tren tiket: tahun dan rentang tanggal terpilih', $filters);
        $sheet->fromArray(['Bulan', 'Total tiket', 'Closed', 'Sedang berlangsung'], null, 'A6');
        foreach ($report['trend'] as $offset => $item) {
            $sheet->fromArray([$item['label'], $item['total'], $item['closed'], $item['total'] - $item['closed']], null, 'A'.($offset + 7), true);
        }
        $this->style($sheet, 'D', 18);
    }
}
