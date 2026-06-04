<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $baseDate = now()->startOfMonth(); // 🔥 ini kunci

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);

            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month
            ];
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
        $selectedRemark = $request->get('remark', '');

        $months = [];
        $baseDate = now()->startOfMonth(); // 🔥 ini kunci

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);

            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month
            ];
        }

        // ✅ Mapping statis
        $areaRegionalMap = [
            'Area1' => ['SUMBAGSEL', 'SUMBAGUT', 'SUMBAGTENG'],
            'Area2' => ['JAKARTA BANTEN', 'EASTERN JABOTABEK', 'JABAR'],
            'Area3' => ['BALNUS', 'JATENG DIY', 'JATIM'],
            'Area4' => ['KALIMANTAN', 'PUMA', 'SULAWESI'],
            'HQ'    => ['HQ'],
        ];

        $areas = array_keys($areaRegionalMap);

        $pageTitle = 'Report Campaign';

        return view('mitra-sbp.report-campaign-sbp', compact(
            'months',
            'month',
            'selectedRemark',
            'pageTitle',
            'areas',
            'areaRegionalMap'
        ));
    }


    public function reportSaldoSbp(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');

        $months = [];
        $baseDate = now()->startOfMonth(); // 🔥 ini kunci

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);

            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month
            ];
        }
        
        $lastUpdated = DB::table('saldo_users')->max('updated_at');

        // Optional: format langsung di controller
        if ($lastUpdated) {
            $lastUpdated = Carbon::parse($lastUpdated)->format('d F Y, H:i:s');
        }

        // ✅ Mapping statis
        $areaRegionalMap = [
            'Area1' => ['SUMBAGSEL', 'SUMBAGUT', 'SUMBAGTENG'],
            'Area2' => ['JAKARTA BANTEN', 'EASTERN JABOTABEK', 'JABAR'],
            'Area3' => ['BALNUS', 'JATENG DIY', 'JATIM'],
            'Area4' => ['KALIMANTAN', 'PUMA', 'SULAWESI'],
            'HQ'    => ['HQ'],
        ];

        $areas = array_keys($areaRegionalMap);

        $pageTitle = 'Report Saldo';

        return view('mitra-sbp.report-saldo-sbp', compact(
            'months', 
            'month', 
            'selectedRemark', 
            'pageTitle',
            'areas',
            'areaRegionalMap',
            'lastUpdated' // ⬅️ kirim ke view
            ));
    }
    public function reportBalanceTopUp()
    {
        logUserLogin();

        return view('report.report-balance-top-up');
    }

    public function reportBalanceTopUpData(Request $request)
    {
        $leadOwnerSubQuery = DB::table('leads_master as lm')
            ->leftJoin('users as ulm', 'ulm.id', '=', 'lm.user_id')
            ->selectRaw('LOWER(TRIM(lm.email)) as email_key')
            ->selectRaw('MAX(ulm.name) as lead_owner_name')
            ->selectRaw('MAX(lm.company_name) as lead_company_name')
            ->selectRaw('MAX(lm.nama) as lead_contact_name')
            ->whereNotNull('lm.email')
            ->where('lm.email', '!=', '')
            ->groupBy(DB::raw('LOWER(TRIM(lm.email))'));

        $mitraOwnerSubQuery = DB::table('mitra_sbp as ms')
            ->selectRaw('LOWER(TRIM(ms.email_myads)) as email_key')
            ->selectRaw('MAX(ms.remark) as mitra_owner_name')
            ->whereNotNull('ms.email_myads')
            ->where('ms.email_myads', '!=', '')
            ->groupBy(DB::raw('LOWER(TRIM(ms.email_myads))'));

        $b2bOwnerSubQuery = DB::table('b2b_clients as bc')
            ->leftJoin('users as ubc_by_id', 'ubc_by_id.id', '=', 'bc.user_id')
            ->leftJoin('users as ubc_by_email', function ($join) {
                $join->on(
                    DB::raw('LOWER(TRIM(ubc_by_email.email))'),
                    '=',
                    DB::raw('LOWER(TRIM(bc.myads_account))')
                );
            })
            ->selectRaw('LOWER(TRIM(bc.myads_account)) as email_key')
            ->selectRaw('MAX(COALESCE(ubc_by_id.name, ubc_by_email.name)) as b2b_owner_name')
            ->whereNotNull('bc.myads_account')
            ->where('bc.myads_account', '!=', '')
            ->groupBy(DB::raw('LOWER(TRIM(bc.myads_account))'));

        $query = DB::table('report_balance_top_up as rb')
            ->leftJoin('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->leftJoinSub($leadOwnerSubQuery, 'lo', function ($join) {
                $join->on(
                    DB::raw('LOWER(TRIM(rb.email_client))'),
                    '=',
                    'lo.email_key'
                );
            })
            ->leftJoinSub($mitraOwnerSubQuery, 'mo', function ($join) {
                $join->on(
                    DB::raw('LOWER(TRIM(rb.email_client))'),
                    '=',
                    'mo.email_key'
                );
            })
            ->leftJoinSub($b2bOwnerSubQuery, 'bo', function ($join) {
                $join->on(
                    DB::raw('LOWER(TRIM(rb.email_client))'),
                    '=',
                    'bo.email_key'
                );
            })
            ->select(
                'rb.no_invoice',
                'rb.email_client',
                'rb.company_name',
                DB::raw('COALESCE(CONCAT(lo.lead_owner_name, " (Canvasser)"), mo.mitra_owner_name, concat(bo.b2b_owner_name, " (B2B)"), "-") as owner_name'),
                DB::raw('CAST(COALESCE(rb.amount, 0) AS DECIMAL(15,2)) as amount'),
                DB::raw('CAST(COALESCE(rb.discount_voucer, 0) AS DECIMAL(15,2)) as discount_voucher'),
                DB::raw('CAST(COALESCE(rb.total_settlement_klien, 0) AS DECIMAL(15,2)) as total_settlement'),
                'rb.payment_method_name',
                'rb.paid_date',
                'rb.tgl_transaksi',
                'dv.voucher_code'
            );

        // Wajib filter tanggal agar query tetap cepat pada data besar.
        if (!$request->filled('start_date') || !$request->filled('end_date')) {
            $query->whereRaw('1 = 0');
        } else {
            try {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->whereBetween('rb.tgl_transaksi', [$startDate, $endDate]);
            } catch (\Exception $e) {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('email')) {
            $email = preg_replace('/\s+/', '', strtolower(trim($request->email)));
            $query->whereRaw('LOWER(REPLACE(TRIM(rb.email_client), " ", "")) = ?', [$email]);
        }

        if ($request->filled('name')) {
            $name = '%' . strtolower(trim($request->name)) . '%';
            $query->where(function ($q) use ($name) {
                $q->whereRaw('LOWER(COALESCE(lo.lead_owner_name, "")) LIKE ?', [$name])
                    ->orWhereRaw('LOWER(COALESCE(mo.mitra_owner_name, "")) LIKE ?', [$name])
                    ->orWhereRaw('LOWER(COALESCE(bo.b2b_owner_name, "")) LIKE ?', [$name])
                    ->orWhereRaw('LOWER(COALESCE(lo.lead_company_name, "")) LIKE ?', [$name])
                    ->orWhereRaw('LOWER(COALESCE(lo.lead_contact_name, "")) LIKE ?', [$name])
                    ->orWhereRaw('LOWER(COALESCE(rb.company_name, "")) LIKE ?', [$name]);
            });
        }

        $query->orderByDesc('rb.paid_date');

        return datatables()->of($query)
            ->editColumn('no_invoice', function ($row) {
                return $row->no_invoice ?: '-';
            })
            ->editColumn('owner_name', function ($row) {
                return $row->owner_name ?: '-';
            })
            ->editColumn('amount', function ($row) {
                return 'Rp ' . number_format((float) $row->amount, 0, ',', '.');
            })
            ->editColumn('discount_voucher', function ($row) {
                return 'Rp ' . number_format((float) $row->discount_voucher, 0, ',', '.');
            })
            ->editColumn('total_settlement', function ($row) {
                return 'Rp ' . number_format((float) $row->total_settlement, 0, ',', '.');
            })
            ->editColumn('paid_date', function ($row) {
                return $row->paid_date
                    ? Carbon::parse($row->paid_date)->format('d-m-Y H:i:s')
                    : '-';
            })
            ->editColumn('tgl_transaksi', function ($row) {
                return $row->tgl_transaksi
                    ? Carbon::parse($row->tgl_transaksi)->format('d-m-Y H:i:s')
                    : '-';
            })
            ->editColumn('voucher_code', function ($row) {
                return $row->voucher_code ?: '-';
            })
            ->make(true);
    }

    public function reportSaldoAdvertising(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');

        $months = [];
        $baseDate = now()->startOfMonth(); // 🔥 ini kunci

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);

            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month
            ];
        }

        $pageTitle = 'Report Saldo Agency Advertising';

        return view('mitra-sbp.report-saldo-advertising', compact('months', 'month', 'selectedRemark', 'pageTitle'));
    }

    public function reportSaldoMaxim(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');

        $months = [];
        $baseDate = now()->startOfMonth(); // 🔥 ini kunci

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);

            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month
            ];
        }

        $pageTitle = 'Report Saldo Maxim';

        return view('mitra-sbp.report-saldo-maxim', compact('months', 'month', 'selectedRemark', 'pageTitle'));
    }

    public function reportSaldoAutomatech(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');

        $months = [];
        $baseDate = now()->startOfMonth(); // 🔥 ini kunci

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);

            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month
            ];
        }

        $pageTitle = 'Report Saldo Automatech';

        return view('mitra-sbp.report-saldo-automatech', compact('months', 'month', 'selectedRemark', 'pageTitle'));
    }

    public function reportSaldoAvalonKemangBogor(Request $request)
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
                'selected' => $date->format('Y-m') === $month
            ];
        }

        $pageTitle = 'Report Saldo Avalon Kemang Bogor';
        $dataRoute = 'report-saldo-avalon-kemang-bogor.data';

        return view('mitra-sbp.report-saldo-automatech', compact('months', 'month', 'selectedRemark', 'pageTitle', 'dataRoute'));
    }

    public function reportAgencyAdvertising(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');

        $months = [];
        $baseDate = now()->startOfMonth(); // 🔥 ini kunci

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);

            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month
            ];
        }

        $pageTitle = 'Report Campaign Agency Advertising';

        return view('mitra-sbp.report-agency-advertising', compact('months', 'month', 'selectedRemark', 'pageTitle'));
    }

    public function reportMaxim(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');

        $months = [];
        $baseDate = now()->startOfMonth(); // 🔥 ini kunci

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);

            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month
            ];
        }

        $pageTitle = 'Report Campaign Maxim';

        return view('mitra-sbp.report-maxim', compact('months', 'month', 'selectedRemark', 'pageTitle'));
    }


    public function reportAutomatech(Request $request)
    {
        logUserLogin();

        $month = $request->get('month', now()->format('Y-m'));
        $selectedRemark = $request->get('remark', '');

        $months = [];
        $baseDate = now()->startOfMonth(); // 🔥 ini kunci

        for ($i = 0; $i < 12; $i++) {
            $date = $baseDate->copy()->subMonths($i);

            $months[] = [
                'value' => $date->format('Y-m'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m') === $month
            ];
        }

        $pageTitle = 'Report Campaign Automatech';

        return view('mitra-sbp.report-automatech', compact('months', 'month', 'selectedRemark', 'pageTitle'));
    }

    public function reportAvalonKemangBogor(Request $request)
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
                'selected' => $date->format('Y-m') === $month
            ];
        }

        $pageTitle = 'Report Campaign Avalon Kemang Bogor';
        $dataRoute = 'report-avalon-kemang-bogor.data';
        $exportRoute = 'report-avalon-kemang-bogor.export';
        $filterTitle = 'AVALON KEMANG BOGOR REPORT FILTER';

        return view('mitra-sbp.report-automatech', compact('months', 'month', 'selectedRemark', 'pageTitle', 'dataRoute', 'exportRoute', 'filterTitle'));
    }
    protected function automatechUploadedReportBaseQuery(Carbon $startDate, Carbon $endDate)
    {
        return DB::table('automatech_reports as ar')
            ->whereBetween('ar.tgl_tayang', [
                $startDate->copy()->format('Y-m-d'),
                $endDate->copy()->format('Y-m-d'),
            ]);
    }
    protected function maximUploadedReportBaseQuery(Carbon $startDate, Carbon $endDate)
    {
        return DB::table('maxim_reports as mr')
            ->whereBetween('mr.tgl_tayang', [
                $startDate->copy()->format('Y-m-d'),
                $endDate->copy()->format('Y-m-d'),
            ]);
    }

    public function reportSaldoSbpData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);

        $saldoQuery = DB::table('saldo_users')
            ->select(
                'id_user',
                DB::raw('COALESCE(saldo_utama,0) as saldo_utama'),
                DB::raw('COALESCE(saldo_monet,0) as saldo_monet'),
                DB::raw('saldo_exp_utama'),
                DB::raw('saldo_exp_monet')
            );


        $baseQuery = DB::table('mitra_sbp as a')
            ->leftJoinSub($saldoQuery, 'b', function ($join) {
                $join->on('a.reg_id', '=', 'b.id_user');
            })
            ->select(
                'a.area',
                'a.regional',
                'a.email_myads',
                'a.remark',
                'b.saldo_utama',
                'b.saldo_monet',
                'b.saldo_exp_utama',
                'b.saldo_exp_monet'
            );

            // dd($baseQuery->get()->take(5));

        if ($request->filled('remark')) {
            $baseQuery->where('a.remark', $request->remark);
        }

        if ($request->filled('area')) {
            $baseQuery->where('a.area', $request->area);
        }

        if ($request->filled('regional')) {
            $baseQuery->where('a.regional', $request->regional);
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

    public function exportSaldoSbp(Request $request)
    {
        try {
            $remark = $request->get('remark');
            $area = $request->get('area');
            $regional = $request->get('regional');

            $saldoQuery = DB::table('saldo_users')
                ->select(
                    'id_user',
                    DB::raw('COALESCE(saldo_utama,0) as saldo_utama'),
                    DB::raw('COALESCE(saldo_monet,0) as saldo_monet'),
                    DB::raw('saldo_exp_utama'),
                    DB::raw('saldo_exp_monet')
                );

            $query = DB::table('mitra_sbp as a')
                ->leftJoinSub($saldoQuery, 'b', function ($join) {
                    $join->on('a.reg_id', '=', 'b.id_user');
                })
                ->select(
                    'a.area',
                    'a.regional',
                    'a.email_myads',
                    'a.remark',
                    DB::raw('COALESCE(b.saldo_utama,0) as saldo_utama'),
                    DB::raw('COALESCE(b.saldo_monet,0) as saldo_monet'),
                    'b.saldo_exp_utama',
                    'b.saldo_exp_monet'
                );

            if (!empty($remark)) {
                $query->where('a.remark', $remark);
            }
            if (!empty($area)) {
                $query->where('a.area', $area);
            }
            if (!empty($regional)) {
                $query->where('a.regional', $regional);
            }

            $data = $query
                ->orderByRaw('COALESCE(b.saldo_utama, 0) DESC')
                ->orderBy('a.email_myads')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $titleRemark = $remark ?: 'Semua';
            $titleArea = $area ?: 'Semua Area';
            $titleRegional = $regional ?: 'Semua Regional';

            $sheet->setCellValue(
                'A1',
                'REPORT SALDO SBP - ' .
                strtoupper($titleRemark) .
                ' - ' . $titleArea .
                ' - ' . $titleRegional
            );
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers = [
                'Area',
                'Regional',
                'Email',
                'Remark',
                'Saldo Utama',
                'Saldo Monet',
                'Saldo Exp Utama',
                'Saldo Exp Monet',
            ];
            $sheet->fromArray($headers, null, 'A3');

            $sheet->getStyle('A3:H3')->applyFromArray([
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
                    $row->area,
                    $row->regional,
                    $row->email_myads,
                    $row->remark,
                    (float) $row->saldo_utama,
                    (float) $row->saldo_monet,
                    $row->saldo_exp_utama,
                    $row->saldo_exp_monet,
                ], null, 'A' . $rowNum);
                $rowNum++;
            }

            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $safeRemark = str_replace(' ', '_', $remark ?: 'Semua');
            $safeArea = str_replace(' ', '_', $area ?: 'AllArea');
            $safeRegional = str_replace(' ', '_', $regional ?: 'AllRegional');

            $fileName = 'Report_Saldo_SBP_' .
                $safeRemark . '_' .
                $safeArea . '_' .
                $safeRegional . '_' .
                now()->format('Ymd_His') . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Export Saldo SBP Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data');
        }
    }

    public function reportSaldoAdvertisingData(Request $request)
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


        $baseQuery = DB::table('agency_advertising as a')
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

            // dd($baseQuery->get()->take(5));

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

    public function reportSaldoMaximData(Request $request)
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

        $baseQuery = DB::table('maxim as a')
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

    public function reportSaldoAutomatechData(Request $request)
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

        $baseQuery = DB::table('automatech as a')
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

    public function reportSaldoAvalonKemangBogorData(Request $request)
    {
        return $this->reportSaldoAutomatechData($request);
    }

    public function reportCampaignSbpData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);
        $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

        $baseQuery = DB::table('mitra_sbp as a')
            ->join('myads_request_soadb as b', 'a.reg_id', '=', 'b.user_id')
            ->whereBetween('b.created_at', [$startDate, $endDate]);

        if ($request->filled('remark')) {
            $baseQuery->where('a.remark', $request->remark);
        }

        if ($request->filled('area_provinsi')) {
            $baseQuery->where('a.area', $request->area_provinsi);
        }

        if ($request->filled('regional')) {
            $baseQuery->where('a.regional', $request->regional);
        }

        $query = (clone $baseQuery)
            ->select(
                DB::raw('DATE(COALESCE(b.broadcast_date, b.created_at)) as tanggal_iklan'),
                'b.broadcast_date',
                'a.email_myads as email',
                'b.id_iklan',
                'b.nama_iklan',
                'b.nama_brand as nama_instansi',
                'b.area_provinsi',
                'b.tipe_iklan as campaign_type',
                'b.tipe_inventori as inventory_type',
                'b.total',
                'b.sukses as success',
                'b.gagal as failed',
                'b.delivered',
                'b.read',
                'b.click',
                DB::raw('CAST(b.balance_terpakai AS UNSIGNED) as balance_terpakai'),
                'b.pesan as pesan',
                'b.status as campaign_status',
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
            ->make(true);
    }

    public function reportAgencyAdvertisingData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);
        $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

        $baseQuery = DB::table('agency_advertising as a')
            ->join('myads_request_soadb as b', 'a.reg_id', '=', 'b.user_id')
            ->whereBetween('b.created_at', [$startDate, $endDate]);

        $query = (clone $baseQuery)
            ->select(
                DB::raw('DATE(COALESCE(b.broadcast_date, b.created_at)) as tanggal_iklan'),
                'b.broadcast_date',
                'a.email_myads as email',
                'b.id_iklan',
                'b.nama_iklan',
                'b.nama_brand as nama_instansi',
                'b.area_provinsi',
                'b.tipe_iklan as campaign_type',
                'b.tipe_inventori as inventory_type',
                'b.total',
                'b.sukses as success',
                'b.gagal as failed',
                'b.delivered',
                'b.read',
                'b.click',
                DB::raw('CAST(b.balance_terpakai AS UNSIGNED) as balance_terpakai'),
                'b.pesan as pesan',
                'b.status as campaign_status',
                'a.remark'
            );

        $summaryRow = (clone $baseQuery)
            ->selectRaw('SUM(CAST(COALESCE(b.sukses, 0) AS UNSIGNED)) as success_total')
            ->selectRaw('SUM(CAST(COALESCE(b.gagal, 0) AS UNSIGNED)) as failed_total')
            ->selectRaw('SUM(CAST(COALESCE(b.total, 0) AS UNSIGNED)) as total_campaign')
            ->first();

        $summary = [
            'success' => (int) ($summaryRow->success_total ?? 0),
            'failed' => (int) ($summaryRow->failed_total ?? 0),
            'total' => (int) ($summaryRow->total_campaign ?? 0),
        ];

        return datatables()->of($query)
            ->with('summary', $summary)
            ->editColumn('balance_terpakai', function ($row) {
                return 'Rp ' . number_format($row->balance_terpakai, 0, ',', '.');
            })
            ->make(true);
    }

    public function reportMaximData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);
        $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

        $baseQuery = $this->maximUploadedReportBaseQuery($startDate, $endDate);

        $query = (clone $baseQuery)
            ->select(
                DB::raw('DATE(mr.tgl_tayang) as tanggal_iklan'),
                'mr.id_iklan',
                'mr.judul_pesan_iklan',
                'mr.operator_seluler',
                'mr.kategori_iklan',
                'mr.tipe_kanal',
                'mr.sukses as success',
                DB::raw('(COALESCE(mr.gagal, 0) + COALESCE(mr.refunded, 0)) as failed'),
                'mr.refunded',
                'mr.read',
                'mr.click',
                DB::raw('CASE WHEN COALESCE(mr.sukses, 0) > 0 THEN (COALESCE(mr.read, 0) / mr.sukses) * 100 ELSE 0 END as percentage_read'),
                DB::raw('CASE WHEN COALESCE(mr.read, 0) > 0 THEN (COALESCE(mr.click, 0) / mr.read) * 100 ELSE 0 END as percentage_click'),
                'mr.total_harga',
                'mr.detil_status'
            );

        $summaryRow = (clone $baseQuery)
            ->selectRaw('SUM(COALESCE(mr.sukses, 0)) as total_success')
            ->selectRaw('SUM(COALESCE(mr.gagal, 0) + COALESCE(mr.refunded, 0)) as total_failed')
            ->selectRaw('SUM(COALESCE(mr.refunded, 0)) as total_refunded')
            ->selectRaw('SUM(COALESCE(mr.read, 0)) as total_read')
            ->selectRaw('SUM(COALESCE(mr.click, 0)) as total_click')
            ->selectRaw('SUM(COALESCE(mr.total_harga, 0)) as total_harga')
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


    public function reportAutomatechData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $monthNum] = explode('-', $month);
        $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

        $baseQuery = $this->automatechUploadedReportBaseQuery($startDate, $endDate);

        $query = (clone $baseQuery)
            ->select(
                DB::raw('DATE(ar.tgl_tayang) as tanggal_iklan'),
                'ar.id_iklan',
                'ar.judul_pesan_iklan',
                'ar.operator_seluler',
                'ar.kategori_iklan',
                'ar.tipe_kanal',
                'ar.sukses as success',
                DB::raw('(COALESCE(ar.gagal, 0) + COALESCE(ar.refunded, 0)) as failed'),
                'ar.refunded',
                'ar.read',
                'ar.click',
                'ar.total_harga',
                'ar.detil_status'
            );

        $summaryRow = (clone $baseQuery)
            ->selectRaw('SUM(COALESCE(ar.sukses, 0)) as total_success')
            ->selectRaw('SUM(COALESCE(ar.gagal, 0) + COALESCE(ar.refunded, 0)) as total_failed')
            ->selectRaw('SUM(COALESCE(ar.refunded, 0)) as total_refunded')
            ->selectRaw('SUM(COALESCE(ar.read, 0)) as total_read')
            ->selectRaw('SUM(COALESCE(ar.click, 0)) as total_click')
            ->selectRaw('SUM(COALESCE(ar.total_harga, 0)) as total_harga')
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

    public function reportAvalonKemangBogorData(Request $request)
    {
        return $this->reportAutomatechData($request);
    }

    public function exportCampaignSbp(Request $request)
    {
        try {

            $month = $request->get('month', now()->format('Y-m'));
            [$year, $monthNum] = explode('-', $month);

            $remark = $request->get('remark');
            $area = $request->get('area_provinsi');
            $regional = $request->get('regional');

            $query = DB::table('mitra_sbp as a')
                ->join('myads_request_soadb as b', 'a.reg_id', '=', 'b.user_id')
                ->select(
                    DB::raw('DATE(COALESCE(b.broadcast_date, b.created_at)) as tanggal_iklan'),
                    'b.broadcast_date',
                    'a.email_myads as email',
                    'b.id_iklan',
                    'b.nama_iklan',
                    'b.nama_brand as nama_instansi',
                    'b.area_provinsi',
                    'a.regional',
                    'a.area',
                    'b.tipe_iklan as campaign_type',
                    'b.tipe_inventori as inventory_type',
                    'b.total',
                    'b.sukses as success',
                    'b.gagal as failed',
                    'b.delivered',
                    'b.read',
                    'b.click',
                    'b.balance_terpakai',
                    'b.pesan as pesan',
                    'b.status as campaign_status',
                    'a.remark'
                )
                ->whereYear('b.created_at', $year)
                ->whereMonth('b.created_at', $monthNum);

            // ==============================
            // FILTER
            // ==============================

            if (!empty($remark)) {
                $query->where('a.remark', $remark);
            }

            if (!empty($area)) {
                $query->where('a.area', $area);
            }

            if (!empty($regional)) {
                $query->where('a.regional', $regional);
            }

            $data = $query->orderByRaw('COALESCE(b.broadcast_date, b.created_at) DESC')->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // ==============================
            // TITLE
            // ==============================
            $titleRemark = $remark ?: 'Semua';
            $titleArea = $area ?: 'Semua Area';
            $titleRegional = $regional ?: 'Semua Regional';

            $sheet->setCellValue(
                'A1',
                'REPORT CAMPAIGN SBP - ' .
                strtoupper($titleRemark) .
                ' - ' . $titleArea .
                ' - ' . $titleRegional .
                ' - ' . $month
            );

            $sheet->mergeCells('A1:T1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // ==============================
            // HEADER
            // ==============================
            $headers = [
                'Tanggal Iklan',
                'Broadcast Date',
                'Email',
                'ID Iklan',
                'Nama Iklan',
                'Nama Instansi',
                'Area Provinsi',
                'Regional',
                'Campaign Type',
                'Inventory Type',
                'Total',
                'Success',
                'Failed',
                'Delivered',
                'Read',
                'Click',
                'Balance Terpakai',
                'Pesan',
                'Campaign Status',
                'Remark'
            ];

            $sheet->fromArray($headers, null, 'A3');

            $sheet->getStyle('A3:T3')->applyFromArray([
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

            // ==============================
            // DATA
            // ==============================
            $rowNum = 4;

            foreach ($data as $row) {
                $sheet->fromArray([
                    $row->tanggal_iklan,
                    $row->broadcast_date,
                    $row->email,
                    $row->id_iklan,
                    $row->nama_iklan,
                    $row->nama_instansi,
                    $row->area_provinsi,
                    $row->regional,
                    $row->campaign_type,
                    $row->inventory_type,
                    $row->total,
                    $row->success,
                    $row->failed,
                    $row->delivered,
                    $row->read,
                    $row->click,
                    $row->balance_terpakai,
                    $row->pesan,
                    $row->campaign_status,
                    $row->remark,
                ], null, 'A' . $rowNum);

                $rowNum++;
            }

            foreach (range('A', 'T') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'Report_Campaign_SBP_' .
                ($remark ?: 'Semua') . '_' .
                ($area ?: 'AllArea') . '_' .
                ($regional ?: 'AllRegional') . '_' .
                $month . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);

        } catch (\Exception $e) {
            \Log::error('Export Campaign SBP Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data');
        }
    }

    public function exportAgencyAdvertising(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            [$year, $monthNum] = explode('-', $month);
            $remark = $request->get('remark');

            $query = DB::table('agency_advertising as a')
                ->join('myads_request_soadb as b', 'a.reg_id', '=', 'b.user_id')
                ->select(
                    DB::raw('DATE(COALESCE(b.broadcast_date, b.created_at)) as tanggal_iklan'),
                    'b.broadcast_date',
                    'a.email_myads as email',
                    'b.id_iklan',
                    'b.nama_iklan',
                    'b.nama_brand as nama_instansi',
                    'b.area_provinsi',
                    'b.tipe_iklan as campaign_type',
                    'b.tipe_inventori as inventory_type',
                    'b.total',
                    'b.sukses as success',
                    'b.gagal as failed',
                    'b.delivered',
                    'b.read',
                    'b.click',
                    'b.balance_terpakai',
                    'b.pesan as pesan',
                    'b.status as campaign_status',
                    'a.remark'
                )
                ->whereYear('b.created_at', $year)
                ->whereMonth('b.created_at', $monthNum);

            if (!empty($remark)) {
                $query->where('a.remark', $remark);
            }

            $data = $query->orderByRaw('COALESCE(b.broadcast_date, b.created_at) DESC')->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $titleRemark = $remark ?: 'Semua';
            $sheet->setCellValue('A1', 'REPORT CAMPAIGN AGENCY ADVERTISING - ' . strtoupper($titleRemark) . ' - ' . $month);
            $sheet->mergeCells('A1:S1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers = [
                'Tanggal Iklan',
                'Broadcast Date',
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
                'Delivered',
                'Read',
                'Click',
                'Balance Terpakai',
                'Pesan',
                'Campaign Status',
                'Remark'
            ];
            $sheet->fromArray($headers, null, 'A3');

            $sheet->getStyle('A3:S3')->applyFromArray([
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
                    $row->broadcast_date,
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
                    $row->delivered,
                    $row->read,
                    $row->click,
                    $row->balance_terpakai,
                    $row->pesan,
                    $row->campaign_status,
                    $row->remark,
                ], null, 'A' . $rowNum);
                $rowNum++;
            }

            foreach (range('A', 'S') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'Report_Campaign_Agency_Advertising_' . ($remark ?: 'Semua') . '_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Export Campaign Agency Advertising Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data');
        }
    }

    public function exportMaxim(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            [$year, $monthNum] = explode('-', $month);
            $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
            $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

            $data = $this->maximUploadedReportBaseQuery($startDate, $endDate)
                ->select(
                    DB::raw('DATE(mr.tgl_tayang) as tanggal_iklan'),
                    'mr.id_iklan',
                    'mr.judul_pesan_iklan',
                    'mr.operator_seluler',
                    'mr.kategori_iklan',
                    'mr.tipe_kanal',
                    'mr.sukses as success',
                    DB::raw('(COALESCE(mr.gagal, 0) + COALESCE(mr.refunded, 0)) as failed'),
                    'mr.refunded',
                    'mr.read',
                    'mr.click',
                    DB::raw('CASE WHEN COALESCE(mr.sukses, 0) > 0 THEN (COALESCE(mr.read, 0) / mr.sukses) * 100 ELSE 0 END as percentage_read'),
                    DB::raw('CASE WHEN COALESCE(mr.read, 0) > 0 THEN (COALESCE(mr.click, 0) / mr.read) * 100 ELSE 0 END as percentage_click'),
                    'mr.total_harga',
                    'mr.detil_status'
                )
                ->orderByDesc('mr.tgl_tayang')
                ->orderByDesc('mr.id_iklan')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'REPORT MAXIM - ' . $month);
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

            $fileName = 'Report_Maxim_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Export Maxim Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data');
        }
    }


    public function exportAutomatech(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            [$year, $monthNum] = explode('-', $month);
            $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
            $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

            $data = $this->automatechUploadedReportBaseQuery($startDate, $endDate)
                ->select(
                    DB::raw('DATE(ar.tgl_tayang) as tanggal_iklan'),
                    'ar.id_iklan',
                    'ar.judul_pesan_iklan',
                    'ar.operator_seluler',
                    'ar.kategori_iklan',
                    'ar.tipe_kanal',
                    'ar.sukses as success',
                    DB::raw('(COALESCE(ar.gagal, 0) + COALESCE(ar.refunded, 0)) as failed'),
                    'ar.refunded',
                    'ar.read',
                    'ar.click',
                    'ar.total_harga',
                    'ar.detil_status'
                )
                ->orderByDesc('ar.tgl_tayang')
                ->orderByDesc('ar.id_iklan')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'REPORT AUTOMATECH - ' . $month);
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

            $fileName = 'Report_Automatech_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Export Automatech Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data');
        }
    }

    public function exportAvalonKemangBogor(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            [$year, $monthNum] = explode('-', $month);
            $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
            $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

            $data = $this->automatechUploadedReportBaseQuery($startDate, $endDate)
                ->select(
                    DB::raw('DATE(ar.tgl_tayang) as tanggal_iklan'),
                    'ar.id_iklan',
                    'ar.judul_pesan_iklan',
                    'ar.operator_seluler',
                    'ar.kategori_iklan',
                    'ar.tipe_kanal',
                    'ar.sukses as success',
                    DB::raw('(COALESCE(ar.gagal, 0) + COALESCE(ar.refunded, 0)) as failed'),
                    'ar.refunded',
                    'ar.read',
                    'ar.click',
                    'ar.total_harga',
                    'ar.detil_status'
                )
                ->orderByDesc('ar.tgl_tayang')
                ->orderByDesc('ar.id_iklan')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'REPORT AVALON KEMANG BOGOR - ' . $month);
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

            $fileName = 'Report_Avalon_Kemang_Bogor_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Export Avalon Kemang Bogor Error: ' . $e->getMessage());
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

    private function getPerformanceExportDataByRemark(string $month, string $remark)
    {
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $endDate   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        return DB::table('mitra_sbp as a')
            ->leftJoin('report_balance_top_up as b', function ($join) use ($startDate, $endDate) {
                $join->on('a.email_myads', '=', 'b.email_client')
                    ->whereBetween('b.tgl_transaksi', [$startDate, $endDate]);
            })
            ->where('a.remark', $remark)
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
    }

    private function fillPerformanceExportSheet($sheet, string $title, $data): void
    {
        $sheet->setTitle($title);

        $headers = [
            'Remark',
            'Area',
            'Regional',
            'Email MyAds',
            'User ID',
            'Total Settlement Klien'
        ];
        $sheet->fromArray($headers, null, 'A1');

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

        $rowNum = 2;
        foreach ($data as $row) {
            $sheet->fromArray([
                $row->remark,
                $row->area,
                $row->regional,
                $row->email_myads,
                $row->user_id ?? '-',
                $row->total_settlement_klien,
            ], null, 'A' . $rowNum);
            $rowNum++;
        }

        if ($rowNum > 2) {
            $sheet->getStyle('F2:F' . ($rowNum - 1))
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(22);
    }

    public function exportPerformanceAll(Request $request)
    {
        try {
            $month = $request->get('month'); // format: YYYY-MM
            if (!$month) {
                return redirect()->back()->with('error', 'Filter bulan wajib diisi');
            }

            $sheetConfigs = [
                ['title' => 'Mitra SBP', 'remark' => 'Mitra SBP'],
                ['title' => 'Agency', 'remark' => 'Agency'],
                ['title' => 'Internal', 'remark' => 'Internal'],
            ];

            $spreadsheet = new Spreadsheet();
            $spreadsheet->removeSheetByIndex(0);

            foreach ($sheetConfigs as $idx => $config) {
                $data = $this->getPerformanceExportDataByRemark($month, $config['remark']);

                $sheet = $spreadsheet->createSheet($idx);
                $this->fillPerformanceExportSheet($sheet, $config['title'], $data);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $fileName = 'Export_Performance_All_' . $month . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error('Export Performance All Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export Performance All');
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

        return view('mitra-sbp.report-cdsi', compact('months', 'month', 'selectedRemark', 'pageTitle'));
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
                    'total_success' => 0,
                    'total_failed' => 0,
                    'total_refunded' => 0,
                    'total_click' => 0,
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
            ->selectRaw('SUM(COALESCE(cr.sukses, 0)) as total_success')
            ->selectRaw('SUM(COALESCE(cr.gagal, 0) + COALESCE(cr.refunded, 0)) as total_failed')
            ->selectRaw('SUM(COALESCE(cr.refunded, 0)) as total_refunded')
            ->selectRaw('SUM(COALESCE(cr.read, 0)) as total_read')
            ->selectRaw('SUM(COALESCE(cr.click, 0)) as total_click')
            ->selectRaw('SUM(COALESCE(cr.total_harga, 0)) as total_harga')
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

    public function reportCdsiDormant(Request $request)
    {
        logUserLogin();

        $pageTitle = 'Data Dormant CDSI';

        return view('mitra-sbp.report-cdsi-dormant', compact('pageTitle'));
    }

    protected function cdsiDormantBaseQuery()
    {
        $dormantQuery = DB::table('cdsi_data_dorman as cdd')
            ->selectRaw('cdd.email as email')
            ->selectRaw('cdd.nomor as nomor')
            ->selectRaw('cdd.nama_instansi as nama_instansi')
            ->selectRaw('DATE(cdd.last_tgl_transaksi) as last_tgl_transaksi')
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

        return view('mitra-sbp.report-cdsi-province', compact('months', 'month', 'pageTitle'));
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





