<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CdsiReportController extends Controller
{
    public function referralIndex()
    {
        logUserLogin();

        $pageTitle = 'Referral CDSI';

        return view('cdsi.referral-index', compact('pageTitle'));
    }

    public function referralTopupChannel(Request $request)
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

        $channels = $this->getCdsiReferralChannels();
        $pageTitle = 'Daily Top Up Referral CDSI';

        return view('cdsi.referral-topup-channel', compact('months', 'month', 'channels', 'pageTitle'));
    }

    public function referralTopupChannelData(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $rows = $this->getCdsiReferralTopupRows($month);

            return datatables()->of(collect($rows))->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in referralTopupChannelData: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function referralTopupChannelDetailData(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $rows = $this->getCdsiReferralTopupDetailRows($month);
            $canManageInvoice = in_array(Auth::user()->role, ['Admin', 'KSS', 'kss'], true);

            return datatables()->of(collect($rows))
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($canManageInvoice) {
                    if (!$canManageInvoice) {
                        return '';
                    }

                    $buttonLabel = !empty($row['id_transaksi_myads']) ? 'Edit Invoice' : 'Input Invoice';
                    $buttonClass = !empty($row['id_transaksi_myads']) ? 'btn-warning' : 'btn-primary';

                    return '<button type="button" class="btn btn-sm ' . $buttonClass . ' btnUpdateMyadsInvoice" '
                        . 'data-transaction-id="' . e($row['transaction_id_raw']) . '" '
                        . 'data-myads-invoice="' . e($row['id_transaksi_myads'] ?? '') . '" '
                        . 'data-display-transaction-id="' . e($row['transaction_id']) . '">'
                        . $buttonLabel
                        . '</button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in referralTopupChannelDetailData: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function referralData(Request $request)
    {
        $query = DB::table('cdsi_referrals')
            ->select('id', 'name', 'referral_code', 'status', 'created_at')
            ->orderByDesc('id');

        return datatables()->of($query)
            ->addIndexColumn()
            ->editColumn('status', function ($row) {
                $isActive = strtolower((string) $row->status) === 'active';

                return $isActive
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Non Active</span>';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at
                    ? Carbon::parse($row->created_at)->format('d-m-Y H:i')
                    : '-';
            })
            ->addColumn('action', function ($row) {
                $isActive = strtolower((string) $row->status) === 'active';
                $targetStatus = $isActive ? 'non_active' : 'active';
                $buttonLabel = $isActive ? 'Non Active' : 'Active';
                $buttonClass = $isActive ? 'btn-danger' : 'btn-success';

                return '<button type="button" class="btn btn-sm ' . $buttonClass . ' btnToggleReferralStatus" '
                    . 'data-id="' . e($row->id) . '" '
                    . 'data-name="' . e($row->name) . '" '
                    . 'data-status="' . e($targetStatus) . '">'
                    . $buttonLabel
                    . '</button>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function generateReferralCode(Request $request)
    {
        $name = (string) $request->get('name', '');

        return response()->json([
            'referral_code' => $this->generateUniqueCdsiReferralCode($name),
        ]);
    }

    public function updateReferralTopupMyadsInvoice(Request $request)
    {
        if (!in_array(Auth::user()->role, ['Admin', 'KSS', 'kss'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'id_transaksi_myads' => 'required|string|max:255',
        ], [
            'transaction_id.required' => 'ID transaksi wajib dipilih.',
            'id_transaksi_myads.required' => 'No Invoice MyAds wajib diisi.',
        ]);

        $updated = DB::table('payment_transactions_cdsi')
            ->where('transaction_id', $validated['transaction_id'])
            ->update([
                'id_transaksi_myads' => trim($validated['id_transaksi_myads']),
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Data transaksi tidak ditemukan atau tidak ada perubahan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'No Invoice MyAds berhasil disimpan.',
        ]);
    }

    public function storeReferral(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'referral_code' => 'required|string|max:50|unique:cdsi_referrals,referral_code',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'referral_code.required' => 'Code referral wajib diisi.',
            'referral_code.unique' => 'Code referral sudah digunakan.',
        ]);

        DB::table('cdsi_referrals')->insert([
            'name' => trim($validated['name']),
            'referral_code' => strtoupper(trim($validated['referral_code'])),
            'status' => 'active',
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Referral CDSI berhasil ditambahkan.',
        ]);
    }

    public function updateReferralStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,non_active',
        ], [
            'status.required' => 'Status referral wajib dipilih.',
            'status.in' => 'Status referral tidak valid.',
        ]);

        $updated = DB::table('cdsi_referrals')
            ->where('id', $id)
            ->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Referral CDSI tidak ditemukan atau tidak ada perubahan status.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => $validated['status'] === 'active'
                ? 'Referral CDSI berhasil diaktifkan.'
                : 'Referral CDSI berhasil dinonaktifkan dan tidak akan dihitung di TopUp Referral CDSI.',
        ]);
    }

    protected function generateUniqueCdsiReferralCode(string $name): string
    {
        $prefix = $this->buildReferralPrefixFromName($name);

        do {
            $code = $prefix . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        } while (DB::table('cdsi_referrals')->where('referral_code', $code)->exists());

        return $code;
    }

    protected function buildReferralPrefixFromName(string $name): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9\s]/', ' ', $name));
        $parts = preg_split('/\s+/', trim($normalized), -1, PREG_SPLIT_NO_EMPTY);

        $prefix = collect($parts)
            ->map(function ($part) {
                return substr($part, 0, 2);
            })
            ->implode('');

        $prefix = substr($prefix, 0, 6);

        if ($prefix === '') {
            return 'REF';
        }

        return strlen($prefix) < 3
            ? str_pad($prefix, 3, 'X')
            : $prefix;
    }

    protected function getCdsiReferralChannels(): array
    {
        $channels = [[
            'key' => 'cdsi',
            'label' => 'CDSI',
            'color' => '#d1ecf1',
            'referral_code' => null,
        ]];

        $referrals = DB::table('cdsi_referrals')
            ->select('id', 'name', 'referral_code', 'status')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        foreach ($referrals as $referral) {
            $channels[] = [
                'key' => 'ref_' . $referral->id,
                'label' => $referral->name,
                'color' => '#fff3cd',
                'referral_code' => strtoupper((string) $referral->referral_code),
            ];
        }

        return $channels;
    }

    protected function getCdsiReferralTopupRows(string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $startDate = $monthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00');
        $endDate = $monthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59');

        $channels = $this->getCdsiReferralChannels();
        $channelKeys = collect($channels)->pluck('key')->values()->all();
        $channelByReferral = collect($channels)
            ->filter(function ($channel) {
                return !empty($channel['referral_code']);
            })
            ->keyBy('referral_code');

        $grouped = [];
        $periodCursor = $monthDate->copy()->startOfMonth();
        $today = Carbon::today();
        $periodEnd = $monthDate->month === $today->month && $monthDate->year === $today->year
            ? $today->copy()
            : $monthDate->copy()->endOfMonth();

        while ($periodCursor->lte($periodEnd)) {
            $dateKey = $periodCursor->format('Y-m-d');
            $grouped[$dateKey] = [];

            foreach ($channelKeys as $key) {
                $grouped[$dateKey][$key] = [
                    'settlement' => 0,
                    'users' => [],
                ];
            }

            $periodCursor->addDay();
        }

        $transactions = $this->getMatchedCdsiTransactions($startDate, $endDate);

        foreach ($transactions as $transaction) {
            $dateKey = (string) $transaction->trx_date;
            $referralCode = strtoupper(trim((string) $transaction->referral_code));
            $matchedChannel = $channelByReferral->get($referralCode);
            $channelKey = $matchedChannel['key'] ?? 'cdsi';

            if (!isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [];
                foreach ($channelKeys as $key) {
                    $grouped[$dateKey][$key] = [
                        'settlement' => 0,
                        'users' => [],
                    ];
                }
            }

            $grouped[$dateKey][$channelKey]['settlement'] += (float) $transaction->transaction_amount;
            $grouped[$dateKey][$channelKey]['users'][] = $transaction->user_id;
        }

        ksort($grouped);

        $rows = [];
        $grandTotals = [];
        foreach ($channelKeys as $key) {
            $grandTotals[$key] = [
                'settlement' => 0,
                'users' => [],
            ];
        }

        foreach ($grouped as $dateKey => $perChannel) {
            $row = [
                'date' => Carbon::parse($dateKey)->translatedFormat('d F Y'),
            ];

            $totalSettlement = 0;
            $totalUsers = [];

            foreach ($channelKeys as $key) {
                $uniqueUsers = array_values(array_unique(array_filter($perChannel[$key]['users'])));
                $settlement = (float) $perChannel[$key]['settlement'];

                $row[$key . '_user'] = count($uniqueUsers);
                $row[$key . '_settle'] = number_format($settlement, 0, ',', '.');

                $totalSettlement += $settlement;
                $totalUsers = array_merge($totalUsers, $uniqueUsers);
                $grandTotals[$key]['settlement'] += $settlement;
                $grandTotals[$key]['users'] = array_merge($grandTotals[$key]['users'], $uniqueUsers);
            }

            $row['total_user'] = count(array_unique($totalUsers));
            $row['total'] = number_format($totalSettlement, 0, ',', '.');
            $rows[] = $row;
        }

        if (!empty($rows)) {
            $totalRow = [
                'date' => 'Total Keseluruhan',
            ];

            $allUsers = [];
            $allSettlement = 0;

            foreach ($channelKeys as $key) {
                $uniqueUsers = array_values(array_unique(array_filter($grandTotals[$key]['users'])));
                $settlement = (float) $grandTotals[$key]['settlement'];

                $totalRow[$key . '_user'] = count($uniqueUsers);
                $totalRow[$key . '_settle'] = number_format($settlement, 0, ',', '.');

                $allUsers = array_merge($allUsers, $uniqueUsers);
                $allSettlement += $settlement;
            }

            $totalRow['total_user'] = count(array_unique($allUsers));
            $totalRow['total'] = number_format($allSettlement, 0, ',', '.');
            $rows[] = $totalRow;
        }

        return $rows;
    }

    protected function getCdsiReferralTopupDetailRows(string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $startDate = $monthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00');
        $endDate = $monthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59');

        return $this->getCdsiPaymentTransactions($startDate, $endDate)
            ->map(function ($transaction) {
                return [
                    'transaction_id_raw' => $transaction->transaction_id,
                    'paid_date' => $transaction->payment_datetime
                        ? Carbon::parse($transaction->payment_datetime)->format('d-m-Y')
                        : '-',
                    'transaction_id' => $transaction->transaction_id ?: '-',
                    'customer_email' => $transaction->customer_email ?: '-',
                    'amount' => number_format((float) $transaction->transaction_amount, 0, ',', '.'),
                    'id_transaksi_myads' => $transaction->id_transaksi_myads,
                    'transfer_status' => ($transaction->is_received || !empty($transaction->id_transaksi_myads)) ? 'Receive' : 'Pending',
                ];
            })
            ->values()
            ->all();
    }

    protected function getCdsiPaymentTransactions(string $startDate, string $endDate)
    {
        return DB::table('payment_transactions_cdsi as pt')
            ->leftJoin('cdsi_referrals as cr', DB::raw('UPPER(TRIM(pt.referral_code))'), '=', DB::raw('UPPER(TRIM(cr.referral_code))'))
            ->select(
                'pt.transaction_id',
                'pt.customer_email',
                DB::raw('CAST(pt.transaction_amount AS DECIMAL(15,2)) as transaction_amount'),
                'pt.id_transaksi_myads',
                DB::raw('COALESCE(pt.payment_date, pt.transaction_date) as payment_datetime'),
                'cr.status as referral_status',
                DB::raw("EXISTS(
                    SELECT 1
                    FROM transaksi_balance_transfer as tbt
                    WHERE LOWER(tbt.status) = 'paid'
                      AND LOWER(TRIM(tbt.email_penerima)) = LOWER(TRIM(pt.customer_email))
                      AND CAST(tbt.jumlah AS DECIMAL(15,2)) = CAST(pt.transaction_amount AS DECIMAL(15,2))
                ) as is_received")
            )
            ->whereRaw('LOWER(pt.status) = ?', ['success'])
            ->whereBetween(DB::raw('COALESCE(pt.payment_date, pt.transaction_date)'), [$startDate, $endDate])
            ->where(function ($query) {
                $query->whereNull('cr.id')
                    ->orWhere('cr.status', 'active');
            })
            ->orderByDesc(DB::raw('COALESCE(pt.payment_date, pt.transaction_date)'))
            ->get();
    }

    protected function getMatchedCdsiTransactions(string $startDate, string $endDate)
    {
        return DB::table('payment_transactions_cdsi as pt')
            ->leftJoin('cdsi_referrals as cr', DB::raw('UPPER(TRIM(pt.referral_code))'), '=', DB::raw('UPPER(TRIM(cr.referral_code))'))
            ->select(
                DB::raw("DATE(COALESCE(pt.payment_date, pt.transaction_date)) as trx_date"),
                'pt.transaction_id',
                'pt.user_id',
                'pt.customer_name',
                'pt.customer_email',
                'pt.referral_code',
                'cr.id as referral_id',
                'cr.status as referral_status',
                DB::raw('CAST(pt.transaction_amount AS DECIMAL(15,2)) as transaction_amount'),
                DB::raw('COALESCE(pt.payment_date, pt.transaction_date) as payment_datetime'),
                DB::raw('(SELECT MIN(tbt.status) FROM transaksi_balance_transfer as tbt WHERE LOWER(tbt.status) = \'paid\' AND LOWER(TRIM(tbt.email_penerima)) = LOWER(TRIM(pt.customer_email)) AND CAST(tbt.jumlah AS DECIMAL(15,2)) = CAST(pt.transaction_amount AS DECIMAL(15,2))) as transfer_status'),
                'pt.id_transaksi_myads'
            )
            ->whereRaw('LOWER(pt.status) = ?', ['success'])
            ->whereBetween(DB::raw('COALESCE(pt.payment_date, pt.transaction_date)'), [$startDate, $endDate])
            ->where(function ($query) {
                $query->whereNull('cr.id')
                    ->orWhere('cr.status', 'active');
            })
            ->where(function ($query) {
                $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('transaksi_balance_transfer as tbt')
                        ->whereRaw('LOWER(tbt.status) = ?', ['paid'])
                        ->whereRaw('LOWER(TRIM(tbt.email_penerima)) = LOWER(TRIM(pt.customer_email))')
                        ->whereRaw('CAST(tbt.jumlah AS DECIMAL(15,2)) = CAST(pt.transaction_amount AS DECIMAL(15,2))');
                })->orWhereNotNull('pt.id_transaksi_myads');
            })
            ->orderByDesc(DB::raw('COALESCE(pt.payment_date, pt.transaction_date)'))
            ->get();
    }

    public function reportCdsi(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');

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

        $pageTitle = 'Report Campaign CDSI';

        return view('cdsi.report-cdsi', compact('months', 'month', 'selectedRemark', 'pageTitle'));
    }

    protected function cdsiUploadedReportBaseQuery(Carbon $startDate, Carbon $endDate)
    {
        if (!Schema::hasTable('cdsi_reports')) {
            return null;
        }

        return DB::table('cdsi_reports as cr')
            ->whereBetween('cr.tgl_tayang', [
                $startDate->copy()->format('Y-m-d'),
                $endDate->copy()->format('Y-m-d'),
            ]);
    }

    public function reportCdsiData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);
        $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

        $baseQuery = $this->cdsiUploadedReportBaseQuery($startDate, $endDate);

        if ($baseQuery === null) {
            return datatables()->of(collect([]))
                ->with('summary', [
                    'total_campaign' => 0,
                    'total_success' => 0,
                    'total_failed' => 0,
                    'total_harga' => 0,
                ])
                ->make(true);
        }

        $query = (clone $baseQuery)
            ->select(
                DB::raw('DATE(cr.tgl_tayang) as tanggal_iklan'),
                'cr.id_iklan',
                'cr.judul_pesan_iklan',
                'cr.operator_seluler',
                'cr.kategori_iklan',
                'cr.tipe_kanal',
                'cr.sukses as success',
                DB::raw('(COALESCE(cr.gagal, 0) + COALESCE(cr.refunded, 0)) as failed'),
                'cr.refunded',
                'cr.read',
                'cr.click',
                'cr.total_harga',
                'cr.detil_status'
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

    public function reportCdsiDormant(Request $request)
    {
        logUserLogin();

        $pageTitle = 'Data Dormant CDSI';

        return view('cdsi.report-cdsi-dormant', compact('pageTitle'));
    }

    protected function cdsiDormantBaseQuery()
    {
        $dormantQuery = DB::table('cdsi_data_dorman as cdd')
            ->selectRaw('cdd.email as email')
            ->selectRaw('cdd.nomor as nomor')
            ->selectRaw('cdd.nama_instansi as nama_instansi')
            ->selectRaw("CASE WHEN cdd.last_tgl_transaksi IS NULL OR DATE(cdd.last_tgl_transaksi) <= '0001-01-01' THEN NULL ELSE DATE(cdd.last_tgl_transaksi) END as last_tgl_transaksi")
            ->selectRaw('COALESCE(cdd.total_settlement, 0) as total_settlement');

        $nonTopupQuery = DB::table('cdsi_data_non_topup as cdnt')
            ->selectRaw('cdnt.email as email')
            ->selectRaw('cdnt.nomor as nomor')
            ->selectRaw('cdnt.nama_instansi as nama_instansi')
            ->selectRaw('NULL as last_tgl_transaksi')
            ->selectRaw('0 as total_settlement');

        return DB::query()->fromSub(
            $dormantQuery->unionAll($nonTopupQuery),
            'cd'
        );
    }

    public function reportCdsiDormantData(Request $request)
    {
        $baseQuery = $this->cdsiDormantBaseQuery();

        $query = (clone $baseQuery)
            ->select(
                'cd.email',
                'cd.nomor',
                'cd.nama_instansi',
                'cd.last_tgl_transaksi',
                DB::raw('COALESCE(cd.total_settlement, 0) as total_settlement')
            );

        $summaryRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_data')
            ->selectRaw('SUM(COALESCE(cd.total_settlement, 0)) as total_settlement')
            ->first();

        $summary = [
            'total_data' => (int) ($summaryRow->total_data ?? 0),
            'total_settlement' => (int) ($summaryRow->total_settlement ?? 0),
        ];

        return datatables()->of($query)
            ->with('summary', $summary)
            ->editColumn('last_tgl_transaksi', function ($row) {
                return !empty($row->last_tgl_transaksi)
                    ? Carbon::parse($row->last_tgl_transaksi)->format('d-m-Y')
                    : '-';
            })
            ->editColumn('total_settlement', function ($row) {
                return 'Rp ' . number_format((float) $row->total_settlement, 0, ',', '.');
            })
            ->orderColumn('email', 'cd.email $1')
            ->orderColumn('nomor', 'cd.nomor $1')
            ->orderColumn('nama_instansi', 'cd.nama_instansi $1')
            ->orderColumn('last_tgl_transaksi', 'cd.last_tgl_transaksi $1')
            ->orderColumn('total_settlement', 'cd.total_settlement $1')
            ->make(true);
    }

    public function exportCdsiDormant(Request $request)
    {
        try {
            $data = $this->cdsiDormantBaseQuery()
                ->select(
                    'cd.email',
                    'cd.nomor',
                    'cd.nama_instansi',
                    'cd.last_tgl_transaksi',
                    DB::raw('COALESCE(cd.total_settlement, 0) as total_settlement')
                )
                ->orderByDesc('cd.last_tgl_transaksi')
                ->orderBy('cd.email')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data dormant untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'DATA DORMANT CDSI');
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers = [
                'Email',
                'Nomor',
                'Nama Instansi',
                'Tanggal Terakhir Transaksi',
                'Total Settlement',
            ];

            $sheet->fromArray($headers, null, 'A3');
            $sheet->getStyle('A3:E3')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6F42C1'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            $rowNum = 4;
            foreach ($data as $row) {
                $sheet->fromArray([
                    $row->email,
                    $row->nomor,
                    $row->nama_instansi,
                    !empty($row->last_tgl_transaksi) ? Carbon::parse($row->last_tgl_transaksi)->format('d-m-Y') : '-',
                    (float) $row->total_settlement,
                ], null, 'A' . $rowNum);
                $rowNum++;
            }

            $sheet->getStyle('E4:E' . max($rowNum - 1, 4))
                ->getNumberFormat()
                ->setFormatCode('"Rp" #,##0');

            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'Data_Dormant_CDSI_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Export CDSI Dormant Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data dormant');
        }
    }

    public function exportCdsi(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            [$year, $monthNum] = explode('-', $month);
            $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
            $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

            $baseQuery = $this->cdsiUploadedReportBaseQuery($startDate, $endDate);

            if ($baseQuery === null) {
                return redirect()->back()->with('error', 'Tabel cdsi_reports belum tersedia. Jalankan migration atau upload report CDSI terlebih dahulu.');
            }

            $data = $baseQuery
                ->select(
                    DB::raw('DATE(cr.tgl_tayang) as tanggal_iklan'),
                    'cr.id_iklan',
                    'cr.judul_pesan_iklan',
                    'cr.operator_seluler',
                    'cr.kategori_iklan',
                    'cr.tipe_kanal',
                    'cr.sukses as success',
                    DB::raw('(COALESCE(cr.gagal, 0) + COALESCE(cr.refunded, 0)) as failed'),
                    'cr.refunded',
                    'cr.read',
                    'cr.click',
                    'cr.total_harga',
                    'cr.detil_status'
                )
                ->orderByDesc('cr.tgl_tayang')
                ->orderByDesc('cr.id_iklan')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'REPORT CDSI - ' . $month);
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
                    round((float) $row->percentage_read, 2) . '%',
                    round((float) $row->percentage_click, 2) . '%',
                    $row->total_harga,
                    $row->detil_status,
                ], null, 'A' . $rowNum);
                $rowNum++;
            }

            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'Report_CDSI_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Export CDSI Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data');
        }
    }

    public function reportCdsiProvince(Request $request)
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

        $pageTitle = 'Top Up Active';

        return view('cdsi.report-cdsi-province', compact('months', 'month', 'pageTitle'));
    }

    protected function cdsiProvinceTopupBaseQuery(string $month)
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $startDate = $monthDate->copy()->startOfMonth()->format('Y-m-d');
        $endDate = $monthDate->copy()->endOfMonth()->format('Y-m-d');

        return DB::table('report_balance_top_up as rp')
            ->select(
                'rp.data_province_name',
                'rp.user_id',
                'rp.email_client',
                DB::raw('SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))) as total_settlement_klien'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('MAX(rp.tgl_transaksi) as tgl_transaksi')
            )
            ->whereDate('rp.tgl_transaksi', '>=', $startDate)
            ->whereDate('rp.tgl_transaksi', '<=', $endDate)
            ->whereNotNull('rp.data_province_name')
            ->whereNotNull('rp.email_client')
            ->whereNotNull('rp.total_settlement_klien')
            ->where('rp.payment_method_name', '!=', 'Voucher Bonus')
            ->groupBy('rp.data_province_name', 'rp.user_id', 'rp.email_client');
    }

    public function reportCdsiProvinceData(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $search = $request->input('search.value');

            $baseQuery = $this->cdsiProvinceTopupBaseQuery($month)
                ->orderBy('total_settlement_klien', 'desc');

            if ($search) {
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('rp.data_province_name', 'like', "%$search%")
                        ->orWhere('rp.email_client', 'like', "%$search%")
                        ->orWhere('rp.user_id', 'like', "%$search%");
                });
            }

            $allData = (clone $baseQuery)->get();
            $totals = [
                'total_provinces' => $allData->unique('data_province_name')->count(),
                'total_user_ids' => $allData->unique('user_id')->count(),
                'total_emails' => $allData->unique('email_client')->count(),
                'total_settlement' => $allData->sum('total_settlement_klien'),
                'total_settlement_format' => 'Rp ' . number_format($allData->sum('total_settlement_klien'), 0, ',', '.'),
            ];

            return datatables()->of($baseQuery)
                ->addColumn('tanggal_format', function ($row) {
                    return Carbon::parse($row->tgl_transaksi)->translatedFormat('F Y');
                })
                ->addColumn('total_settlement_format', function ($row) {
                    return 'Rp ' . number_format($row->total_settlement_klien, 0, ',', '.');
                })
                ->with('totals', $totals)
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in reportCdsiProvinceData: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportCdsiProvince(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $monthDate = Carbon::createFromFormat('Y-m', $month);
            $displayMonth = $monthDate->translatedFormat('F Y');

            $data = $this->cdsiProvinceTopupBaseQuery($month)
                ->orderBy('rp.data_province_name', 'asc')
                ->orderBy('total_settlement_klien', 'desc')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk diekspor');
            }

            $exportData = [];
            foreach ($data as $row) {
                $exportData[] = [
                    'Provinsi' => $row->data_province_name,
                    'User ID' => $row->user_id,
                    'Email' => $row->email_client,
                    'Bulan' => Carbon::parse($row->tgl_transaksi)->translatedFormat('F Y'),
                    'Total Settlement' => ' ' . number_format((float) $row->total_settlement_klien, 0, ',', '.'),
                ];
            }

            $fileName = 'CDSI_Daily_TopUp_Per_Province_' . str_replace(' ', '_', $displayMonth) . '_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

            return response()->streamDownload(function () use ($exportData) {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                $headers = ['Provinsi', 'User ID', 'Email', 'Bulan', 'Total Settlement'];
                $sheet->fromArray($headers, null, 'A1');
                $sheet->getStyle('A1:E1')->applyFromArray([
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

                $rowNumber = 2;
                foreach ($exportData as $item) {
                    $sheet->fromArray(array_values($item), null, 'A' . $rowNumber);
                    $rowNumber++;
                }

                $sheet->getColumnDimension('A')->setWidth(25);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(35);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(20);

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Error in exportCdsiProvince: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }
}
