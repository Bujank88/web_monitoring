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
    private const MERCHANT_WHITELIST = ['CH778899'];
    private const MONITORING_EMAIL = 'arief_azhar@ptkam.co.id';
    private const REFERRAL_SENDER_ID = 'REG-DO-000000661407';
    private const RATE_CATEGORIES = ['sms' => 'SMS', 'waba' => 'WABA'];
    private const RATE_CHANNELS = ['lba' => 'LBA', 'broadcast' => 'Broadcast', 'targeted' => 'Targeted'];

    public function monitoringSaldo(Request $request)
    {
        abort_if(strcasecmp(trim((string) Auth::user()?->role), '1Synergy') === 0, 403);
        logUserLogin();
        $month = $request->get('month', now()->format('Y-m'));
        $history = $this->monitoringSaldoHistory($month);
        $canViewIncomingBalance = strcasecmp(trim((string) Auth::user()?->role), '1Synergy') !== 0;
        if (!$canViewIncomingBalance) {
            $history['total_in'] = null;
            $history['remaining_balance'] = null;
            $history['opening_balance'] = null;
            $history['ending_balance'] = null;
            $history['rows'] = array_values(array_filter(
                $history['rows'],
                fn ($row) => $row['transaction_type'] === 'Keluar'
            ));
            foreach ($history['rows'] as &$row) {
                $row['amount_in'] = null;
                $row['running_balance'] = null;
            }
            unset($row);
        }

        return view('one_synergy.monitoring_saldo', [
            'pageTitle' => 'Monitoring Saldo 1Synergy',
            'month' => $month,
            'months' => $this->monthOptions($month),
            'monitoringEmail' => $canViewIncomingBalance ? self::MONITORING_EMAIL : null,
            'senderId' => $canViewIncomingBalance ? self::REFERRAL_SENDER_ID : null,
            'canViewIncomingBalance' => $canViewIncomingBalance,
            'incomingBalanceNote' => 'Saldo masuk melalui transfer',
            'outgoingBalanceNote' => 'Balance terpakai dari Report 1Synergy',
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

    public function merchantSummary(Request $request)
    {
        logUserLogin();
        $month = $request->get('month', now()->format('Y-m'));

        return view('one_synergy.merchant_summary', [
            'months' => $this->monthOptions($month),
            'merchants' => $this->merchantOptions(true),
            'pageTitle' => 'Summary Merchant 1Synergy',
            'dataUrl' => route('one-synergy.merchant-summary.data'),
        ]);
    }

    public function merchantSummaryData(Request $request)
    {
        return datatables()
            ->of(collect($this->merchantSummaryRows($request->get('month', now()->format('Y-m')))))
            ->make(true);
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

        $selectedMerchant = (string) $request->get('merchant', '');

        return view('one_synergy.report-one-synergy', [
            'months' => $months,
            'month' => $month,
            'selectedRemark' => '',
            'pageTitle' => 'Report Campaign 1Synergy',
            'brandLabel' => '1Synergy',
            'dataUrl' => route('one-synergy.report.data'),
            'exportUrl' => route('one-synergy.report.export'),
            'showMerchantFilter' => true,
            'merchants' => $this->merchantOptions(false, false),
            'selectedMerchant' => $selectedMerchant,
        ]);
    }

    private function merchantOptions(bool $usersOnly = false, bool $restrictMerchants = true): array
    {
        try {
            return DB::connection('kam_myads')
                ->table('merchant_campaign_mappings as m')
                ->when($restrictMerchants, fn ($query) => $query->whereIn('m.merchant_id', self::MERCHANT_WHITELIST))
                ->leftJoin('users as u', 'u.merchant_id', '=', 'm.merchant_id')
                ->when($usersOnly, fn ($query) => $query->where('u.role', 'user'))
                ->select('m.merchant_id', DB::raw('MAX(u.name) as merchant_name'))
                ->groupBy('m.merchant_id')
                ->orderBy('merchant_name')
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->merchant_id,
                    'key' => 'merchant_' . md5($row->merchant_id),
                    'label' => $row->merchant_name
                        ? $row->merchant_name . ' (' . $row->merchant_id . ')'
                        : $row->merchant_id,
                ])
                ->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    private function campaignIdsForMerchant(string $merchantId, bool $restrictMerchants = true): array
    {
        if ($restrictMerchants && $merchantId !== '' && !in_array($merchantId, self::MERCHANT_WHITELIST, true)) {
            return [];
        }

        return DB::connection('kam_myads')
            ->table('merchant_campaign_mappings')
            ->when($restrictMerchants, fn ($query) => $query->whereIn('merchant_id', self::MERCHANT_WHITELIST))
            ->when($merchantId !== '', fn ($query) => $query->where('merchant_id', $merchantId))
            ->distinct()
            ->pluck('campaign_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function merchantSummaryRows(string $month): array
    {
        $date = Carbon::createFromFormat('!Y-m', $month);
        $merchants = collect($this->merchantOptions(true));

        if ($merchants->isEmpty()) {
            return [];
        }

        $mappings = DB::connection('kam_myads')
            ->table('merchant_campaign_mappings')
            ->whereIn('merchant_id', $merchants->pluck('id')->all())
            ->distinct()
            ->get(['merchant_id', 'campaign_id'])
            ->groupBy(fn ($row) => (string) $row->campaign_id);

        $reports = OneSynergyCampaignReport::query()
            ->whereBetween('tgl_tayang', [
                $date->copy()->startOfMonth()->format('Y-m-d'),
                $date->copy()->endOfMonth()->format('Y-m-d'),
            ])
            ->whereIn('id_iklan', $mappings->keys()->all())
            ->get(['id_iklan', 'tgl_tayang', 'sukses', 'kategori_iklan', 'tipe_kanal']);
        $rates = DB::table('one_synergy_monthly_multipliers')->where('month', $month)->first();

        $daily = [];
        $totals = [];
        foreach ($merchants as $merchant) {
            $totals[$merchant['id']] = ['campaign' => 0, 'balance' => 0];
        }

        foreach ($reports as $report) {
            $day = Carbon::parse($report->tgl_tayang)->format('Y-m-d');
            $balance = $this->reportBalance($report, $rates);
            foreach ($mappings->get((string) $report->id_iklan, collect()) as $mapping) {
                $merchantId = (string) $mapping->merchant_id;
                $daily[$day][$merchantId] ??= ['campaign' => 0, 'balance' => 0];
                $daily[$day][$merchantId]['campaign'] = ($daily[$day][$merchantId]['campaign'] ?? 0) + 1;
                $daily[$day][$merchantId]['balance'] = $balance === null || $daily[$day][$merchantId]['balance'] === null
                    ? null : $daily[$day][$merchantId]['balance'] + $balance;
                $totals[$merchantId]['campaign'] = ($totals[$merchantId]['campaign'] ?? 0) + 1;
                $totals[$merchantId]['balance'] = $balance === null || $totals[$merchantId]['balance'] === null
                    ? null : $totals[$merchantId]['balance'] + $balance;
            }
        }

        $rows = [];
        $firstDay = $date->copy()->startOfMonth();
        $lastDay = $date->isSameMonth(now())
            ? now()->startOfDay()
            : $date->copy()->endOfMonth()->startOfDay();

        for ($day = $firstDay; $day->lte($lastDay); $day->addDay()) {
            $merchantValues = $daily[$day->format('Y-m-d')] ?? [];
            $row = ['date' => $day->format('d-m-Y'), 'total_campaign' => 0, 'total_balance' => 0];
            foreach ($merchants as $merchant) {
                $values = $merchantValues[$merchant['id']] ?? ['campaign' => 0, 'balance' => 0];
                $row[$merchant['key'] . '_campaign'] = $values['campaign'];
                $row[$merchant['key'] . '_balance'] = $values['balance'] === null ? null : number_format($values['balance'], 0, ',', '.');
                $row['total_campaign'] += $values['campaign'];
                $row['total_balance'] = $row['total_balance'] === null || $values['balance'] === null
                    ? null : $row['total_balance'] + $values['balance'];
            }
            $row['total_balance'] = $row['total_balance'] === null ? null : number_format($row['total_balance'], 0, ',', '.');
            $rows[] = $row;
        }

        $totalRow = ['date' => 'Total Keseluruhan', 'total_campaign' => 0, 'total_balance' => 0];
        foreach ($merchants as $merchant) {
            $values = $totals[$merchant['id']] ?? ['campaign' => 0, 'balance' => 0];
            $totalRow[$merchant['key'] . '_campaign'] = $values['campaign'];
            $totalRow[$merchant['key'] . '_balance'] = $values['balance'] === null ? null : number_format($values['balance'], 0, ',', '.');
            $totalRow['total_campaign'] += $values['campaign'];
            $totalRow['total_balance'] = $totalRow['total_balance'] === null || $values['balance'] === null
                ? null : $totalRow['total_balance'] + $values['balance'];
        }
        $totalRow['total_balance'] = $totalRow['total_balance'] === null ? null : number_format($totalRow['total_balance'], 0, ',', '.');
        $rows[] = $totalRow;

        return $rows;
    }

    private function reportBalance(OneSynergyCampaignReport $report, ?object $rates): ?float
    {
        if ((int) $report->sukses === 0) {
            return 0;
        }
        $category = strtolower(trim((string) $report->kategori_iklan));
        $channel = strtolower(trim((string) $report->tipe_kanal));
        if (!isset(self::RATE_CATEGORIES[$category]) && isset(self::RATE_CATEGORIES[$channel])) {
            [$category, $channel] = [$channel, $category];
        }
        $field = $category . '_' . $channel . '_multiplier';
        if (!isset(self::RATE_CATEGORIES[$category], self::RATE_CHANNELS[$channel]) || !isset($rates->{$field})) {
            return null;
        }

        return (int) $report->sukses * (float) $rates->{$field};
    }

    private function baseQuery(Carbon $startDate, Carbon $endDate, string $merchantId = '')
    {
        if (!Schema::hasTable((new OneSynergyCampaignReport())->getTable())) {
            return null;
        }

        // Keep the `cr` alias used by the shared CDSI DataTable column config.
        $query = OneSynergyCampaignReport::query()->from((new OneSynergyCampaignReport())->getTable() . ' as cr')
            ->whereBetween('cr.tgl_tayang', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ]);

        if ($merchantId !== '') {
            $query->whereIn('cr.id_iklan', $this->campaignIdsForMerchant($merchantId, false));
        }

        return $query;
    }

    private function period(Request $request): array
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);
        $month = $request->get('month', now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $month);

        return [$month, $date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
    }

    public function data(Request $request)
    {
        [$month, $startDate, $endDate] = $this->period($request);
        $rates = DB::table('one_synergy_monthly_multipliers')->where('month', $month)->first();
        $baseQuery = $this->baseQuery($startDate, $endDate, (string) $request->get('merchant', ''));

        if ($baseQuery === null) {
            $summary = $this->emptySummary();
            $summary['total_harga'] = $rates === null ? null : 0;
            return datatables()->of(collect([]))->with('summary', $summary)->make(true);
        }

        $query = (clone $baseQuery)->select(
            DB::raw('DATE(cr.tgl_tayang) as tanggal_iklan'),
            'cr.id_iklan',
            'cr.judul_pesan_iklan',
            'cr.operator_seluler',
            'cr.kategori_iklan',
            'cr.tipe_kanal',
            'cr.sukses as success',
            DB::raw('COALESCE(cr.gagal, 0) as failed'),
            'cr.read',
            'cr.click'
        );

        $channel = "CASE WHEN UPPER(TRIM(cr.kategori_iklan)) IN ('SMS', 'WABA') THEN UPPER(TRIM(cr.kategori_iklan)) ELSE UPPER(TRIM(cr.tipe_kanal)) END";
        $summaryRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_campaign')
            ->selectRaw("SUM(CASE WHEN ($channel) = 'SMS' THEN COALESCE(cr.sukses, 0) ELSE 0 END) as total_success_sms")
            ->selectRaw("SUM(CASE WHEN ($channel) = 'WABA' THEN COALESCE(cr.sukses, 0) ELSE 0 END) as total_success_waba")
            ->selectRaw("SUM(CASE WHEN ($channel) = 'SMS' THEN COALESCE(cr.gagal, 0) ELSE 0 END) as total_failed_sms")
            ->selectRaw("SUM(CASE WHEN ($channel) = 'WABA' THEN COALESCE(cr.gagal, 0) ELSE 0 END) as total_failed_waba")
            ->first();

        $usage = (clone $baseQuery)
            ->selectRaw("($channel) as rate_category, UPPER(TRIM(cr.tipe_kanal)) as rate_channel")
            ->selectRaw('SUM(COALESCE(cr.sukses, 0)) as successes')
            ->groupByRaw("($channel), UPPER(TRIM(cr.tipe_kanal))")
            ->get();
        $totalBalance = $rates === null ? null : 0;
        foreach ($usage as $item) {
            if ((int) $item->successes === 0) {
                continue;
            }
            $categoryKey = strtolower((string) $item->rate_category);
            $channelKey = strtolower((string) $item->rate_channel);
            $field = $categoryKey . '_' . $channelKey . '_multiplier';
            if (!isset(self::RATE_CATEGORIES[$categoryKey], self::RATE_CHANNELS[$channelKey])
                || $rates === null || $rates->{$field} === null) {
                $totalBalance = null;
                break;
            }
            $totalBalance += (float) $item->successes * (float) $rates->{$field};
        }

        $summary = [
            'total_campaign' => (int) ($summaryRow->total_campaign ?? 0),
            'total_success_sms' => (int) ($summaryRow->total_success_sms ?? 0),
            'total_success_waba' => (int) ($summaryRow->total_success_waba ?? 0),
            'total_failed_sms' => (int) ($summaryRow->total_failed_sms ?? 0),
            'total_failed_waba' => (int) ($summaryRow->total_failed_waba ?? 0),
            'total_harga' => $totalBalance === null ? null : round($totalBalance, 2),
        ];

        return datatables()->of($query)
            ->with('summary', $summary)
            ->make(true);
    }

    public function monthlyMultipliers(Request $request)
    {
        abort_unless(Auth::user()?->role === 'Admin', 403);
        $request->validate(['month' => 'nullable|date_format:Y-m']);
        $month = $request->input('month', now()->format('Y-m'));

        return view('one_synergy.monthly-multipliers', [
            'month' => $month,
            'rates' => DB::table('one_synergy_monthly_multipliers')->where('month', $month)->first(),
            'settings' => DB::table('one_synergy_monthly_multipliers')->orderByDesc('month')->get(),
            'rateCategories' => self::RATE_CATEGORIES,
            'rateChannels' => self::RATE_CHANNELS,
        ]);
    }

    public function saveMonthlyMultiplier(Request $request)
    {
        abort_unless(Auth::user()?->role === 'Admin', 403);
        $rules = ['month' => 'required|date_format:Y-m'];
        foreach (self::RATE_CATEGORIES as $category => $label) {
            foreach (self::RATE_CHANNELS as $channel => $channelLabel) {
                $rules[$category . '_' . $channel . '_multiplier'] = 'required|numeric|min:0|max:9999999999999.99|decimal:0,2';
            }
        }
        $validated = $request->validate($rules);
        DB::table('one_synergy_monthly_multipliers')->upsert([
            ...$validated,
            'updated_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ], ['month'], [...array_diff(array_keys($rules), ['month']), 'updated_by', 'updated_at']);

        return redirect()->route('one-synergy.monthly-multipliers', ['month' => $validated['month']])
            ->with('success', 'Pengali bulanan berhasil disimpan.');
    }

    private function emptySummary(): array
    {
        return [
            'total_campaign' => 0,
            'total_success_sms' => 0,
            'total_success_waba' => 0,
            'total_failed_sms' => 0,
            'total_failed_waba' => 0,
            'total_harga' => 0,
        ];
    }

    public function export(Request $request)
    {
        [$month, $startDate, $endDate] = $this->period($request);
        $baseQuery = $this->baseQuery($startDate, $endDate, (string) $request->get('merchant', ''));

        if ($baseQuery === null) {
            return redirect()->back()->with('error', 'Tabel 1Synergy belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $baseQuery->select(
            DB::raw('DATE(cr.tgl_tayang) as tanggal_iklan'),
            'cr.id_iklan', 'cr.judul_pesan_iklan', 'cr.operator_seluler',
            'cr.kategori_iklan', 'cr.tipe_kanal', 'cr.sukses as success',
            DB::raw('COALESCE(cr.gagal, 0) as failed'),
            'cr.read', 'cr.click', 'cr.detil_status',
            DB::raw('CASE WHEN COALESCE(cr.sukses, 0) > 0 THEN (COALESCE(cr.read, 0) / cr.sukses) * 100 ELSE 0 END as percentage_read'),
            DB::raw('CASE WHEN COALESCE(cr.read, 0) > 0 THEN (COALESCE(cr.click, 0) / cr.read) * 100 ELSE 0 END as percentage_click')
        )->orderByDesc('cr.tgl_tayang')->orderByDesc('cr.id_iklan')->get();

        if ($data->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data 1Synergy untuk di-export.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'REPORT 1SYNERGY - ' . $month)->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['Tanggal Tayang', 'ID Iklan', 'Judul Pesan Iklan', 'Operator Seluler', 'Kategori Iklan', 'Tipe Kanal', 'Success', 'Failed', 'Read', 'Click', 'Percentage Read', 'Percentage Click', 'Detil Status'];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:M3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rowNum = 4;
        foreach ($data as $row) {
            $sheet->fromArray([
                $row->tanggal_iklan, $row->id_iklan, $row->judul_pesan_iklan,
                $row->operator_seluler, $row->kategori_iklan, $row->tipe_kanal,
                $row->success, $row->failed, $row->read, $row->click,
                round((float) $row->percentage_read, 2) . '%',
                round((float) $row->percentage_click, 2) . '%',
                $row->detil_status,
            ], null, 'A' . $rowNum++);
        }

        foreach (range('A', 'M') as $column) {
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
            'acceptCsv' => true,
            'csvExample' => asset('examples/report-1synergy.csv'),
        ]);
    }

    public function template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['ID IKLAN', 'TGL TAYANG', 'JUDUL PESAN IKLAN', 'OPERATOR SELULER', 'KATEGORI IKLAN', 'TIPE KANAL', 'DETIL STATUS', 'READ', 'CLICK'],
            ['1849001', '15 Sep 2026', 'Contoh WABA 1Synergy', 'TELKOMSEL', 'WABA', 'BROADCAST', 'Sukses: 5.304 Gagal: 1.131', '2929', '0'],
        ], null, 'A1');
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'Template Laporan 1Synergy.xlsx');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'extensions:xlsx,xls,csv', 'max:10240'],
        ]);

        if (!Schema::hasTable((new OneSynergyCampaignReport())->getTable())) {
            return redirect()->route('one-synergy.upload')->with('error', 'Tabel 1Synergy belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $file = $validated['report_file'];
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'report-1synergy';
        $fileName = $safeName . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs('one-synergy-report-uploads', $fileName);
        $rows = IOFactory::load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, true);
        $headers = [];
        foreach (array_shift($rows) ?? [] as $column => $header) {
            $key = strtoupper(trim(str_replace("\xEF\xBB\xBF", '', (string) $header)));
            $headers[$key] = $column;
        }
        $required = ['ID IKLAN', 'TGL TAYANG', 'JUDUL PESAN IKLAN', 'OPERATOR SELULER', 'KATEGORI IKLAN', 'TIPE KANAL', 'DETIL STATUS', 'READ', 'CLICK'];
        if ($missing = array_diff($required, array_keys($headers))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'report_file' => 'Kolom wajib tidak ditemukan: ' . implode(', ', $missing),
            ]);
        }
        $payload = [];

        foreach ($rows as $row) {
            $cell = fn (string $name) => isset($headers[$name]) ? ($row[$headers[$name]] ?? null) : null;
            $idIklan = trim((string) $cell('ID IKLAN'));
            $judul = trim((string) $cell('JUDUL PESAN IKLAN'));
            $status = trim((string) $cell('DETIL STATUS'));
            if ($idIklan === '') {
                continue;
            }
            [$sukses, $gagal] = $this->parseStatus($status);
            $payload[] = [
                'id_iklan' => $idIklan,
                'tgl_tayang' => $this->parseDate($cell('TGL TAYANG')),
                'judul_pesan_iklan' => $judul ?: null,
                'operator_seluler' => trim((string) $cell('OPERATOR SELULER')) ?: null,
                'kategori_iklan' => trim((string) $cell('KATEGORI IKLAN')) ?: null,
                'tipe_kanal' => trim((string) $cell('TIPE KANAL')) ?: null,
                'detil_status' => $status ?: null,
                'sukses' => $sukses,
                'gagal' => $gagal,
                'refunded' => 0,
                'read' => $this->parseInteger($cell('READ')),
                'click' => $this->parseInteger($cell('CLICK')),
                'total_harga' => $this->parseInteger($cell('TOTAL HARGA')),
                'source_file_name' => $file->getClientOriginalName(),
                'upload_batch' => now()->format('YmdHis'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($payload === []) {
            return redirect()->route('one-synergy.upload')->with('error', 'Tidak ada baris valid dalam file report.');
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
        $monthDate = Carbon::createFromFormat('!Y-m', $month);
        $incoming = DB::table('transaksi_balance_transfer')->select([
            DB::raw("'Masuk' as transaction_type"),
            DB::raw("'Balance Transfer' as source"),
            'tanggal as transaction_datetime',
            'email_penerima as reference_email',
            DB::raw('CAST(0 AS DECIMAL(15,2)) as amount_out'),
            DB::raw('CAST(COALESCE(jumlah, 0) AS DECIMAL(15,2)) as amount_in'),
        ])->whereRaw('LOWER(TRIM(email_penerima)) = ?', [strtolower(self::MONITORING_EMAIL)]);

        // 1Synergy receives transfers and spends balance on reported campaigns.
        $reports = OneSynergyCampaignReport::query()
            ->whereIn('id_iklan', $this->campaignIdsForMerchant(''))
            ->whereNotNull('tgl_tayang')
            ->get(['tgl_tayang', 'sukses', 'kategori_iklan', 'tipe_kanal']);
        $ratesByMonth = DB::table('one_synergy_monthly_multipliers')
            ->whereIn('month', $reports->map(fn ($report) => $report->tgl_tayang->format('Y-m'))->unique()->all())
            ->get()->keyBy('month');
        $outgoingHistory = $reports->map(fn ($report) => (object) [
            'transaction_type' => 'Keluar',
            'source' => 'Balance Terpakai Report 1Synergy',
            'transaction_datetime' => $report->tgl_tayang->format('Y-m-d'),
            'reference_email' => '-',
            'amount_in' => 0,
            'amount_out' => $this->reportBalance($report, $ratesByMonth->get($report->tgl_tayang->format('Y-m'))),
        ]);
        $sumOutgoing = fn ($rows) => $rows->contains(fn ($row) => $row->amount_out === null)
            ? null : (float) $rows->sum('amount_out');

        $incomingHistory = DB::query()->fromSub($incoming, 'incoming_history');
        $periodStart = $monthDate->format('Y-m-d');
        $periodEnd = $monthDate->copy()->addMonth()->format('Y-m-d');
        $openingIn = (float) (clone $incomingHistory)->where('transaction_datetime', '<', $periodStart)->sum('amount_in');
        $openingOut = $sumOutgoing($outgoingHistory->where('transaction_datetime', '<', $periodStart));
        $openingBalance = $openingOut === null ? null : $openingIn - $openingOut;
        $remainingIn = (float) (clone $incomingHistory)->sum('amount_in');
        $remainingOut = $sumOutgoing($outgoingHistory);
        $incomingRows = $incomingHistory->where('transaction_datetime', '>=', $periodStart)
            ->where('transaction_datetime', '<', $periodEnd)->get();
        $outgoingRows = $outgoingHistory->where('transaction_datetime', '>=', $periodStart)
            ->where('transaction_datetime', '<', $periodEnd);

        $runningBalance = $openingBalance;
        $totalIn = 0;
        $totalOut = 0;
        $rows = $incomingRows->concat($outgoingRows)
            ->sortBy(fn ($row) => Carbon::parse($row->transaction_datetime)->timestamp)
            ->values()
            ->map(function ($row) use (&$runningBalance, &$totalIn, &$totalOut) {
                $amountIn = (float) $row->amount_in;
                $amountOut = $row->amount_out === null ? null : (float) $row->amount_out;
                $totalIn += $amountIn;
                $totalOut = $totalOut === null || $amountOut === null ? null : $totalOut + $amountOut;
                $runningBalance = $runningBalance === null || $amountOut === null ? null : $runningBalance + $amountIn - $amountOut;

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
            'remaining_balance' => $remainingOut === null ? null : $remainingIn - $remainingOut,
            'opening_balance' => $openingBalance,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'ending_balance' => $openingBalance === null || $totalOut === null ? null : $openingBalance + $totalIn - $totalOut,
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
