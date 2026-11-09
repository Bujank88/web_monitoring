<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CalendarController extends Controller
{
    public function index()
    {
        return view('calendar.index');
    }

    public function events()
    {
        $colorMap = [
            'Robert J. Nandjong' => '#20c997', // teal
            'Luky Ghazali' => '#0d6efd',   // blue
            'Fauzia Noviyanti' => '#6610f2',   // purple
            'Nopranda Dirzan' => '#fd7e14',
            'Angga Satria Gusti' => '#198754',
            'Abdul Halim' => '#dc3545',
            'Raden Agie Satria Akbar' => '#6f42c1',
            'Sony Widjaya' => '#17a2b8',
            'Deni Setiawan' => '#e83e8c',
            'Muhammad Arief Syahbana' => '#0dcaf0',
            'Naqsyabandi' => '#adb5bd',
            'Ikrar Dharmawan' => '#795548',
        ];
        return Booking::all()->map(function ($e) use ($colorMap) {

            $color = $colorMap[$e->nama] ?? '#000000';

            return [
                'id'    => $e->id,
                'title' => substr($e->waktu_mulai, 0, 5).' '.$e->nama.' - '.$e->lokasi,

                'start' => $e->tanggal.'T'.$e->waktu_mulai,
                'end'   => $e->tanggal.'T'.$e->waktu_selesai,

                'backgroundColor' => $color,
                'borderColor'     => $color,
                'textColor'       => '#ffffff',

                'extendedProps' => [
                    'nama' => $e->nama,
                    'lokasi' => $e->lokasi,
                    'tanggal' => $e->tanggal,
                    'waktu' => $e->waktu_mulai.' - '.$e->waktu_selesai,
                    'keterangan' => $e->keterangan,
                ]
            ];
        });

    }
    public function store(Request $request)
    {
        Booking::create([
            'nama'       => $request->nama,
            'lokasi'     => $request->lokasi,
            'tanggal'    => $request->tanggal,
            'waktu_mulai'      => $request->start,
            'waktu_selesai'        => $request->end,
            'keterangan' => $request->keterangan,
            'warna'      => $request->color ?? '#000000', // ⬅ default hitam
        ]);

        return response()->json(['success' => true]);
    }

    public function download()
    {
    $events = Booking::orderBy('tanggal')->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->setCellValue('A1', 'Nama');
    $sheet->setCellValue('B1', 'Lokasi');
    $sheet->setCellValue('C1', 'Tanggal');
    $sheet->setCellValue('D1', 'Mulai');
    $sheet->setCellValue('E1', 'Selesai');
    $sheet->setCellValue('F1', 'Keterangan');

    // Style header (optional)
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);

    $row = 2;
    foreach ($events as $e) {
        $sheet->setCellValue('A' . $row, $e->nama);
        $sheet->setCellValue('B' . $row, $e->lokasi);
        $sheet->setCellValue('C' . $row, $e->tanggal);
        $sheet->setCellValue('D' . $row, $e->waktu_mulai);
        $sheet->setCellValue('E' . $row, $e->waktu_selesai);
        $sheet->setCellValue('F' . $row, $e->keterangan);
        $row++;
    }

    // Auto size column
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $fileName = 'calendar_booking_' . date('Ymd_His') . '.xlsx';
    $writer = new Xlsx($spreadsheet);

    // Download response
    return response()->streamDownload(function () use ($writer) {
        $writer->save('php://output');
    }, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
}


    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        
        $booking->update([
            'nama'       => $request->nama,
            'lokasi'     => $request->lokasi,
            'tanggal'    => $request->tanggal,
            'waktu_mulai'      => $request->start,
            'waktu_selesai'        => $request->end,
            'keterangan' => $request->keterangan,
            'warna'      => $request->color ?? '#000000',
        ]);

        return response()->json(['success' => true]);
    }

    public function delete($id)
    {
        Booking::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
