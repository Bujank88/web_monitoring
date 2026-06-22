<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GotoController extends Controller
{
    public function report(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');
        $months = $this->buildMonths($month);

        $pageTitle = 'Report Campaign GOTO';
        $dataRoute = 'report-goto.data';
        $exportRoute = 'report-goto.export';
        $filterTitle = 'GOTO REPORT FILTER';
        $tableAlias = 'gr';

        return view('mitra-sbp.report-automatech', compact('months', 'month', 'selectedRemark', 'pageTitle', 'dataRoute', 'exportRoute', 'filterTitle', 'tableAlias'));
    }

    public function reportData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);
        $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

        $baseQuery = $this->uploadedReportBaseQuery($startDate, $endDate);

        $query = (clone $baseQuery)
            ->select(
                DB::raw('DATE(gr.tgl_tayang) as tanggal_iklan'),
                'gr.id_iklan',
                'gr.judul_pesan_iklan',
                'gr.operator_seluler',
                'gr.kategori_iklan',
                'gr.tipe_kanal',
                'gr.sukses as success',
                DB::raw('(COALESCE(gr.gagal, 0) + COALESCE(gr.refunded, 0)) as failed'),
                'gr.refunded',
                'gr.read',
                'gr.click',
                DB::raw('CASE WHEN (COALESCE(gr.sukses, 0) + COALESCE(gr.gagal, 0)) > 0 THEN (COALESCE(gr.sukses, 0) / (COALESCE(gr.sukses, 0) + COALESCE(gr.gagal, 0))) * 100 ELSE 0 END as percentage_read'),
                DB::raw('CASE WHEN COALESCE(gr.read, 0) > 0 THEN (COALESCE(gr.click, 0) / gr.read) * 100 ELSE 0 END as percentage_click'),
                'gr.total_harga',
                'gr.detil_status'
            );

        $summaryRow = (clone $baseQuery)
            ->selectRaw('SUM(COALESCE(gr.sukses, 0)) as total_success')
            ->selectRaw('SUM(COALESCE(gr.gagal, 0) + COALESCE(gr.refunded, 0)) as total_failed')
            ->selectRaw('SUM(COALESCE(gr.refunded, 0)) as total_refunded')
            ->selectRaw('SUM(COALESCE(gr.read, 0)) as total_read')
            ->selectRaw('SUM(COALESCE(gr.click, 0)) as total_click')
            ->selectRaw('SUM(COALESCE(gr.total_harga, 0)) as total_harga')
            ->first();

        $summary = [
            'total_success' => (int) ($summaryRow->total_success ?? 0),
            'total_failed' => (int) ($summaryRow->total_failed ?? 0),
            'total_refunded' => (int) ($summaryRow->total_refunded ?? 0),
            'total_click' => (int) ($summaryRow->total_click ?? 0),
            'total_harga' => (int) ($summaryRow->total_harga ?? 0),
        ];

        return datatables()->of($query)
            ->with('summary', $summary)
            ->editColumn('percentage_read', function ($row) {
                return number_format((float) $row->percentage_read, 2, ',', '.') . '%';
            })
            ->editColumn('percentage_click', function ($row) {
                return number_format((float) $row->percentage_click, 2, ',', '.') . '%';
            })
            ->editColumn('total_harga', function ($row) {
                return 'Rp ' . number_format((float) $row->total_harga, 0, ',', '.');
            })
            ->make(true);
    }

    public function export(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            [$year, $monthNum] = explode('-', $month);
            $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
            $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

            $data = $this->uploadedReportBaseQuery($startDate, $endDate)
                ->select(
                    DB::raw('DATE(gr.tgl_tayang) as tanggal_iklan'),
                    'gr.id_iklan',
                    'gr.judul_pesan_iklan',
                    'gr.operator_seluler',
                    'gr.kategori_iklan',
                    'gr.tipe_kanal',
                    'gr.sukses as success',
                    DB::raw('(COALESCE(gr.gagal, 0) + COALESCE(gr.refunded, 0)) as failed'),
                    'gr.refunded',
                    'gr.read',
                    'gr.click',
                    DB::raw('CASE WHEN (COALESCE(gr.sukses, 0) + COALESCE(gr.gagal, 0)) > 0 THEN (COALESCE(gr.sukses, 0) / (COALESCE(gr.sukses, 0) + COALESCE(gr.gagal, 0))) * 100 ELSE 0 END as percentage_read'),
                    DB::raw('CASE WHEN COALESCE(gr.read, 0) > 0 THEN (COALESCE(gr.click, 0) / gr.read) * 100 ELSE 0 END as percentage_click'),
                    'gr.total_harga',
                    'gr.detil_status'
                )
                ->orderByDesc('gr.tgl_tayang')
                ->orderByDesc('gr.id_iklan')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'REPORT GOTO - ' . $month);
            $sheet->mergeCells('A1:O1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers = [
                'Tanggal Tayang',
                'ID Iklan',
                'Judul Pesan Iklan',
                'Operator Seluler',
                'Kategori Iklan',
                'Tipe Kanal',
                'Success',
                'Failed',
                'Refunded',
                'Read',
                'Click',
                'Percentage Read',
                'Percentage Click',
                'Total Harga',
                'Detil Status',
            ];
            $sheet->fromArray($headers, null, 'A3');

            $sheet->getStyle('A3:O3')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC3545']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ]);

            $rowNum = 4;
            foreach ($data as $row) {
                $sheet->fromArray([
                    $row->tanggal_iklan,
                    $row->id_iklan,
                    $row->judul_pesan_iklan,
                    $row->operator_seluler,
                    $row->kategori_iklan,
                    $row->tipe_kanal,
                    $row->success,
                    $row->failed,
                    $row->refunded,
                    $row->read,
                    $row->click,
                    number_format((float) $row->percentage_read, 2, ',', '.') . '%',
                    number_format((float) $row->percentage_click, 2, ',', '.') . '%',
                    $row->total_harga,
                    $row->detil_status,
                ], null, 'A' . $rowNum);
                $rowNum++;
            }

            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'Report_GOTO_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Export GOTO Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data');
        }
    }

    public function reportSaldo(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');
        $months = $this->buildMonths($month);

        $pageTitle = 'Report Saldo GOTO';
        $dataRoute = 'report-saldo-goto.data';

        return view('mitra-sbp.report-saldo-automatech', compact('months', 'month', 'selectedRemark', 'pageTitle', 'dataRoute'));
    }

    public function reportSaldoData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        $saldoQuery = DB::table('saldo_users')
            ->select(
                'id_user',
                DB::raw('COALESCE(saldo_utama,0) as saldo_utama'),
                DB::raw('COALESCE(saldo_monet,0) as saldo_monet'),
                DB::raw('saldo_exp_utama as saldo_exp_utama'),
                DB::raw('saldo_exp_monet as saldo_exp_monet')
            );

        $baseQuery = DB::table('goto as a')
            ->leftJoinSub($saldoQuery, 'b', function ($join) {
                $join->on('a.reg_id', '=', 'b.id_user');
            })
            ->select(
                'a.email_myads',
                'a.remark',
                'b.saldo_utama',
                'b.saldo_monet',
                'b.saldo_exp_utama',
                'b.saldo_exp_monet'
            );

        if ($request->filled('remark')) {
            $baseQuery->where('a.remark', $request->remark);
        }

        $summaryRows = (clone $baseQuery)
            ->select('a.remark', DB::raw('COUNT(*) as total'))
            ->groupBy('a.remark')
            ->get();

        $summary = [
            'Mitra SBP' => 0,
            'Agency' => 0,
            'Internal' => 0,
        ];

        foreach ($summaryRows as $row) {
            $summary[$row->remark] = (int) $row->total;
        }

        return datatables()->of($baseQuery)
            ->with('summary', $summary)
            ->editColumn('saldo_utama', function ($row) {
                return 'Rp ' . number_format((float) $row->saldo_utama, 0, ',', '.');
            })
            ->editColumn('saldo_monet', function ($row) {
                return 'Rp ' . number_format((float) $row->saldo_monet, 0, ',', '.');
            })
            ->make(true);
    }

    public function uploadReport()
    {
        logUserLogin();

        $uploadPath = storage_path('app/goto-report-uploads');
        $uploadedFiles = collect();

        if (File::exists($uploadPath)) {
            $uploadedFiles = collect(File::files($uploadPath))
                ->sortByDesc(fn ($file) => $file->getMTime())
                ->map(function ($file) {
                    return [
                        'name' => $file->getFilename(),
                        'size' => number_format($file->getSize() / 1024, 2) . ' KB',
                        'uploaded_at' => Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i'),
                    ];
                })
                ->values();
        }

        $templateFile = route('admin.upload.goto-report.template');
        $pageTitle = 'Upload Report GOTO';
        $uploadTitle = 'Upload Excel Report GOTO';
        $uploadDescription = 'Upload file report GOTO khusus admin. Format file mengikuti template yang sudah disediakan.';
        $storeRoute = 'admin.upload.goto-report.store';
        $emptyUploadText = 'Belum ada file report GOTO yang diupload.';
        $templateButtonText = 'Download Template Laporan GOTO';

        return view('admin.upload-automatech-report', compact(
            'uploadedFiles',
            'templateFile',
            'pageTitle',
            'uploadTitle',
            'uploadDescription',
            'storeRoute',
            'emptyUploadText',
            'templateButtonText'
        ));
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['ID IKLAN', 'TGL TAYANG', 'JUDUL PESAN IKLAN', 'OPERATOR SELULER', 'KATEGORI IKLAN', 'TIPE KANAL', 'DETIL STATUS', 'REFUNDED', 'READ', 'CLICK', 'TOTAL HARGA'],
            ['3649001', '11 May 2026', 'GOTO PROMO DUMMY 1', 'TELKOMSEL', 'WABA', 'LBA', 'Sukses: 125 Gagal: 7', '5000', '92', '37', '132000'],
            ['3649002', '12 May 2026', 'GOTO PROMO DUMMY 2', 'TELKOMSEL', 'LBA', 'SMS', 'Sukses: 98 Gagal: 12', '2000', '41', '18', '98000'],
        ], null, 'A1');

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template Laporan GOTO Dummy.xlsx');
    }

    public function storeUploadReport(Request $request)
    {
        $validated = $request->validate([
            'report_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'report_file.required' => 'File report wajib dipilih.',
            'report_file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).',
            'report_file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $validated['report_file'];
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName);

        if (empty($safeName)) {
            $safeName = 'report-goto';
        }

        $fileName = $safeName . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs('goto-report-uploads', $fileName);
        $uploadBatch = now()->format('YmdHis');

        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        if (count($rows) <= 1) {
            return redirect()
                ->route('admin.upload.goto-report')
                ->with('error', 'File Excel tidak memiliki data yang bisa diproses.');
        }

        $payload = [];
        foreach (array_slice($rows, 1) as $row) {
            $idIklan = trim((string) ($row['A'] ?? ''));
            $tglTayang = $this->parseReportDate($row['B'] ?? null);
            $judulPesanIklan = trim((string) ($row['C'] ?? ''));
            $operatorSeluler = trim((string) ($row['D'] ?? ''));
            $kategoriIklan = trim((string) ($row['E'] ?? ''));
            $tipeKanal = trim((string) ($row['F'] ?? ''));
            $detilStatus = trim((string) ($row['G'] ?? ''));
            $refunded = $this->parseReportInteger($row['H'] ?? 0);
            $read = $this->parseReportInteger($row['I'] ?? 0);
            $click = $this->parseReportInteger($row['J'] ?? 0);
            $totalHarga = $this->parseReportInteger($row['K'] ?? 0);

            if ($idIklan === '' && $judulPesanIklan === '' && $detilStatus === '') {
                continue;
            }

            if ($idIklan === '') {
                continue;
            }

            [$sukses, $gagal] = $this->parseReportStatus($detilStatus);

            $payload[] = [
                'id_iklan' => $idIklan,
                'tgl_tayang' => $tglTayang,
                'judul_pesan_iklan' => $judulPesanIklan !== '' ? $judulPesanIklan : null,
                'operator_seluler' => $operatorSeluler !== '' ? $operatorSeluler : null,
                'kategori_iklan' => $kategoriIklan !== '' ? $kategoriIklan : null,
                'tipe_kanal' => $tipeKanal !== '' ? $tipeKanal : null,
                'detil_status' => $detilStatus !== '' ? $detilStatus : null,
                'sukses' => $sukses,
                'gagal' => $gagal,
                'refunded' => $refunded,
                'read' => $read,
                'click' => $click,
                'total_harga' => $totalHarga,
                'source_file_name' => $file->getClientOriginalName(),
                'upload_batch' => $uploadBatch,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($payload)) {
            return redirect()
                ->route('admin.upload.goto-report')
                ->with('error', 'Tidak ada baris valid yang bisa diimport dari file Excel.');
        }

        DB::transaction(function () use ($payload) {
            DB::table('goto_reports')->delete();
            DB::table('goto_reports')->insert($payload);
        });

        return redirect()
            ->route('admin.upload.goto-report')
            ->with('success', count($payload) . ' baris report GOTO berhasil diimport dari ' . $storedPath . '.');
    }

    protected function uploadedReportBaseQuery(Carbon $startDate, Carbon $endDate)
    {
        return DB::table('goto_reports as gr')
            ->whereBetween('gr.tgl_tayang', [
                $startDate->copy()->format('Y-m-d'),
                $endDate->copy()->format('Y-m-d'),
            ]);
    }

    protected function buildMonths(string $selectedMonth): array
    {
        $months = [];
        $baseDate = now()->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $selectedMonth,
            ];
        }

        return $months;
    }

    protected function parseReportDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            }

            $dateString = trim((string) $value);

            foreach (['m/d/Y', 'n/j/Y', 'd/m/Y', 'j/n/Y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $dateString)->format('Y-m-d');
                } catch (\Throwable $e) {
                }
            }

            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseReportInteger($value): int
    {
        $normalized = preg_replace('/[^\d]/', '', (string) $value);

        return $normalized === '' ? 0 : (int) $normalized;
    }

    protected function parseReportStatus(?string $detailStatus): array
    {
        $success = 0;
        $failed = 0;
        $detailStatus = (string) $detailStatus;

        if (preg_match('/Sukses\s*:\s*([\d\.,]+)/i', $detailStatus, $matches)) {
            $success = $this->parseReportInteger($matches[1]);
        }

        if (preg_match('/Gagal\s*:\s*([\d\.,]+)/i', $detailStatus, $matches)) {
            $failed = $this->parseReportInteger($matches[1]);
        }

        return [$success, $failed];
    }
}
