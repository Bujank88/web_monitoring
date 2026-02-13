<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Topup;
use App\Models\LeadsMaster;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportController extends Controller
{
     /* ================= PAGE LOAD ================= */
    public function topupCanvasser(Request $request)
    {
        logUserLogin();
        $month = $request->get('month', now()->format('Y-m'));

        $canvassers = DB::connection('mysql')
            ->table('users')
            ->where('role', 'cvsr')
            ->pluck('name');

        return view('report.topup-client-canvasser', [
            'month'      => $month,
            'canvassers' => $canvassers
        ]);
    }

    
public function topupCanvasserData(Request $request)
{
    /* ================= DATE RANGE ================= */
    if ($request->month) {
        [$year, $month] = explode('-', $request->month);
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = Carbon::create($year, $month, 1)->endOfMonth();
    } else {
        $start = $request->start
            ? Carbon::parse($request->start)->startOfDay()
            : now()->startOfMonth();

        $end = $request->end
            ? Carbon::parse($request->end)->endOfDay()
            : now()->endOfMonth();
    }

    /* ================= TOPUP ================= */
    $topups = DB::connection('pgsql')
        ->table('em_myads_topup')
        ->whereBetween('tgl_transaksi', [$start, $end])
        ->select('tgl_transaksi', 'email_client', 'total_settlement_klien')
        ->get()
        ->map(fn($t) => [
            'date'   => Carbon::parse($t->tgl_transaksi)->format('Y-m-d'),
            'email'  => strtolower(trim($t->email_client)),
            'amount' => (float) $t->total_settlement_klien,
        ]);

    if ($topups->isEmpty()) {
        return response()->json(['canvassers'=>[], 'rows'=>[]]);
    }

    $emails = $topups->pluck('email')->unique()->values();

    /* ================= FIX DUPLICATE EMAIL ================= */
    $sub = DB::connection('mysql')
        ->table('leads_master')
        ->selectRaw('LOWER(TRIM(email)) as email, MAX(id) as last_id')
        ->whereIn(DB::raw('LOWER(TRIM(email))'), $emails)
        ->groupBy(DB::raw('LOWER(TRIM(email))'));

    $master = DB::connection('mysql')
        ->table('leads_master as lm')
        ->joinSub($sub, 'x', function ($j) {
            $j->on('lm.id', '=', 'x.last_id');
        })
        ->join('users', 'users.id', '=', 'lm.user_id')
        ->where('users.role', 'cvsr');

    if ($request->canvassers) {
        $master->whereIn('users.name', $request->canvassers);
    }

    $master = $master
        ->selectRaw('LOWER(TRIM(lm.email)) as email, users.name as canvasser')
        ->get();

    if ($master->isEmpty()) {
        return response()->json(['canvassers'=>[], 'rows'=>[]]);
    }

    /* ================= MAP (EMAIL → 1 CANVASSER) ================= */
    $map = $master->pluck('canvasser', 'email');

    $canvassers = $map->values()->unique()->sort()->values();

    /* ================= BUILD PIVOT ================= */
    $rows = [];

    foreach ($topups as $t) {
        if (!isset($map[$t['email']])) continue;

        $c = $map[$t['email']];

        $rows[$t['date']][$c]['amount'] =
            ($rows[$t['date']][$c]['amount'] ?? 0) + $t['amount'];

        $rows[$t['date']][$c]['emails'][] = $t['email'];
    }

    return response()->json([
        'canvassers' => $canvassers,
        'rows' => $rows
    ]);
}

    public function getMoMTopup(Request $request)
    {
        // Ambil bulan sekarang dan bulan lalu (full bulan)
        $now = Carbon::now();

        // Periode bulan ini: mulai tgl 1 sampai akhir bulan sekarang
        $start1 = $now->copy()->startOfMonth()->startOfDay();
        $end1   = $now->copy()->endOfMonth()->endOfDay();

        // Periode bulan lalu: mulai tgl 1 sampai akhir bulan lalu
        $start2 = $now->copy()->subMonth()->startOfMonth()->startOfDay();
        $end2   = $now->copy()->subMonth()->endOfMonth()->endOfDay();

        // Query topup bulan ini
        $topupThisMonth = DB::connection('pgsql')->table('em_myads_topup')
            ->whereBetween('tgl_transaksi', [$start1, $end1])
            ->select('email_client', DB::raw('SUM(total_settlement_klien) as total'))
            ->groupBy('email_client')
            ->get()
            ->mapWithKeys(fn($row) => [strtolower(trim($row->email_client)) => (float) $row->total]);

        // Query topup bulan lalu
        $topupLastMonth = DB::connection('pgsql')->table('em_myads_topup')
            ->whereBetween('tgl_transaksi', [$start2, $end2])
            ->select('email_client', DB::raw('SUM(total_settlement_klien) as total'))
            ->groupBy('email_client')
            ->get()
            ->mapWithKeys(fn($row) => [strtolower(trim($row->email_client)) => (float) $row->total]);

        // Ambil mapping email -> canvasser dari database MySQL
        $emails = $topupThisMonth->keys()->merge($topupLastMonth->keys())->unique();

        $master = DB::connection('mysql')->table('leads_master')
            ->join('users', 'users.id', '=', 'leads_master.user_id')
            ->where('users.role', 'cvsr')
            ->whereIn(DB::raw('LOWER(TRIM(leads_master.email))'), $emails)
            ->selectRaw('LOWER(TRIM(leads_master.email)) as email, users.name as canvasser')
            ->get();

        $map = $master->pluck('canvasser', 'email');

        // Daftar canvasser unik
        $canvassers = $map->unique()->sort()->values();

        // Hitung total per canvasser
        $totalsThisMonth = [];
        $totalsLastMonth = [];

        foreach ($canvassers as $c) {
            $totalsThisMonth[$c] = 0;
            $totalsLastMonth[$c] = 0;
        }

        foreach ($topupThisMonth as $email => $total) {
            $c = $map[$email] ?? null;
            if ($c) $totalsThisMonth[$c] += $total;
        }

        foreach ($topupLastMonth as $email => $total) {
            $c = $map[$email] ?? null;
            if ($c) $totalsLastMonth[$c] += $total;
        }

        // Hitung selisih (bulan ini - bulan lalu)
        $selisih = [];
        foreach ($canvassers as $c) {
            $selisih[$c] = $totalsThisMonth[$c] - $totalsLastMonth[$c];
        }

        // Format response
        $response = [
            'canvassers' => $canvassers,
            'rows' => [
                'Total ' . $start2->format('M Y') => $totalsLastMonth,
                'Total ' . $start1->format('M Y') => $totalsThisMonth,
                'Selisih ' . $start1->format('M Y') . ' - ' . $start2->format('M Y') => $selisih,
            ]
        ];

        return response()->json($response);
    }

    /* ================= EXPORT EXCEL ================= */
    public function exportTopupCanvasserExcel(Request $request)
    {
        return Excel::download(
            new \App\Exports\TopupCanvasserExport($request),
            'topup-canvasser.xlsx'
        );
    }

    /* ================= EXPORT PDF ================= */
    public function exportTopupCanvasserPdf(Request $request)
    {
        $data = $this->topupCanvasserData($request)->getData(true);

        $pdf = Pdf::loadView('report.pdf.topup-canvasser', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('topup-canvasser.pdf');
    }

    public function reportRegionTargetVsTopup(Request $request)
    {
        logUserLogin();
        /* ================= FILTER BULAN ================= */
        $month = $request->get('month', now()->format('Y-m'));

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        /* ================= TOPUP PER REGION ================= */
        $topupPerRegion = DB::connection('mysql')
            ->table('report_balance_top_up as emt')
            ->selectRaw("
                CASE
                    WHEN data_province_name IN ('Sumatera Selatan','Jambi','Bengkulu','Lampung','Bangka Belitung', 'Kepulauan Bangka Belitung') THEN 'SUMBAGSEL'
                    WHEN data_province_name IN ('Sumatera Barat','Riau','Kepulauan Riau') THEN 'SUMBAGTENG'
                    WHEN data_province_name IN ('Sumatera Utara','Aceh') THEN 'SUMBAGUT'
                    WHEN data_province_name IN ('DKI Jakarta','Banten') THEN 'JABODETABEK'
                    WHEN data_province_name = 'Jawa Barat' THEN 'JABAR'
                    WHEN data_province_name IN ('Jawa Tengah','Yogyakarta', 'DI Yogyakarta') THEN 'JATENG DIY'
                    WHEN data_province_name = 'Jawa Timur' THEN 'JATIM'
                    WHEN data_province_name IN ('Bali','NTB','NTT') THEN 'BALI NUSRA'
                    WHEN data_province_name IN ('Kalimantan Tengah','Kalimantan Barat','Kalimantan Utara','Kalimantan Timur','Kalimantan Selatan') THEN 'KALIMANTAN'
                    WHEN data_province_name IN ('Sulawesi Utara','Sulawesi Tengah','Gorontalo','Sulawesi Tenggara','Sulawesi Selatan','Maluku Utara') THEN 'SULAWESI'
                    WHEN data_province_name IN ('Maluku','Papua Barat','Papua') THEN 'PAPUA MALUKU'
                    ELSE 'UNKNOWN'
                END AS region,
                SUM(emt.total_settlement_klien) AS topup
            ")
            ->whereBetween('emt.tgl_transaksi', [$start, $end])
            ->whereNotNull('emt.tgl_transaksi')
            // ->where('emt.payment_history_status', 'PAID')
            ->groupBy('region')
            ->get()
            ->mapWithKeys(fn ($item) => [strtoupper($item->region) => $item]);

        /* ================= TARGET ================= */
        $targets = DB::table('region_target')
            ->where('data_type', 'PowerHouse')
            ->whereMonth('date', $start->month)
            ->whereYear('date', $start->year)
            ->get()
            ->mapWithKeys(fn ($item) => [strtoupper($item->region_name) => $item]);

        /* ================= LAST UPDATE GLOBAL ================= */
        $lastUpdate = DB::connection('mysql')
            ->table('report_balance_top_up')
            ->whereBetween('tgl_transaksi', [$start, $end])
            ->whereNotNull('tgl_transaksi')
            ->where('payment_history_status', 'PAID')
            ->max('tgl_transaksi');

        /* ================= MERGE DATA ================= */
        $data = [];

        $regions = $targets->keys()
            ->merge($topupPerRegion->keys())
            ->unique();
            // ->filter(fn ($r) => $r !== 'UNKNOWN');

        // Hitung sisa hari di bulan berjalan
            $today = Carbon::now();
            $todayDate = $today->format('Y-m-d'); // Tanggal hari ini untuk filter transaksi
            $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endOfMonth = Carbon::now()->endOfMonth(); // Untuk hitung sisa hari kerja
        // Daftar tanggal merah Indonesia 2026 (bisa disesuaikan atau query dari database)
        $holidays = [
            '2026-01-01', // Tahun Baru
            '2026-02-17', // Isra Miraj (estimasi)
            '2026-03-22', // Nyepi
            '2026-03-23', // Idul Fitri (estimasi)
            '2026-03-24', // Idul Fitri (estimasi)
            '2026-04-10', // Wafat Yesus Kristus
            '2026-05-01', // Hari Buruh
            '2026-05-02', // Cuti Bersama (estimasi)
            '2026-05-21', // Kenaikan Yesus Kristus
            '2026-05-30', // Idul Adha (estimasi)
            '2026-06-01', // Hari Pancasila
            '2026-06-20', // Tahun Baru Islam (estimasi)
            '2026-08-17', // Hari Kemerdekaan
            '2026-08-29', // Maulid Nabi (estimasi)
            '2026-12-25', // Hari Natal
        ];

        // Hitung hanya hari kerja (Senin-Jumat) yang tersisa, exclude weekend dan tanggal merah
        $remainingWorkingDays = 0;
        $currentDate = $today->copy();
        
        while ($currentDate->lte($endOfMonth)) {
            // Cek apakah hari ini adalah weekday (Senin-Jumat)
            $isWeekday = $currentDate->isWeekday(); // true jika Senin-Jumat
            
            // Cek apakah bukan tanggal merah
            $isNotHoliday = !in_array($currentDate->format('Y-m-d'), $holidays);
            
            // Jika weekday dan bukan tanggal merah, hitung sebagai hari kerja
            if ($isWeekday && $isNotHoliday) {
                $remainingWorkingDays++;
            }
            
            $currentDate->addDay();
        }
        
        foreach ($regions as $region) {
            $targetRow = $targets[$region] ?? null;
            $topupRow  = $topupPerRegion[$region] ?? null;

            $target = $targetRow ? (float) $targetRow->target_amount : 0;
            $pic    = $targetRow ? ($targetRow->pic ?? '-') : '-';
            $topup  = (float) ($topupRow->topup ?? 0);

            $percentage = $target > 0
                ? round(($topup / $target) * 100, 2)
                : 0;

            $gap = $topup - $target;
                
            $gapDaily = $remainingWorkingDays > 0 ? $gap / $remainingWorkingDays : 0;
            $gapDaily *= -1;

            if (strtoupper($region) === 'UNKNOWN') {
                $gap = 0;
                $gapDaily = 0;
            }

            $data[] = [
                'region'     => $region,
                'pic'        => $pic,
                'target'     => $target,
                'topup'      => $topup,
                'gap'        => $gap,
                'gap_daily'  => $gapDaily,
                'percentage' => $percentage,
            ];
        }

        return view('report.region-target-topup', [
            'data'       => collect($data)->values(),
            'lastUpdate' => $lastUpdate,
            'month'      => $month
        ]);
    }



    public function reportMitraSBP(Request $request)
    {
        logUserLogin();
        
        /* ================= FILTER BULAN ================= */
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);
        $monthStart = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $monthEnd = Carbon::create($year, $monthNum, 1)->endOfMonth();
        
        /* ================= GENERATE MONTHS DROPDOWN ================= */
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $value = $date->format('Y-m');
            $label = $date->translatedFormat('F Y');
            $months[] = ['value' => $value, 'label' => $label, 'selected' => $value === $month];
        }
        
        /* ================= GET CVSR EMAILS TO EXCLUDE ================= */
        // Get all CVSR emails to exclude from Mitra SBP, Agency, Internal counts
        // REASON: CVSR takes PRIORITY over remark classification (align with Daily TopUp logic)
        $cvsrEmails = DB::table('leads_master as lm')
            ->join('users as u', 'u.id', '=', 'lm.user_id')
            ->where('u.role', 'cvsr')
            ->where('u.name', '!=', 'self service')
            ->select(DB::raw('LOWER(TRIM(lm.email)) as email'))
            ->get()
            ->pluck('email')
            ->toArray();

        $data_mitra_sbp = DB::table('mitra_sbp as ms')
            ->leftJoin('region_target as rt', function ($join) use ($monthStart) {
                $join->on('rt.region_name', '=', 'ms.regional')
                    ->where('rt.data_type', 'Mitra SBP')
                    ->whereDate('rt.date', $monthStart);
            })
            ->leftJoin('report_balance_top_up as rbt', function ($join) use ($monthStart, $monthEnd)  {
                $join->on('rbt.email_client', '=', 'ms.email_myads')
                        ->whereBetween('rbt.tgl_transaksi', [$monthStart, $monthEnd]);
            })
            ->select(
                'ms.area',
                'ms.regional as region_name',
                DB::raw('COALESCE(rt.target_amount, 0) as target_amount'),
                DB::raw('COALESCE(SUM(rbt.total_settlement_klien), 0) as mitra_sbp')
            )
            ->where('ms.remark', 'Mitra SBP')
            ->groupBy(
                'ms.area',
                'ms.regional',
                'rt.target_amount'
            )
            ->havingRaw('(ms.regional != "" OR COALESCE(rt.target_amount,0) > 0 OR COALESCE(SUM(rbt.total_settlement_klien),0) > 0)')
            ->orderBy('ms.area')
            ->orderBy('ms.regional')
            ->get();

        $grouped_mitra_sbp = $data_mitra_sbp->groupBy('area');

        $data_agency = DB::table('mitra_sbp as ms')
            ->leftJoin('region_target as rt', function ($join) use ($monthStart) {
                $join->on('rt.region_name', '=', 'ms.regional')
                    ->where('rt.data_type', 'Agency')
                    ->whereDate('rt.date', $monthStart);
            })
            ->leftJoin('report_balance_top_up as rbt', function ($join) use ($monthStart, $monthEnd)  {
                $join->on('rbt.email_client', '=', 'ms.email_myads')
                        ->whereBetween('rbt.tgl_transaksi', [$monthStart, $monthEnd]);
            })
            ->select(
                'ms.area',
                'ms.regional as region_name',
                DB::raw('COALESCE(rt.target_amount, 0) as target_amount'),
                DB::raw('COALESCE(SUM(rbt.total_settlement_klien), 0) as agency')
            )
            ->where('ms.remark', 'Agency')
            ->groupBy(
                'ms.area',
                'ms.regional',
                'rt.target_amount'
            )
            ->havingRaw('(ms.regional != "" OR COALESCE(rt.target_amount,0) > 0 OR COALESCE(SUM(rbt.total_settlement_klien),0) > 0)')
            ->orderBy('ms.area')
            ->orderBy('ms.regional')
            ->get();

        $grouped_agency = $data_agency->groupBy('area');


        $data_internal = DB::table('mitra_sbp as ms')
            ->leftJoin('region_target as rt', function ($join) use ($monthStart) {
                $join->on('rt.region_name', '=', 'ms.regional')
                    ->where('rt.data_type', 'Internal')
                    ->whereDate('rt.date', $monthStart);
            })
            ->leftJoin('report_balance_top_up as rbt', function ($join) use ($monthStart, $monthEnd)  {
                $join->on('rbt.email_client', '=', 'ms.email_myads')
                        ->whereBetween('rbt.tgl_transaksi', [$monthStart, $monthEnd]);
            })
            ->select(
                'ms.area',
                'ms.regional as region_name',
                DB::raw('COALESCE(rt.target_amount, 0) as target_amount'),
                DB::raw('COALESCE(SUM(rbt.total_settlement_klien), 0) as internal')
            )
            ->where('ms.remark', 'Internal')
            ->groupBy(
                'ms.area',
                'ms.regional',
                'rt.target_amount'
            )
            ->havingRaw('(ms.regional != "" OR COALESCE(rt.target_amount,0) > 0 OR COALESCE(SUM(rbt.total_settlement_klien),0) > 0)')
            ->orderBy('ms.area')
            ->orderBy('ms.regional')
            ->get();

        $grouped_internal = $data_internal->groupBy('area');
        
        return view('mitra-sbp.report-performance', compact('grouped_mitra_sbp', 'data_mitra_sbp', 'grouped_agency', 'data_agency', 'grouped_internal', 'data_internal', 'months', 'month'));
    }

    public function reportCampaignSbp(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $value = $date->format('Y-m');
            $label = $date->translatedFormat('F Y');
            $months[] = ['value' => $value, 'label' => $label, 'selected' => $value === $month];
        }

        return view('mitra-sbp.report-campaign-sbp', compact('months', 'month'));
    }

    private function campaignSbpEmailSubquery($year, $monthNum, $remark = null)
    {
        $emails = DB::table('mitra_sbp as ms')
            ->join('data_campaign_seasonal as dc', 'ms.email_myads', '=', 'dc.email')
            ->whereYear('dc.tanggal_iklan', $year)
            ->whereMonth('dc.tanggal_iklan', $monthNum);

        if (!empty($remark)) {
            $emails->where('ms.remark', $remark);
        }

        return $emails->selectRaw('DISTINCT LOWER(TRIM(dc.email)) as email_key');
    }

    private function campaignSbpSaldoUsersSubquery($year, $monthNum, $remark = null)
    {
        $campaignEmails = $this->campaignSbpEmailSubquery($year, $monthNum, $remark);

        return DB::table('saldo_users as su')
            ->joinSub($campaignEmails, 'ce', function ($join) {
                $join->on(DB::raw('LOWER(TRIM(su.email))'), '=', 'ce.email_key');
            })
            ->selectRaw('LOWER(TRIM(su.email)) as email_key')
            ->selectRaw('MAX(COALESCE(su.saldo_utama, 0)) as saldo_utama')
            ->selectRaw('MAX(COALESCE(su.saldo_monet, 0)) as saldo_monet')
            ->groupBy(DB::raw('LOWER(TRIM(su.email))'));
    }

    public function reportCampaignSbpData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        $saldoUsersSub = $this->campaignSbpSaldoUsersSubquery($year, $monthNum, $request->get('remark'));

        $baseQuery = DB::table('mitra_sbp as a')
            ->join('data_campaign_seasonal as b', 'a.email_myads', '=', 'b.email')
            ->leftJoinSub($saldoUsersSub, 'su', function ($join) {
                $join->on(DB::raw('LOWER(TRIM(b.email))'), '=', 'su.email_key');
            })
            ->whereYear('b.tanggal_iklan', $year)
            ->whereMonth('b.tanggal_iklan', $monthNum);

        if ($request->filled('remark')) {
            $baseQuery->where('a.remark', $request->remark);
        }

        $query = (clone $baseQuery)
            ->select(
                DB::raw('DATE(b.tanggal_iklan) as tanggal_iklan'),
                'b.email',
                'b.id_iklan',
                'b.nama_iklan',
                'b.nama_instansi',
                'b.area_provinsi',
                'b.campaign_type',
                'b.inventory_type',
                'b.total',
                'b.success',
                'b.failed',
                DB::raw('CAST(b.balance_terpakai AS UNSIGNED) as balance_terpakai'),
                DB::raw('COALESCE(su.saldo_utama, 0) as saldo_utama'),
                DB::raw('COALESCE(su.saldo_monet, 0) as saldo_monet'),
                'b.wording as pesan',
                'b.campaign_status',
                'a.remark'
            );

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

        return datatables()->of($query)
            ->with('summary', $summary)
            ->editColumn('balance_terpakai', function ($row) {
                return 'Rp ' . number_format($row->balance_terpakai, 0, ',', '.');
            })
            ->editColumn('saldo_utama', function ($row) {
                return 'Rp ' . number_format((float) $row->saldo_utama, 0, ',', '.');
            })
            ->editColumn('saldo_monet', function ($row) {
                return 'Rp ' . number_format((float) $row->saldo_monet, 0, ',', '.');
            })
            ->make(true);
    }

    public function exportCampaignSbp(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            [$year, $monthNum] = explode('-', $month);
            $remark = $request->get('remark');

            $saldoUsersSub = $this->campaignSbpSaldoUsersSubquery($year, $monthNum, $remark);

            $query = DB::table('mitra_sbp as a')
                ->join('data_campaign_seasonal as b', 'a.email_myads', '=', 'b.email')
                ->leftJoinSub($saldoUsersSub, 'su', function ($join) {
                    $join->on(DB::raw('LOWER(TRIM(b.email))'), '=', 'su.email_key');
                })
                ->select(
                    DB::raw('DATE(b.tanggal_iklan) as tanggal_iklan'),
                    'b.email',
                    'b.id_iklan',
                    'b.nama_iklan',
                    'b.nama_instansi',
                    'b.area_provinsi',
                    'b.campaign_type',
                    'b.inventory_type',
                    'b.total',
                    'b.success',
                    'b.failed',
                    'b.balance_terpakai',
                    DB::raw('COALESCE(su.saldo_utama, 0) as saldo_utama'),
                    DB::raw('COALESCE(su.saldo_monet, 0) as saldo_monet'),
                    'b.wording as pesan',
                    'b.campaign_status',
                    'a.remark'
                )
                ->whereYear('b.tanggal_iklan', $year)
                ->whereMonth('b.tanggal_iklan', $monthNum);

            if (!empty($remark)) {
                $query->where('a.remark', $remark);
            }

            $data = $query->orderBy('b.tanggal_iklan', 'desc')->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $titleRemark = $remark ?: 'Semua';
            $sheet->setCellValue('A1', 'REPORT CAMPAIGN SBP - ' . strtoupper($titleRemark) . ' - ' . $month);
            $sheet->mergeCells('A1:Q1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers = [
                'Tanggal Iklan',
                'Email',
                'ID Iklan',
                'Nama Iklan',
                'Nama Instansi',
                'Area Provinsi',
                'Campaign Type',
                'Inventory Type',
                'Total',
                'Success',
                'Failed',
                'Balance Terpakai',
                'Sisa Saldo Utama',
                'Sisa Saldo Monet',
                'Pesan',
                'Campaign Status',
                'Remark'
            ];
            $sheet->fromArray($headers, null, 'A3');

            $sheet->getStyle('A3:Q3')->applyFromArray([
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
                    $row->email,
                    $row->id_iklan,
                    $row->nama_iklan,
                    $row->nama_instansi,
                    $row->area_provinsi,
                    $row->campaign_type,
                    $row->inventory_type,
                    $row->total,
                    $row->success,
                    $row->failed,
                    $row->balance_terpakai,
                    $row->saldo_utama,
                    $row->saldo_monet,
                    $row->pesan,
                    $row->campaign_status,
                    $row->remark,
                ], null, 'A' . $rowNum);
                $rowNum++;
            }

            foreach (range('A', 'Q') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'Report_Campaign_SBP_' . ($remark ?: 'Semua') . '_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);

        } catch (\Exception $e) {
            \Log::error('Export Campaign SBP Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data');
        }
    }
    public function exportMitraSBP()
    {
        try {
            // =========================
            // FILTER BULAN
            // =========================
            $month = request()->get('month'); // format: YYYY-MM

            if (!$month) {
                return redirect()->back()->with('error', 'Filter bulan wajib diisi');
            }

            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $endDate   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

            // =========================
            // QUERY DATA
            // =========================
            $data = DB::table('mitra_sbp as a')
                ->leftJoin('report_balance_top_up as b', function ($join) use ($startDate, $endDate) {
                    $join->on('a.email_myads', '=', 'b.email_client')
                        ->whereBetween('b.tgl_transaksi', [$startDate, $endDate]);
                })
                ->where('a.remark', 'Mitra SBP')
                ->groupBy(
                    'a.remark',
                    'a.area',
                    'a.regional',
                    'a.email_myads',
                    'b.user_id'
                )
                ->select(
                    'a.remark',
                    'a.area',
                    'a.regional',
                    'a.email_myads',
                    'b.user_id',
                    DB::raw('SUM(COALESCE(b.total_settlement_klien, 0)) as total_settlement_klien')
                )
                ->get();
                
            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data Mitra SBP');
            }

            // =========================
            // PREPARE EXPORT DATA
            // =========================
            $exportData = [];
            foreach ($data as $row) {
                $exportData[] = [
                    'Remark' => $row->remark,
                    'Area' => $row->area,
                    'Regional' => $row->regional,
                    'Email MyAds' => $row->email_myads,
                    'User ID' => $row->user_id ?? '-',
                    'Total Settlement Klien' => $row->total_settlement_klien,
                ];
            }

            // =========================
            // FILE NAME
            // =========================
            $fileName = 'Export_Mitra_SBP_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($exportData) {

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // HEADER
                $headers = [
                    'Remark',
                    'Area',
                    'Regional',
                    'Email MyAds',
                    'User ID',
                    'Total Settlement Klien'
                ];
                $sheet->fromArray($headers, null, 'A1');

                // HEADER STYLE
                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '28A745']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                // DATA
                $rowNum = 2;
                foreach ($exportData as $item) {
                    $sheet->fromArray($item, null, 'A' . $rowNum);
                    $rowNum++;
                }

                // FORMAT ANGKA
                $sheet->getStyle('F2:F' . ($rowNum - 1))
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // COLUMN WIDTH
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(22);

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');

            }, $fileName);

        } catch (\Exception $e) {
            \Log::error('Export Mitra SBP Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Mitra SBP');
        }
    }
    public function exportAgency()
    {
        try {
            // =========================
            // FILTER BULAN
            // =========================
            $month = request()->get('month'); // format: YYYY-MM

            if (!$month) {
                return redirect()->back()->with('error', 'Filter bulan wajib diisi');
            }

            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $endDate   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

            // =========================
            // QUERY DATA
            // =========================
            $data = DB::table('mitra_sbp as a')
                ->leftJoin('report_balance_top_up as b', function ($join) use ($startDate, $endDate) {
                    $join->on('a.email_myads', '=', 'b.email_client')
                        ->whereBetween('b.tgl_transaksi', [$startDate, $endDate]);
                })
                ->where('a.remark', 'Agency')
                ->groupBy(
                    'a.remark',
                    'a.area',
                    'a.regional',
                    'a.email_myads',
                    'b.user_id'
                )
                ->select(
                    'a.remark',
                    'a.area',
                    'a.regional',
                    'a.email_myads',
                    'b.user_id',
                    DB::raw('SUM(COALESCE(b.total_settlement_klien, 0)) as total_settlement_klien')
                )
                ->get();
                
            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data Agency');
            }

            // =========================
            // PREPARE EXPORT DATA
            // =========================
            $exportData = [];
            foreach ($data as $row) {
                $exportData[] = [
                    'Remark' => $row->remark,
                    'Area' => $row->area,
                    'Regional' => $row->regional,
                    'Email MyAds' => $row->email_myads,
                    'User ID' => $row->user_id ?? '-',
                    'Total Settlement Klien' => $row->total_settlement_klien,
                ];
            }

            // =========================
            // FILE NAME
            // =========================
            $fileName = 'Export_Agency_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($exportData) {

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // HEADER
                $headers = [
                    'Remark',
                    'Area',
                    'Regional',
                    'Email MyAds',
                    'User ID',
                    'Total Settlement Klien'
                ];
                $sheet->fromArray($headers, null, 'A1');

                // HEADER STYLE
                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '28A745']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                // DATA
                $rowNum = 2;
                foreach ($exportData as $item) {
                    $sheet->fromArray($item, null, 'A' . $rowNum);
                    $rowNum++;
                }

                // FORMAT ANGKA
                $sheet->getStyle('F2:F' . ($rowNum - 1))
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // COLUMN WIDTH
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(22);

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');

            }, $fileName);

        } catch (\Exception $e) {
            \Log::error('Export Agency Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Agency');
        }
    }
    public function exportInternal()
    {
        try {
            // =========================
            // FILTER BULAN
            // =========================
            $month = request()->get('month'); // format: YYYY-MM

            if (!$month) {
                return redirect()->back()->with('error', 'Filter bulan wajib diisi');
            }

            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $endDate   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

            // =========================
            // QUERY DATA
            // =========================
            $data = DB::table('mitra_sbp as a')
                ->leftJoin('report_balance_top_up as b', function ($join) use ($startDate, $endDate) {
                    $join->on('a.email_myads', '=', 'b.email_client')
                        ->whereBetween('b.tgl_transaksi', [$startDate, $endDate]);
                })
                ->where('a.remark', 'Internal')
                ->groupBy(
                    'a.remark',
                    'a.area',
                    'a.regional',
                    'a.email_myads',
                    'b.user_id'
                )
                ->select(
                    'a.remark',
                    'a.area',
                    'a.regional',
                    'a.email_myads',
                    'b.user_id',
                    DB::raw('SUM(COALESCE(b.total_settlement_klien, 0)) as total_settlement_klien')
                )
                ->get();
                
            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data Internal');
            }

            // =========================
            // PREPARE EXPORT DATA
            // =========================
            $exportData = [];
            foreach ($data as $row) {
                $exportData[] = [
                    'Remark' => $row->remark,
                    'Area' => $row->area,
                    'Regional' => $row->regional,
                    'Email MyAds' => $row->email_myads,
                    'User ID' => $row->user_id ?? '-',
                    'Total Settlement Klien' => $row->total_settlement_klien,
                ];
            }

            // =========================
            // FILE NAME
            // =========================
            $fileName = 'Export_Internal_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($exportData) {

                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // HEADER
                $headers = [
                    'Remark',
                    'Area',
                    'Regional',
                    'Email MyAds',
                    'User ID',
                    'Total Settlement Klien'
                ];
                $sheet->fromArray($headers, null, 'A1');

                // HEADER STYLE
                $sheet->getStyle('A1:F1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '28A745']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                // DATA
                $rowNum = 2;
                foreach ($exportData as $item) {
                    $sheet->fromArray($item, null, 'A' . $rowNum);
                    $rowNum++;
                }

                // FORMAT ANGKA
                $sheet->getStyle('F2:F' . ($rowNum - 1))
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                // COLUMN WIDTH
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(22);

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');

            }, $fileName);

        } catch (\Exception $e) {
            \Log::error('Export Internal Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Internal');
        }
    }
}
