<?php

namespace App\Http\Controllers;

use App\Models\OneSynergy\CampaignReport as OneSynergyCampaignReport;
use App\Models\OneSynergy\PaymentTransaction as OneSynergyPaymentTransaction;
use App\Models\OneSynergy\Referral as OneSynergyReferral;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OneSynergyReportController extends Controller
{
    private const MONITORING_EMAIL = 'arief_azhar@ptkam.co.id';
    private const REFERRAL_SENDER_ID = 'REG-DO-000000662035';

    public function monitoringSaldo(Request $request)
    {
        logUserLogin();
        $month = $request->get('month', now()->format('Y-m'));
        $history = $this->monitoringSaldoHistory($month);

        return view('one_synergy.monitoring_saldo', [
            'pageTitle' => 'Monitoring Saldo 1Synergy',
            'month' => $month,
            'months' => $this->monthOptions($month),
            'monitoringEmail' => self::MONITORING_EMAIL,
            'senderId' => self::REFERRAL_SENDER_ID,
            'remainingBalance' => $history['remaining_balance'],
            'openingBalance' => $history['opening_balance'],
            'totalIn' => $history['total_in'],
            'totalOut' => $history['total_out'],
            'endingBalance' => $history['ending_balance'],
            'historyRows' => $history['rows'],
        ]);
    }

    public function referralIndex()
    {
        logUserLogin();

        return view('cdsi.referral-index', [
            'pageTitle' => 'Referral 1Synergy',
            'brandLabel' => '1Synergy',
            'referralDataUrl' => route('one-synergy.referrals.data'),
            'referralGenerateUrl' => route('one-synergy.referrals.generate'),
            'referralStoreUrl' => route('one-synergy.referrals.store'),
            'referralStatusBaseUrl' => url('one-synergy/referrals'),
        ]);
    }

    public function referralData()
    {
        $query = OneSynergyReferral::query()
            ->select('id', 'name', 'referral_code', 'status', 'created_at')
            ->orderByDesc('id');

        return datatables()->of($query)
            ->addIndexColumn()
            ->editColumn('status', fn ($row) => strtolower((string) $row->status) === 'active'
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-secondary">Non Active</span>')
            ->editColumn('created_at', fn ($row) => $row->created_at ? Carbon::parse($row->created_at)->format('d-m-Y H:i') : '-')
            ->addColumn('action', function ($row) {
                $active = strtolower((string) $row->status) === 'active';
                return '<button type="button" class="btn btn-sm ' . ($active ? 'btn-danger' : 'btn-success')
                    . ' btnToggleReferralStatus" data-id="' . e($row->id) . '" data-name="' . e($row->name)
                    . '" data-status="' . ($active ? 'non_active' : 'active') . '">'
                    . ($active ? 'Non Active' : 'Active') . '</button>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function generateReferralCode(Request $request)
    {
        return response()->json(['referral_code' => $this->uniqueReferralCode((string) $request->get('name', ''))]);
    }

    public function storeReferral(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'referral_code' => 'required|string|max:50|unique:' . (new OneSynergyReferral())->getTable() . ',referral_code',
        ]);

        OneSynergyReferral::query()->create([
            'name' => trim($validated['name']),
            'referral_code' => strtoupper(trim($validated['referral_code'])),
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Referral 1Synergy berhasil ditambahkan.']);
    }

    public function updateReferralStatus(Request $request, int $id)
    {
        $validated = $request->validate(['status' => 'required|in:active,non_active']);
        $updated = OneSynergyReferral::query()->whereKey($id)->update([
            'status' => $validated['status'],
            'updated_at' => now(),
        ]);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Referral 1Synergy tidak ditemukan atau tidak berubah.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => $validated['status'] === 'active'
                ? 'Referral 1Synergy berhasil diaktifkan.'
                : 'Referral 1Synergy berhasil dinonaktifkan.',
        ]);
    }

    public function referralTopup(Request $request)
    {
        logUserLogin();
        $month = $request->get('month', now()->format('Y-m'));

        return view('cdsi.referral-topup-channel', [
            'months' => $this->monthOptions($month),
            'month' => $month,
            'channels' => $this->referralChannels(),
            'pageTitle' => 'Daily Top Up Referral 1Synergy',
            'brandLabel' => '1Synergy',
            'topupDataUrl' => route('one-synergy.referral-topup.data'),
            'topupDetailUrl' => route('one-synergy.referral-topup.detail-data'),
            'updateInvoiceUrl' => route('one-synergy.referral-topup.update-myads-invoice'),
        ]);
    }

    public function referralTopupData(Request $request)
    {
        return datatables()->of(collect($this->referralTopupRows($request->get('month', now()->format('Y-m')))))->make(true);
    }

    public function referralTopupDetailData(Request $request)
    {
        $rows = $this->paymentTransactionsForMonth($request->get('month', now()->format('Y-m')))
            ->map(fn ($transaction) => [
                'transaction_id_raw' => $transaction->transaction_id,
                'paid_date' => $transaction->payment_datetime ? Carbon::parse($transaction->payment_datetime)->format('d-m-Y') : '-',
                'transaction_id' => $transaction->transaction_id ?: '-',
                'customer_email' => $transaction->customer_email ?: '-',
                'amount' => number_format((float) $transaction->transaction_amount, 0, ',', '.'),
                'id_transaksi_myads' => $transaction->id_transaksi_myads,
                'transfer_status' => ($transaction->is_received || $transaction->id_transaksi_myads) ? 'Receive' : 'Pending',
            ]);

        return datatables()->of($rows)->addIndexColumn()
            ->addColumn('action', function ($row) {
                $hasInvoice = !empty($row['id_transaksi_myads']);
                return '<button type="button" class="btn btn-sm ' . ($hasInvoice ? 'btn-warning' : 'btn-primary')
                    . ' btnUpdateMyadsInvoice" data-transaction-id="' . e($row['transaction_id_raw'])
                    . '" data-myads-invoice="' . e($row['id_transaksi_myads'] ?? '')
                    . '" data-display-transaction-id="' . e($row['transaction_id']) . '">'
                    . ($hasInvoice ? 'Edit Invoice' : 'Input Invoice') . '</button>';
            })->rawColumns(['action'])->make(true);
    }

    public function updateMyadsInvoice(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'id_transaksi_myads' => 'required|string|max:255',
        ]);
        $updated = OneSynergyPaymentTransaction::query()->where('transaction_id', $validated['transaction_id'])->update([
            'id_transaksi_myads' => trim($validated['id_transaksi_myads']),
            'updated_at' => now(),
        ]);

        return $updated
            ? response()->json(['success' => true, 'message' => 'No Invoice MyAds berhasil disimpan.'])
            : response()->json(['success' => false, 'message' => 'Data transaksi tidak ditemukan atau tidak berubah.'], 404);
    }


    public function index(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $months = [];
        $baseDate = now()->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);
            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month,
            ];
        }

        return view('cdsi.report-cdsi', [
            'months' => $months,
            'month' => $month,
            'selectedRemark' => '',
            'pageTitle' => 'Report Campaign 1Synergy',
            'brandLabel' => '1Synergy',
            'dataUrl' => route('one-synergy.report.data'),
            'exportUrl' => route('one-synergy.report.export'),
            'showMerchantFilter' => true,
            'merchants' => [],
            'selectedMerchant' => '',
        ]);
    }

    private function baseQuery(Carbon $startDate, Carbon $endDate)
    {
        if (!Schema::hasTable((new OneSynergyCampaignReport())->getTable())) {
            return null;
        }

        // Keep the `cr` alias used by the shared CDSI DataTable column config.
        return OneSynergyCampaignReport::query()->from((new OneSynergyCampaignReport())->getTable() . ' as cr')
            ->whereBetween('cr.tgl_tayang', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ]);
    }

    private function period(Request $request): array
    {
        $month = $request->get('month', now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $month);

        return [$month, $date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }

    public function data(Request $request)
    {
        [, $startDate, $endDate] = $this->period($request);
        $baseQuery = $this->baseQuery($startDate, $endDate);

        if ($baseQuery === null) {
            return datatables()->of(collect([]))->with('summary', $this->emptySummary())->make(true);
        }

        $query = (clone $baseQuery)->select(
            DB::raw('DATE(cr.tgl_tayang) as tanggal_iklan'),
            'cr.id_iklan',
            'cr.judul_pesan_iklan',
            'cr.operator_seluler',
            'cr.kategori_iklan',
            'cr.tipe_kanal',
            'cr.sukses as success',
            DB::raw('(COALESCE(cr.gagal, 0) + COALESCE(cr.refunded, 0)) as failed'),
            'cr.read',
            'cr.click',
            'cr.total_harga'
        );

        $summaryRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_campaign')
            ->selectRaw('SUM(COALESCE(cr.sukses, 0)) as total_success')
            ->selectRaw('SUM(COALESCE(cr.gagal, 0) + COALESCE(cr.refunded, 0)) as total_failed')
            ->selectRaw('SUM(COALESCE(cr.total_harga, 0)) as total_harga')
            ->first();

        $summary = [
            'total_campaign' => (int) ($summaryRow->total_campaign ?? 0),
            'total_success' => (int) ($summaryRow->total_success ?? 0),
            'total_failed' => (int) ($summaryRow->total_failed ?? 0),
            'total_harga' => (int) ($summaryRow->total_harga ?? 0),
        ];

        return datatables()->of($query)
            ->with('summary', $summary)
            ->editColumn('total_harga', fn ($row) => 'Rp ' . number_format((float) $row->total_harga, 0, ',', '.'))
            ->make(true);
    }

    private function emptySummary(): array
    {
        return ['total_campaign' => 0, 'total_success' => 0, 'total_failed' => 0, 'total_harga' => 0];
    }

    public function export(Request $request)
    {
        [$month, $startDate, $endDate] = $this->period($request);
        $baseQuery = $this->baseQuery($startDate, $endDate);

        if ($baseQuery === null) {
            return redirect()->back()->with('error', 'Tabel 1Synergy belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $baseQuery->select(
            DB::raw('DATE(cr.tgl_tayang) as tanggal_iklan'),
            'cr.id_iklan', 'cr.judul_pesan_iklan', 'cr.operator_seluler',
            'cr.kategori_iklan', 'cr.tipe_kanal', 'cr.sukses as success',
            DB::raw('(COALESCE(cr.gagal, 0) + COALESCE(cr.refunded, 0)) as failed'),
            'cr.refunded', 'cr.read', 'cr.click', 'cr.total_harga', 'cr.detil_status',
            DB::raw('CASE WHEN COALESCE(cr.sukses, 0) > 0 THEN (COALESCE(cr.read, 0) / cr.sukses) * 100 ELSE 0 END as percentage_read'),
            DB::raw('CASE WHEN COALESCE(cr.read, 0) > 0 THEN (COALESCE(cr.click, 0) / cr.read) * 100 ELSE 0 END as percentage_click')
        )->orderByDesc('cr.tgl_tayang')->orderByDesc('cr.id_iklan')->get();

        if ($data->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data 1Synergy untuk di-export.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'REPORT 1SYNERGY - ' . $month)->mergeCells('A1:O1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['Tanggal Tayang', 'ID Iklan', 'Judul Pesan Iklan', 'Operator Seluler', 'Kategori Iklan', 'Tipe Kanal', 'Success', 'Failed', 'Refunded', 'Read', 'Click', 'Percentage Read', 'Percentage Click', 'Total Harga', 'Detil Status'];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:O3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rowNum = 4;
        foreach ($data as $row) {
            $sheet->fromArray([
                $row->tanggal_iklan, $row->id_iklan, $row->judul_pesan_iklan,
                $row->operator_seluler, $row->kategori_iklan, $row->tipe_kanal,
                $row->success, $row->failed, $row->refunded, $row->read, $row->click,
                round((float) $row->percentage_read, 2) . '%',
                round((float) $row->percentage_click, 2) . '%',
                $row->total_harga, $row->detil_status,
            ], null, 'A' . $rowNum++);
        }

        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'Report_1Synergy_' . $month . '.xlsx');
    }

    public function upload()
    {
        logUserLogin();
        $uploadPath = storage_path('app/one-synergy-report-uploads');
        $uploadedFiles = collect();

        if (File::exists($uploadPath)) {
            $uploadedFiles = collect(File::files($uploadPath))->sortByDesc(fn ($file) => $file->getMTime())
                ->map(fn ($file) => [
                    'name' => $file->getFilename(),
                    'size' => number_format($file->getSize() / 1024, 2) . ' KB',
                    'uploaded_at' => Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i'),
                ])->values();
        }

        return view('admin.upload-cdsi-report', [
            'uploadedFiles' => $uploadedFiles,
            'templateFile' => route('one-synergy.upload.template'),
            'uploadAction' => route('one-synergy.upload.store'),
            'brandLabel' => '1Synergy',
        ]);
    }

    public function template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['ID IKLAN', 'TGL TAYANG', 'JUDUL PESAN IKLAN', 'OPERATOR SELULER', 'KATEGORI IKLAN', 'TIPE KANAL', 'DETIL STATUS', 'REFUNDED', 'READ', 'CLICK', 'TOTAL HARGA'],
            ['1849001', '11 May 2026', 'WABA 1SYNERGY DUMMY 1', 'TELKOMSEL', 'WABA', 'LBA', 'Sukses: 105 Gagal: 9', '3000', '75', '29', '118000'],
        ], null, 'A1');
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'Template Laporan 1Synergy.xlsx');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        if (!Schema::hasTable((new OneSynergyCampaignReport())->getTable())) {
            return redirect()->route('one-synergy.upload')->with('error', 'Tabel 1Synergy belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $file = $validated['report_file'];
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'report-1synergy';
        $fileName = $safeName . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs('one-synergy-report-uploads', $fileName);
        $rows = IOFactory::load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, true);
        $payload = [];

        foreach (array_slice($rows, 1) as $row) {
            $idIklan = trim((string) ($row['A'] ?? ''));
            $judul = trim((string) ($row['C'] ?? ''));
            $status = trim((string) ($row['G'] ?? ''));
            if ($idIklan === '') {
                continue;
            }
            [$sukses, $gagal] = $this->parseStatus($status);
            $payload[] = [
                'id_iklan' => $idIklan,
                'tgl_tayang' => $this->parseDate($row['B'] ?? null),
                'judul_pesan_iklan' => $judul ?: null,
                'operator_seluler' => trim((string) ($row['D'] ?? '')) ?: null,
                'kategori_iklan' => trim((string) ($row['E'] ?? '')) ?: null,
                'tipe_kanal' => trim((string) ($row['F'] ?? '')) ?: null,
                'detil_status' => $status ?: null,
                'sukses' => $sukses,
                'gagal' => $gagal,
                'refunded' => $this->parseInteger($row['H'] ?? 0),
                'read' => $this->parseInteger($row['I'] ?? 0),
                'click' => $this->parseInteger($row['J'] ?? 0),
                'total_harga' => $this->parseInteger($row['K'] ?? 0),
                'source_file_name' => $file->getClientOriginalName(),
                'upload_batch' => now()->format('YmdHis'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($payload === []) {
            return redirect()->route('one-synergy.upload')->with('error', 'Tidak ada baris valid dalam file Excel.');
        }

        DB::transaction(function () use ($payload) {
            OneSynergyCampaignReport::query()->delete();
            OneSynergyCampaignReport::query()->insert($payload);
        });

        return redirect()->route('one-synergy.upload')
            ->with('success', count($payload) . ' baris report 1Synergy berhasil diimport dari ' . $storedPath . '.');
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            }
            return Carbon::parse(trim((string) $value))->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function parseInteger($value): int
    {
        $normalized = preg_replace('/[^\d]/', '', (string) $value);
        return $normalized === '' ? 0 : (int) $normalized;
    }

    private function parseStatus(string $status): array
    {
        preg_match('/sukses\s*:\s*([\d.,]+)/i', $status, $successMatch);
        preg_match('/gagal\s*:\s*([\d.,]+)/i', $status, $failedMatch);

        return [
            $this->parseInteger($successMatch[1] ?? 0),
            $this->parseInteger($failedMatch[1] ?? 0),
        ];
    }

    private function monitoringSaldoHistory(string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $startDate = $monthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00');
        $endDate = $monthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59');
        $targetEmail = strtolower(trim(self::MONITORING_EMAIL));

        $openingIn = (float) DB::table('report_balance_top_up')
            ->whereRaw('LOWER(TRIM(email_client)) = ?', [$targetEmail])
            ->where('tgl_transaksi', '<', $startDate)
            ->sum(DB::raw('CAST(COALESCE(amount, 0) AS DECIMAL(15,2))'));
        $openingOut = (float) DB::table('transaksi_balance_transfer')
            ->where('id_klien_pengirim', self::REFERRAL_SENDER_ID)
            ->where('tanggal', '<', $startDate)
            ->sum(DB::raw('CAST(COALESCE(jumlah, 0) AS DECIMAL(15,2))'));
        $openingBalance = $openingIn - $openingOut;

        $remainingIn = (float) DB::table('report_balance_top_up')
            ->whereRaw('LOWER(TRIM(email_client)) = ?', [$targetEmail])
            ->sum(DB::raw('CAST(COALESCE(amount, 0) AS DECIMAL(15,2))'));
        $remainingOut = (float) DB::table('transaksi_balance_transfer')
            ->where('id_klien_pengirim', self::REFERRAL_SENDER_ID)
            ->sum(DB::raw('CAST(COALESCE(jumlah, 0) AS DECIMAL(15,2))'));

        $incomingRows = DB::table('report_balance_top_up')->select([
            DB::raw("'Masuk' as transaction_type"),
            DB::raw("'Top Up' as source"),
            'tgl_transaksi as transaction_datetime',
            'email_client as reference_email',
            DB::raw('CAST(COALESCE(amount, 0) AS DECIMAL(15,2)) as amount_in'),
            DB::raw('CAST(0 AS DECIMAL(15,2)) as amount_out'),
        ])->whereRaw('LOWER(TRIM(email_client)) = ?', [$targetEmail])
            ->whereBetween('tgl_transaksi', [$startDate, $endDate])->get();

        $outgoingRows = DB::table('transaksi_balance_transfer')->select([
            DB::raw("'Keluar' as transaction_type"),
            DB::raw("'Balance Transfer' as source"),
            'tanggal as transaction_datetime',
            'email_penerima as reference_email',
            DB::raw('CAST(0 AS DECIMAL(15,2)) as amount_in'),
            DB::raw('CAST(COALESCE(jumlah, 0) AS DECIMAL(15,2)) as amount_out'),
        ])->where('id_klien_pengirim', self::REFERRAL_SENDER_ID)
            ->whereBetween('tanggal', [$startDate, $endDate])->get();

        $runningBalance = $openingBalance;
        $totalIn = 0;
        $totalOut = 0;
        $rows = $incomingRows->concat($outgoingRows)
            ->sortBy(fn ($row) => Carbon::parse($row->transaction_datetime)->timestamp)
            ->values()
            ->map(function ($row) use (&$runningBalance, &$totalIn, &$totalOut) {
                $amountIn = (float) $row->amount_in;
                $amountOut = (float) $row->amount_out;
                $totalIn += $amountIn;
                $totalOut += $amountOut;
                $runningBalance += $amountIn - $amountOut;

                return [
                    'transaction_date' => $row->transaction_datetime
                        ? Carbon::parse($row->transaction_datetime)->format('d-m-Y H:i') : '-',
                    'transaction_type' => $row->transaction_type,
                    'source' => $row->source,
                    'reference_email' => $row->reference_email ?: '-',
                    'amount_in' => $amountIn,
                    'amount_out' => $amountOut,
                    'running_balance' => $runningBalance,
                ];
            })->all();

        return [
            'remaining_balance' => $remainingIn - $remainingOut,
            'opening_balance' => $openingBalance,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'ending_balance' => $openingBalance + $totalIn - $totalOut,
            'rows' => $rows,
        ];
    }

    private function uniqueReferralCode(string $name): string
    {
        $parts = preg_split('/\s+/', trim(strtoupper(preg_replace('/[^A-Za-z0-9\s]/', ' ', $name))), -1, PREG_SPLIT_NO_EMPTY);
        $prefix = substr(collect($parts)->map(fn ($part) => substr($part, 0, 2))->implode(''), 0, 6);
        $prefix = $prefix === '' ? 'REF' : (strlen($prefix) < 3 ? str_pad($prefix, 3, 'X') : $prefix);

        do {
            $code = $prefix . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        } while (OneSynergyReferral::query()->where('referral_code', $code)->exists());

        return $code;
    }

    private function monthOptions(string $selectedMonth): array
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

    private function referralChannels(): array
    {
        $channels = [[
            'key' => 'one_synergy',
            'label' => '1Synergy',
            'color' => '#dbeafe',
            'referral_code' => null,
        ]];

        foreach (OneSynergyReferral::query()->where('status', 'active')->orderBy('name')->get() as $referral) {
            $channels[] = [
                'key' => 'ref_' . $referral->id,
                'label' => $referral->name,
                'color' => '#fff3cd',
                'referral_code' => strtoupper((string) $referral->referral_code),
            ];
        }
        return $channels;
    }

    private function paymentTransactionsForMonth(string $month)
    {
        $date = Carbon::createFromFormat('Y-m', $month);
        return $this->paymentTransactions(
            $date->copy()->startOfMonth()->format('Y-m-d 00:00:00'),
            $date->copy()->endOfMonth()->format('Y-m-d 23:59:59')
        );
    }

    private function paymentTransactions(string $startDate, string $endDate, bool $receivedOnly = false)
    {
        $query = OneSynergyPaymentTransaction::query()
            ->from((new OneSynergyPaymentTransaction())->getTable() . ' as pt')
            ->leftJoin((new OneSynergyReferral())->getTable() . ' as sr', DB::raw('UPPER(TRIM(pt.referral_code))'), '=', DB::raw('UPPER(TRIM(sr.referral_code))'))
            ->select(
                DB::raw('DATE(COALESCE(pt.payment_date, pt.transaction_date)) as trx_date'),
                'pt.transaction_id', 'pt.user_id', 'pt.customer_name', 'pt.customer_email',
                'pt.referral_code', 'pt.id_transaksi_myads', 'sr.id as referral_id',
                DB::raw('CAST(pt.transaction_amount AS DECIMAL(15,2)) as transaction_amount'),
                DB::raw('COALESCE(pt.payment_date, pt.transaction_date) as payment_datetime'),
                DB::raw("EXISTS(SELECT 1 FROM transaksi_balance_transfer tbt
                    WHERE LOWER(tbt.status) = 'paid'
                      AND LOWER(TRIM(tbt.email_penerima)) = LOWER(TRIM(pt.customer_email))
                      AND CAST(tbt.jumlah AS DECIMAL(15,2)) = CAST(pt.transaction_amount AS DECIMAL(15,2))) as is_received")
            )
            ->whereRaw('LOWER(pt.status) = ?', ['success'])
            ->whereBetween(DB::raw('COALESCE(pt.payment_date, pt.transaction_date)'), [$startDate, $endDate])
            ->where(fn ($builder) => $builder->whereNull('sr.id')->orWhere('sr.status', 'active'));

        if ($receivedOnly) {
            $query->where(function ($builder) {
                $builder->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))->from('transaksi_balance_transfer as tbt')
                        ->whereRaw('LOWER(tbt.status) = ?', ['paid'])
                        ->whereRaw('LOWER(TRIM(tbt.email_penerima)) = LOWER(TRIM(pt.customer_email))')
                        ->whereRaw('CAST(tbt.jumlah AS DECIMAL(15,2)) = CAST(pt.transaction_amount AS DECIMAL(15,2))');
                })->orWhereNotNull('pt.id_transaksi_myads');
            });
        }

        return $query->orderByDesc(DB::raw('COALESCE(pt.payment_date, pt.transaction_date)'))->get();
    }

    private function referralTopupRows(string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $channels = $this->referralChannels();
        $channelByReferral = collect($channels)->filter(fn ($channel) => $channel['referral_code'])->keyBy('referral_code');
        $channelKeys = collect($channels)->pluck('key')->all();
        $periodEnd = $monthDate->isSameMonth(today()) ? today() : $monthDate->copy()->endOfMonth();
        $grouped = [];

        for ($cursor = $monthDate->copy()->startOfMonth(); $cursor->lte($periodEnd); $cursor->addDay()) {
            foreach ($channelKeys as $key) {
                $grouped[$cursor->format('Y-m-d')][$key] = ['settlement' => 0, 'users' => []];
            }
        }

        $transactions = $this->paymentTransactions(
            $monthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00'),
            $monthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59'),
            true
        );
        foreach ($transactions as $transaction) {
            $dateKey = (string) $transaction->trx_date;
            $matched = $channelByReferral->get(strtoupper(trim((string) $transaction->referral_code)));
            $channelKey = $matched['key'] ?? 'one_synergy';
            $grouped[$dateKey][$channelKey]['settlement'] += (float) $transaction->transaction_amount;
            $grouped[$dateKey][$channelKey]['users'][] = $transaction->user_id;
        }

        $rows = [];
        $grand = collect($channelKeys)->mapWithKeys(fn ($key) => [$key => ['settlement' => 0, 'users' => []]])->all();
        foreach ($grouped as $date => $values) {
            $row = ['date' => Carbon::parse($date)->translatedFormat('d F Y')];
            $allUsers = [];
            $total = 0;
            foreach ($channelKeys as $key) {
                $users = array_unique(array_filter($values[$key]['users']));
                $amount = (float) $values[$key]['settlement'];
                $row[$key . '_user'] = count($users);
                $row[$key . '_settle'] = number_format($amount, 0, ',', '.');
                $allUsers = array_merge($allUsers, $users);
                $total += $amount;
                $grand[$key]['users'] = array_merge($grand[$key]['users'], $users);
                $grand[$key]['settlement'] += $amount;
            }
            $row['total_user'] = count(array_unique($allUsers));
            $row['total'] = number_format($total, 0, ',', '.');
            $rows[] = $row;
        }

        if ($rows !== []) {
            $totalRow = ['date' => 'Total Keseluruhan'];
            $allUsers = [];
            $allAmount = 0;
            foreach ($channelKeys as $key) {
                $users = array_unique(array_filter($grand[$key]['users']));
                $totalRow[$key . '_user'] = count($users);
                $totalRow[$key . '_settle'] = number_format($grand[$key]['settlement'], 0, ',', '.');
                $allUsers = array_merge($allUsers, $users);
                $allAmount += $grand[$key]['settlement'];
            }
            $totalRow['total_user'] = count(array_unique($allUsers));
            $totalRow['total'] = number_format($allAmount, 0, ',', '.');
            $rows[] = $totalRow;
        }
        return $rows;
    }

}
