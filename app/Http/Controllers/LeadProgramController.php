<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\RegionalSummary;

class LeadProgramController extends Controller
{

    public function getDailyTopupData($monthFilter = null)
    {
        try {
            // Ambil email dari masing-masing kategori
            $mitraSbpEmails = DB::table('mitra_sbp')
                ->where('remark', 'Mitra SBP')
                ->pluck('email_myads')
                ->toArray();

            $agencyEmails = DB::table('mitra_sbp')
                ->where('remark', 'Agency')
                ->pluck('email_myads')
                ->toArray();

            $outletEmails = DB::table('mitra_sbp')
                ->where('remark', 'Outlet')
                ->pluck('email_myads')
                ->toArray();

            $internalEmails = DB::table('mitra_sbp')
                ->where('remark', 'Internal')
                ->pluck('email_myads')
                ->toArray();

            $advertisingEmails = DB::table('mitra_sbp')
                ->where('remark', 'Agency Advertising')
                ->pluck('email_myads')
                ->toArray();

            // Ambil list cvsr user IDs (untuk check canvasser dengan per-user join logic)
            $canvasserUserIds = DB::table('users')
                ->where('role', 'cvsr')
                ->where('name', '!=', 'self service')
                ->pluck('id')
                ->toArray();

            // Query data topup dari MySQL untuk bulan berjalan atau bulan yang difilter
            if ($monthFilter) {
                $startDate = Carbon::createFromFormat('Y-m-d', $monthFilter)->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::createFromFormat('Y-m-d', $monthFilter)->endOfMonth()->format('Y-m-d');
            } else {
                $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            }

            // Query dengan LEFT JOIN ke leads_master dan mitra_sbp 
            // untuk capture SEMUA transaksi, termasuk yang tidak ada di leads_master
            $topupData = DB::table('report_balance_top_up as rp')
                ->leftJoin('mitra_sbp as m', 'm.email_myads', '=', 'rp.email_client')
                ->leftJoin('leads_master as lm', 'lm.email', '=', 'rp.email_client')
                ->leftJoin('b2b_clients as bc', DB::raw('LOWER(bc.myads_account)'), '=', DB::raw('LOWER(rp.email_client)'))
                ->select(
                    DB::raw("DATE(rp.tgl_transaksi) as tanggal"),
                    'rp.email_client as email',
                    'rp.user_id as id_user',
                    DB::raw("CAST(rp.total_settlement_klien AS DECIMAL(15,2)) as total_settlement"),
                    'm.remark',
                    'lm.user_id as leads_user_id',
                    'bc.id as b2b_client_id'
                )
                ->whereRaw("rp.tgl_transaksi >= ?", [$startDate])
                ->whereRaw("rp.tgl_transaksi <= ?", [$endDate . ' 23:59:59'])
                ->whereNotNull('rp.email_client')
                ->whereNotNull('rp.total_settlement_klien')
                ->where('rp.payment_method_name', '!=', 'Voucher Bonus')
                ->orderBy('rp.tgl_transaksi', 'desc')
                ->get();

            if ($topupData->count() === 0) {
                return [];
            }

            // Group by tanggal dan kategorikan
            $groupedData = [];

            foreach ($topupData as $row) {
                $date = $row->tanggal;

                if (!isset($groupedData[$date])) {
                    $groupedData[$date] = [
                        'mitra_sbp' => ['settlement' => 0, 'users' => []],
                        'agency' => ['settlement' => 0, 'users' => []],
                        'internal' => ['settlement' => 0, 'users' => []],
                        'outlet' => ['settlement' => 0, 'users' => []],
                        'canvasser' => ['settlement' => 0, 'users' => []],
                        'b2b' => ['settlement' => 0, 'users' => []],
                        'advertising' => ['settlement' => 0, 'users' => []],
                    ];
                }

                $email = strtolower(trim($row->email));
                $settlement = floatval($row->total_settlement);
                $userId = $row->id_user;
                $leadsUserId = $row->leads_user_id;

                // PRIORITY 1: Jika email ada di leads_master AND user belongs to cvsr
                // CHECK CVSR FIRST karena ini adalah source of truth paling specific
                // Ini align dengan Home Regional logic (getRegionalData)
                if (!empty($leadsUserId) && in_array($leadsUserId, $canvasserUserIds)) {
                    $groupedData[$date]['canvasser']['settlement'] += $settlement;
                    $groupedData[$date]['canvasser']['users'][] = $userId;
                }
                // PRIORITY 2: Jika email terdaftar di b2b_clients, masukkan ke channel B2B
                elseif (!empty($row->b2b_client_id)) {
                    $groupedData[$date]['b2b']['settlement'] += $settlement;
                    $groupedData[$date]['b2b']['users'][] = $userId;
                }
                // PRIORITY 2: Cek mitra_sbp remark (Internal, Mitra SBP, Agency)
                // HANYA jika tidak ada di leads_master sebagai cvsr
                elseif (!empty($row->remark)) {
                    if ($row->remark === 'Internal') {
                        $groupedData[$date]['internal']['settlement'] += $settlement;
                        $groupedData[$date]['internal']['users'][] = $userId;
                    } elseif ($row->remark === 'Mitra SBP') {
                        $groupedData[$date]['mitra_sbp']['settlement'] += $settlement;
                        $groupedData[$date]['mitra_sbp']['users'][] = $userId;
                    } elseif ($row->remark === 'Agency') {
                        $groupedData[$date]['agency']['settlement'] += $settlement;
                        $groupedData[$date]['agency']['users'][] = $userId;
                    } elseif ($row->remark === 'Outlet') {
                        $groupedData[$date]['outlet']['settlement'] += $settlement;
                        $groupedData[$date]['outlet']['users'][] = $userId;
                    } elseif ($row->remark === 'B2B') {
                        $groupedData[$date]['b2b']['settlement'] += $settlement;
                        $groupedData[$date]['b2b']['users'][] = $userId;
                    } elseif ($row->remark === 'Agency Advertising') {
                        $groupedData[$date]['advertising']['settlement'] += $settlement;
                        $groupedData[$date]['advertising']['users'][] = $userId;
                    } else {
                        // Remark lainnya ke outlet
                        $groupedData[$date]['outlet']['settlement'] += $settlement;
                        $groupedData[$date]['outlet']['users'][] = $userId;
                    }
                }
                // PRIORITY 3: Fallback ke outlet untuk transaksi yang tidak match di atas
                else {
                    $groupedData[$date]['outlet']['settlement'] += $settlement;
                    $groupedData[$date]['outlet']['users'][] = $userId;
                }
            }

            // Format hasil untuk view
            $result = [];
            $totals = [
                'mitra_sbp_settle' => 0,
                'mitra_sbp_user' => [],
                'agency_settle' => 0,
                'agency_user' => [],
                'internal_settle' => 0,
                'internal_user' => [],
                'outlet_settle' => 0,
                'outlet_user' => [],
                'canvasser_settle' => 0,
                'canvasser_user' => [],
                'b2b_settle' => 0,
                'b2b_user' => [],
                'advertising_settle' => 0,
                'advertising_user' => [],
            ];

            // Sort by date descending
            krsort($groupedData);

            foreach ($groupedData as $date => $data) {
                $row = [
                    'date' => Carbon::parse($date)->locale('id')->translatedFormat('d F Y'),
                    'mitra_sbp_settle' => number_format($data['mitra_sbp']['settlement'], 0, ',', '.'),
                    'mitra_sbp_user' => count(array_unique($data['mitra_sbp']['users'])),
                    'internal_settle' => number_format($data['internal']['settlement'], 0, ',', '.'),
                    'internal_user' => count(array_unique($data['internal']['users'])),
                    'agency_settle' => number_format($data['agency']['settlement'], 0, ',', '.'),
                    'agency_user' => count(array_unique($data['agency']['users'])),
                    'self_service_settle' => number_format($data['outlet']['settlement'], 0, ',', '.'),
                    'self_service_user' => count(array_unique($data['outlet']['users'])),
                    'canvasser_settle' => number_format($data['canvasser']['settlement'], 0, ',', '.'),
                    'canvasser_user' => count(array_unique($data['canvasser']['users'])),
                    'b2b_settle' => number_format($data['b2b']['settlement'], 0, ',', '.'),
                    'b2b_user' => count(array_unique($data['b2b']['users'])),
                    'advertising_settle' => number_format($data['advertising']['settlement'], 0, ',', '.'),
                    'advertising_user' => count(array_unique($data['advertising']['users'])),
                    'total' => number_format(
                        $data['mitra_sbp']['settlement'] +
                            $data['internal']['settlement'] +
                            $data['agency']['settlement'] +
                            $data['outlet']['settlement'] +
                            $data['canvasser']['settlement'] +
                            $data['b2b']['settlement']+
                            $data['advertising']['settlement'],
                        0,
                        ',',
                        '.'
                    ),
                    'total_user' => count(array_unique(array_merge(
                        $data['mitra_sbp']['users'],
                        $data['internal']['users'],
                        $data['agency']['users'],
                        $data['outlet']['users'],
                        $data['canvasser']['users'],
                        $data['b2b']['users'],
                        $data['advertising']['users']
                    ))),
                ];

                $result[] = $row;

                // Tambahkan ke total keseluruhan
                $totals['mitra_sbp_settle'] += $data['mitra_sbp']['settlement'];
                $totals['mitra_sbp_user'] = array_merge($totals['mitra_sbp_user'], $data['mitra_sbp']['users']);
                $totals['internal_settle'] += $data['internal']['settlement'];
                $totals['internal_user'] = array_merge($totals['internal_user'], $data['internal']['users']);
                $totals['agency_settle'] += $data['agency']['settlement'];
                $totals['agency_user'] = array_merge($totals['agency_user'], $data['agency']['users']);
                $totals['outlet_settle'] += $data['outlet']['settlement'];
                $totals['outlet_user'] = array_merge($totals['outlet_user'], $data['outlet']['users']);
                $totals['canvasser_settle'] += $data['canvasser']['settlement'];
                $totals['canvasser_user'] = array_merge($totals['canvasser_user'], $data['canvasser']['users']);
                $totals['b2b_settle'] += $data['b2b']['settlement'];
                $totals['b2b_user'] = array_merge($totals['b2b_user'], $data['b2b']['users']);
                $totals['advertising_settle'] += $data['advertising']['settlement'];
                $totals['advertising_user'] = array_merge($totals['advertising_user'], $data['advertising']['users']);
            }

            // Tambahkan row total
            if (!empty($result)) {
                $result[] = [
                    'date' => 'Total Keseluruhan',
                    'mitra_sbp_settle' => number_format($totals['mitra_sbp_settle'], 0, ',', '.'),
                    'mitra_sbp_user' => count(array_unique($totals['mitra_sbp_user'])),
                    'internal_settle' => number_format($totals['internal_settle'], 0, ',', '.'),
                    'internal_user' => count(array_unique($totals['internal_user'])),
                    'agency_settle' => number_format($totals['agency_settle'], 0, ',', '.'),
                    'agency_user' => count(array_unique($totals['agency_user'])),
                    'self_service_settle' => number_format($totals['outlet_settle'], 0, ',', '.'),
                    'self_service_user' => count(array_unique($totals['outlet_user'])),
                    'canvasser_settle' => number_format($totals['canvasser_settle'], 0, ',', '.'),
                    'canvasser_user' => count(array_unique($totals['canvasser_user'])),
                    'b2b_settle' => number_format($totals['b2b_settle'], 0, ',', '.'),
                    'b2b_user' => count(array_unique($totals['b2b_user'])),
                    'advertising_settle' => number_format($totals['advertising_settle'], 0, ',', '.'),
                    'advertising_user' => count(array_unique($totals['advertising_user'])),
                    'total' => number_format(
                        $totals['mitra_sbp_settle'] +
                            $totals['internal_settle'] +
                            $totals['agency_settle'] +
                            $totals['outlet_settle'] +
                            $totals['canvasser_settle'] +
                            $totals['b2b_settle'] +
                            $totals['advertising_settle'],
                        0,
                        ',',
                        '.'
                    ),
                    'total_user' => count(array_unique(array_merge(
                        $totals['mitra_sbp_user'],
                        $totals['internal_user'],
                        $totals['agency_user'],
                        $totals['outlet_user'],
                        $totals['canvasser_user'],
                        $totals['b2b_user'],
                        $totals['advertising_user']
                    ))),
                ];
            }

            return $result;
        } catch (\Exception $e) {
            \Log::error("Error in getDailyTopupData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [];
        }
    }

    public function getTopupByEmailAndRegion()
    {
        $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');

        $data = DB::table('report_balance_top_up as rp')
            ->select(
                'rp.data_province_name as province',
                'rp.email_client as email',
                'rp.user_id',
                DB::raw('SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))) as total_settlement')
            )
            ->whereDate('rp.tgl_transaksi', '>=', $startDate)
            ->whereNotNull('rp.email_client')
            ->whereNotNull('rp.data_province_name')
            ->whereNotNull('rp.total_settlement_klien')
            ->groupBy(
                'rp.data_province_name',
                'rp.email_client',
                'rp.user_id'
            )
            ->orderBy('rp.data_province_name')
            ->orderByDesc('total_settlement')
            ->get();

        // Grouping agar mudah ditampilkan di view
        $result = [];

        foreach ($data as $row) {
            $province = $row->province;

            if (!isset($result[$province])) {
                $result[$province] = [
                    'rows' => [],
                    'grand_total' => 0
                ];
            }

            $result[$province]['rows'][] = [
                'email' => $row->email,
                'user_id' => $row->user_id,
                'total' => number_format($row->total_settlement, 0, ',', '.')
            ];

            $result[$province]['grand_total'] += $row->total_settlement;
        }

        // format grand total
        foreach ($result as $province => $val) {
            $result[$province]['grand_total'] =
                number_format($val['grand_total'], 0, ',', '.');
        }

        return $result;
    }


    public function getDailyTopupDataTable(Request $request)
    {
        try {
            $monthFilter = $request->get('month');
            $result = $this->getDailyTopupData($monthFilter);

            return datatables()->of(collect($result))
                ->make(true);
        } catch (\Exception $e) {
            \Log::error("Error in getDailyTopupDataTable: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getLeadsAndAccountData()
    {
        try {
            // Tanggal 1 bulan yang lalu
            $oneMonthAgo = Carbon::now()->subMonth()->format('Y-m-d');

            // 1. Jumlah Leads dari leads_master dengan data_type = 'leads'
            $totalLeads = DB::table('leads_master')
                ->where('data_type', 'leads')
                ->count();

            // 2. Query untuk mendapatkan data akun existing dan new
            $accountData = DB::table('data_registarsi_status_approveorreject as dt')
                ->join('leads_master as lm', 'dt.email', '=', 'lm.email')
                ->join('users as u', 'lm.user_id', '=', 'u.id')
                ->join('report_balance_top_up as rp', 'dt.email', '=', 'rp.email_client')
                ->leftJoin('regional_tracers as rt', 'lm.email', '=', 'rt.pic_email')
                ->select(
                    'u.name',
                    'dt.email as email_register',
                    'dt.tanggal_approval_aktivasi',
                    DB::raw('CAST(rp.total_settlement_klien AS DECIMAL(15,2)) as total_settlement_klien'),
                    'rt.regional',
                    DB::raw("CASE
                        WHEN STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d') <= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                        THEN 'akun_existing'
                        ELSE 'akun_new'
                    END AS status_akun")
                )
                ->get();

            // Hitung jumlah existing akun
            $existingAkun = $accountData->where('status_akun', 'akun_existing')->unique('email_register')->count();

            // Hitung jumlah new akun
            $newAkun = $accountData->where('status_akun', 'akun_new')->unique('email_register')->count();

            // Hitung total top up new akun
            $topUpNewAkun = $accountData->where('status_akun', 'akun_new')
                ->sum('total_settlement_klien');

            // Hitung total top up existing akun
            $topUpExistingAkun = $accountData->where('status_akun', 'akun_existing')
                ->sum('total_settlement_klien');

            return [
                'total_leads' => $totalLeads,
                'existing_akun' => $existingAkun,
                'new_akun' => $newAkun,
                'top_up_new_akun' => $topUpNewAkun,
                'top_up_existing_akun' => $topUpExistingAkun,
            ];
        } catch (\Exception $e) {
            \Log::error("Error in getLeadsAndAccountData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [
                'total_leads' => 0,
                'existing_akun' => 0,
                'new_akun' => 0,
                'top_up_new_akun' => 0,
                'top_up_existing_akun' => 0,
            ];
        }
    }

    public function getLeadsDataApi()
    {
        try {
            $data = $this->getLeadsAndAccountData();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getRegionalData(Request $request)
    {
        try {
            $monthFilter = $request->get('month');
            $monthDate = null;
            if (!empty($monthFilter)) {
                $monthDate = strlen($monthFilter) === 7
                    ? Carbon::createFromFormat('Y-m-d', $monthFilter . '-01')
                    : Carbon::createFromFormat('Y-m-d', $monthFilter);
            }

            // Create cache key with month filter
            $cacheKey = 'regional_data_' . ($monthFilter ?? Carbon::now()->format('Y-m'));
            
            // Cache hasil query selama 5 menit (300 detik)
            return Cache::remember($cacheKey, 300, function() use ($request, $monthFilter, $monthDate) {
                return $this->processRegionalData($request, $monthFilter, $monthDate);
            });
        } catch (\Exception $e) {
            \Log::error("Error in getRegionalData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [];
        }
    }
    
    private function processRegionalData($request, $monthFilter, $monthDate)
    {
        try {
            // OPTIMIZED: Cache canvassers list (TTL 15 menit) 
            $canvasers = Cache::remember('canvassers_active', 900, function() {
                return DB::table('users')
                    ->where('role', 'cvsr')
                    ->where('name', '!=', 'self service')
                    ->select('id', 'name')
                    ->get();
            });

            if ($canvasers->isEmpty()) {
                return [];
            }

            \Log::info("Total Canvassers found: " . $canvasers->count());
            $canvaserIds = $canvasers->pluck('id')->all();
            $logbookPeriod = $monthDate ? $monthDate->copy() : Carbon::now();
            $logbookMonth = (int) $logbookPeriod->month;
            $logbookYear = (int) $logbookPeriod->year;

            $today = $monthDate ? $monthDate->copy()->endOfMonth() : Carbon::now();
            $todayDate = $today->format('Y-m-d');
            $startOfMonth = ($monthDate ? $monthDate->copy() : Carbon::now())->startOfMonth()->format('Y-m-d');
            $endOfMonth = ($monthDate ? $monthDate->copy() : Carbon::now())->endOfMonth();

            $holidays = [
                '2026-01-01',
                '2026-02-17',
                '2026-03-22',
                '2026-03-23',
                '2026-03-24',
                '2026-04-10',
                '2026-05-01',
                '2026-05-02',
                '2026-05-21',
                '2026-05-30',
                '2026-06-01',
                '2026-06-20',
                '2026-08-17',
                '2026-08-29',
                '2026-12-25',
            ];

            $remainingWorkingDays = 0;
            if ($today->month == Carbon::today()->month && $today->year == Carbon::today()->year) {
                $currentDate = Carbon::today();
            } else {
                $currentDate = $today->copy();
            }

            while ($currentDate->lte($endOfMonth)) {
                $isWeekday = $currentDate->isWeekday();
                $isNotHoliday = !in_array($currentDate->format('Y-m-d'), $holidays);
                if ($isWeekday && $isNotHoliday) {
                    $remainingWorkingDays++;
                }
                $currentDate->addDay();
            }

            $currentMonth = $today->format('Y-m');
            $result = [];

            $totals = [
                'leads' => 0,
                'existing_akun' => 0,
                'new_akun' => 0,
                'top_up_new_akun_count' => 0,
                'top_up_existing_akun_count' => 0,
                'top_up_new_akun_rp' => 0,
                'top_up_existing_akun_rp' => 0,
                'total_top_up_rp' => 0,
                'target' => 0,
                'gap' => 0,
                'mom_prev_partial' => 0,
                'mom_current_partial' => 0,
                'mom_prev_remaining' => 0,
                'mom_gap' => 0,
            ];

            $regionalMap = DB::table('leads_master as lm')
                ->leftJoin('regional_tracers as rt', 'lm.email', '=', 'rt.pic_email')
                ->whereIn('lm.user_id', $canvaserIds)
                ->whereNotNull('rt.regional')
                ->groupBy('lm.user_id')
                ->select('lm.user_id', DB::raw('MIN(rt.regional) as regional'))
                ->pluck('regional', 'lm.user_id');

            $leadStats = DB::table('logbook as lb')
                ->join('leads_master as lm', 'lb.leads_master_id', '=', 'lm.id')
                ->whereIn('lm.user_id', $canvaserIds)
                ->where('lb.bulan', $logbookMonth)
                ->where('lb.tahun', $logbookYear)
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("COUNT(DISTINCT CASE WHEN lm.data_type = 'leads' THEN lb.leads_master_id END) as leads"),
                    DB::raw("COUNT(DISTINCT CASE WHEN lm.data_type = 'Eksisting Akun' THEN lb.leads_master_id END) as existing_akun")
                )
                ->get()
                ->keyBy('user_id');

            $endOfMonthFormatted = $endOfMonth->format('Y-m-d');
            $newAkunStats = DB::table('data_registarsi_status_approveorreject as dt')
                ->join('leads_master as lm', 'dt.email', '=', 'lm.email')
                ->whereIn('lm.user_id', $canvaserIds)
                ->whereBetween(
                    DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')"),
                    [$startOfMonth, $endOfMonthFormatted]
                )
                ->groupBy('lm.user_id')
                ->select('lm.user_id', DB::raw('COUNT(DISTINCT dt.email) as new_akun'))
                ->get()
                ->keyBy('user_id');

            $topUpStatsByUser = DB::table('report_balance_top_up as rp')
                ->join('leads_master as lm', DB::raw('LOWER(rp.email_client)'), '=', DB::raw('LOWER(lm.email)'))
                ->whereIn('lm.user_id', $canvaserIds)
                ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("COUNT(rp.id) as top_up_count"),
                    DB::raw("SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))) as total_top_up_rp")
                )
                ->get()
                ->keyBy('user_id');

            $topUpNewAkunByUser = DB::table('data_registarsi_status_approveorreject as dt')
                ->join('report_balance_top_up as rp', function ($join) {
                    $join->on(DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(rp.email_client)'))
                        ->whereRaw("DATE(rp.tgl_transaksi) >= STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')");
                })
                ->join('leads_master as lm', DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(lm.email)'))
                ->whereIn('lm.user_id', $canvaserIds)
                ->where('dt.status', 'APPROVE')
                ->whereBetween(
                    DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')"),
                    [$startOfMonth, $endOfMonthFormatted]
                )
                ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("COUNT(DISTINCT rp.id) as top_up_count"),
                    DB::raw("SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))) as top_up_new_akun_rp")
                )
                ->get()
                ->keyBy('user_id');

            $topUpExistingAkunByUser = DB::table('data_registarsi_status_approveorreject as dt')
                ->join('leads_master as lm', DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(lm.email)'))
                ->join('report_balance_top_up as rp', function ($join) {
                    $join->on(DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(rp.email_client)'))
                        ->whereRaw("DATE(rp.tgl_transaksi) >= STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')");
                })
                ->whereIn('lm.user_id', $canvaserIds)
                ->where('dt.status', 'APPROVE')
                ->whereRaw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d') < ?", [$startOfMonth])
                ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("COUNT(rp.id) as top_up_existing_akun_count"),
                    DB::raw("SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))) as top_up_existing_akun_rp")
                )
                ->get()
                ->keyBy('user_id');

            $targetByUser = DB::table('target_canvaser')
                ->whereIn('user_id', $canvaserIds)
                ->where('bulan', $currentMonth)
                ->select('user_id', 'target')
                ->get()
                ->keyBy('user_id');

            // FIX: Use filtered month for MoM calculation, not always current month
            $momToday = $monthDate ?? Carbon::now();
            $currentMonthStart = $momToday->copy()->startOfMonth()->format('Y-m-d');
            $currentMonthUntilToday = $momToday->copy()->format('Y-m-d');
            $prevMonthStart = $momToday->copy()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
            $prevMonthSameDay = $momToday->copy()->subMonthNoOverflow()->format('Y-m-d');
            $prevMonthEnd = $momToday->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d');
            $prevMonthRemainingStart = $momToday->copy()->subMonthNoOverflow()->addDay()->format('Y-m-d');

            $momByUser = DB::table('report_balance_top_up as rp')
                ->join('leads_master as lm', 'rp.email_client', '=', 'lm.email')
                ->whereIn('lm.user_id', $canvaserIds)
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("SUM(CASE WHEN DATE(rp.tgl_transaksi) BETWEEN '{$prevMonthStart}' AND '{$prevMonthSameDay}' THEN CAST(rp.total_settlement_klien AS DECIMAL(15,2)) ELSE 0 END) as mom_prev_partial"),
                    DB::raw("SUM(CASE WHEN DATE(rp.tgl_transaksi) BETWEEN '{$currentMonthStart}' AND '{$currentMonthUntilToday}' THEN CAST(rp.total_settlement_klien AS DECIMAL(15,2)) ELSE 0 END) as mom_current_partial"),
                    DB::raw("SUM(CASE WHEN DATE(rp.tgl_transaksi) BETWEEN '{$prevMonthRemainingStart}' AND '{$prevMonthEnd}' THEN CAST(rp.total_settlement_klien AS DECIMAL(15,2)) ELSE 0 END) as mom_prev_remaining")
                )
                ->get()
                ->keyBy('user_id');

            foreach ($canvasers as $index => $canvaser) {
                $canvaserId = $canvaser->id;
                $regional = $regionalMap[$canvaserId] ?? '-';
                $totalLeads = (int) ($leadStats[$canvaserId]->leads ?? 0);
                $existingAkun = (int) ($leadStats[$canvaserId]->existing_akun ?? 0);
                $newAkun = (int) ($newAkunStats[$canvaserId]->new_akun ?? 0);
                $topUpStats = $topUpStatsByUser[$canvaserId] ?? null;
                $topUpNewAkunStats = $topUpNewAkunByUser[$canvaserId] ?? null;
                $topUpExistingAkunStats = $topUpExistingAkunByUser[$canvaserId] ?? null;
                $target = (float) ($targetByUser[$canvaserId]->target ?? 0);
                $topUpPrevMonthPartial = (float) ($momByUser[$canvaserId]->mom_prev_partial ?? 0);
                $topUpCurrentMonthPartial = (float) ($momByUser[$canvaserId]->mom_current_partial ?? 0);
                $topUpPrevMonthRemaining = (float) ($momByUser[$canvaserId]->mom_prev_remaining ?? 0);

                $topUpNewAkunRp = $topUpNewAkunStats->top_up_new_akun_rp ?? 0;
                $topUpExistingAkunRp = $topUpExistingAkunStats->top_up_existing_akun_rp ?? 0;
                $totalTopUpFromStats = $topUpStats->total_top_up_rp ?? 0;

                $splitTotal = $topUpNewAkunRp + $topUpExistingAkunRp;
                $totalTopUp = $splitTotal > 0 ? $splitTotal : $totalTopUpFromStats;

                if ($totalTopUpFromStats > 0 && $splitTotal < $totalTopUpFromStats) {
                    $difference = $totalTopUpFromStats - $splitTotal;
                    $topUpExistingAkunRp += $difference;
                    $totalTopUp = $totalTopUpFromStats;
                }

                $achievementPercent = $target > 0 ? ($totalTopUp / $target) * 100 : 0;
                $gap = $totalTopUp - $target;
                $gapDaily = $remainingWorkingDays > 0 ? $gap / $remainingWorkingDays : 0;
                $gapDaily *= -1;
                $momGap = $topUpCurrentMonthPartial - $topUpPrevMonthPartial;

                $result[] = [
                    'no' => $index + 1,
                    'regional' => $regional,
                    'canvaser_name' => $canvaser->name ?? '-',
                    'leads' => $totalLeads,
                    'existing_akun' => $existingAkun,
                    'new_akun' => $newAkun,
                    'top_up_new_akun_count' => $topUpNewAkunStats->top_up_count ?? 0,
                    'top_up_existing_akun_count' => $topUpExistingAkunStats->top_up_existing_akun_count ?? 0,
                    'top_up_new_akun_rp' => number_format($topUpNewAkunRp, 0, ',', '.'),
                    'top_up_existing_akun_rp' => number_format($topUpExistingAkunRp, 0, ',', '.'),
                    'total_top_up_rp' => number_format($totalTopUp, 0, ',', '.'),
                    'target' => number_format($target, 0, ',', '.'),
                    'achievement_percent' => number_format($achievementPercent, 2, ',', '.') . '%',
                    'gap' => number_format($gap, 0, ',', '.'),
                    'gap_daily' => number_format($gapDaily, 0, ',', '.'),
                    'remaining_days' => $remainingWorkingDays,
                    'mom_prev_partial' => number_format($topUpPrevMonthPartial, 0, ',', '.'),
                    'mom_current_partial' => number_format($topUpCurrentMonthPartial, 0, ',', '.'),
                    'mom_prev_remaining' => number_format($topUpPrevMonthRemaining, 0, ',', '.'),
                    'mom_gap' => number_format($momGap, 0, ',', '.'),
                ];

                $totals['leads'] += $totalLeads;
                $totals['existing_akun'] += $existingAkun;
                $totals['new_akun'] += $newAkun;
                $totals['top_up_new_akun_count'] += $topUpNewAkunStats->top_up_count ?? 0;
                $totals['top_up_existing_akun_count'] += $topUpExistingAkunStats->top_up_existing_akun_count ?? 0;
                $totals['top_up_new_akun_rp'] += $topUpNewAkunRp;
                $totals['top_up_existing_akun_rp'] += $topUpExistingAkunRp;
                $totals['total_top_up_rp'] += $totalTopUp;
                $totals['target'] += $target;
                $totals['gap'] += $gap;
                $totals['mom_prev_partial'] += $topUpPrevMonthPartial;
                $totals['mom_current_partial'] += $topUpCurrentMonthPartial;
                $totals['mom_prev_remaining'] += $topUpPrevMonthRemaining;
                $totals['mom_gap'] += $momGap;
            }

            usort($result, function ($a, $b) {
                $percentA = floatval(str_replace(['%', ','], ['.', '.'], $a['achievement_percent']));
                $percentB = floatval(str_replace(['%', ','], ['.', '.'], $b['achievement_percent']));
                return $percentB <=> $percentA;
            });

            if (!empty($result)) {
                $totalAchievementPercent = $totals['target'] > 0 ? ($totals['total_top_up_rp'] / $totals['target']) * 100 : 0;
                $totalGapDaily = $remainingWorkingDays > 0 ? $totals['gap'] / $remainingWorkingDays : 0;
                $totalGapDaily *= -1;

                $result[] = [
                    'no' => '',
                    'regional' => '',
                    'canvaser_name' => 'TOTAL',
                    'leads' => $totals['leads'],
                    'existing_akun' => $totals['existing_akun'],
                    'new_akun' => $totals['new_akun'],
                    'top_up_new_akun_count' => $totals['top_up_new_akun_count'],
                    'top_up_existing_akun_count' => $totals['top_up_existing_akun_count'],
                    'top_up_new_akun_rp' => number_format($totals['top_up_new_akun_rp'], 0, ',', '.'),
                    'top_up_existing_akun_rp' => number_format($totals['top_up_existing_akun_rp'], 0, ',', '.'),
                    'total_top_up_rp' => number_format($totals['total_top_up_rp'], 0, ',', '.'),
                    'target' => number_format($totals['target'], 0, ',', '.'),
                    'achievement_percent' => number_format($totalAchievementPercent, 2, ',', '.') . '%',
                    'gap' => number_format($totals['gap'], 0, ',', '.'),
                    'gap_daily' => number_format($totalGapDaily, 0, ',', '.'),
                    'remaining_days' => $remainingWorkingDays,
                    'mom_prev_partial' => number_format($totals['mom_prev_partial'], 0, ',', '.'),
                    'mom_current_partial' => number_format($totals['mom_current_partial'], 0, ',', '.'),
                    'mom_prev_remaining' => number_format($totals['mom_prev_remaining'], 0, ',', '.'),
                    'mom_gap' => number_format($totals['mom_gap'], 0, ',', '.'),
                    'is_total' => true
                ];
            }

            \Log::info("Total results: " . count($result));
            return $result;
        } catch (\Exception $e) {
            \Log::error("Error in processRegionalData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [];
        }
    }
     public function getRegionalDataTable(Request $request)
    {
        try {
            $result = $this->getRegionalData($request);
            
            return datatables()->of(collect($result))
                ->make(true);

        } catch (\Exception $e) {
            \Log::error("Error in getRegionalDataTable: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getRegionalChartData(Request $request)
    {
        try {
            $monthFilter = $request->get('month');
            $cacheKey = 'regional_chart_' . ($monthFilter ?? Carbon::now()->format('Y-m'));
            
            // Cache chart data selama 5 menit
            return Cache::remember($cacheKey, 300, function() use ($request, $monthFilter) {
                return $this->processRegionalChartData($request, $monthFilter);
            });
        } catch (\Exception $e) {
            \Log::error("Error in getRegionalChartData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    private function processRegionalChartData($request, $monthFilter)
    {
        try {
            // Parse month filter
            $monthDate = null;
            if (!empty($monthFilter)) {
                $monthDate = strlen($monthFilter) === 7
                    ? Carbon::createFromFormat('Y-m-d', $monthFilter . '-01')
                    : Carbon::createFromFormat('Y-m-d', $monthFilter);
            }

            // Cache canvassers list (TTL 15 menit)
            $canvasers = Cache::remember('canvassers_active', 900, function() {
                return DB::table('users')
                    ->where('role', 'cvsr')
                    ->where('name', '!=', 'self service')
                    ->select('id', 'name')
                    ->get();
            });

            if ($canvasers->isEmpty()) {
                return response()->json(['canvassers' => []]);
            }

            $canvaserIds = $canvasers->pluck('id')->all();
            $logbookPeriod = $monthDate ?? Carbon::now();
            $logbookMonth = (int) $logbookPeriod->month;
            $logbookYear = (int) $logbookPeriod->year;

            $today = $monthDate ? $monthDate->copy()->endOfMonth() : Carbon::now();
            $todayDate = $today->format('Y-m-d');
            $startOfMonth = ($monthDate ? $monthDate->copy() : Carbon::now())->startOfMonth()->format('Y-m-d');
            $endOfMonthFormatted = ($monthDate ? $monthDate->copy() : Carbon::now())->endOfMonth()->format('Y-m-d');
            $currentMonth = $today->format('Y-m');

            // Single optimized query untuk chart - lebih cepat dari multiple queries
            $chartData = DB::table('users as u')
                ->leftJoin('logbook as lb', function($join) use ($logbookMonth, $logbookYear) {
                    $join->on('u.id', '=', 'lb.user_id')
                         ->where('lb.bulan', $logbookMonth)
                         ->where('lb.tahun', $logbookYear);
                })
                ->leftJoin('leads_master as lm', 'lb.leads_master_id', '=', 'lm.id')
                ->leftJoin('data_registarsi_status_approveorreject as dt', function($join) use ($startOfMonth, $endOfMonthFormatted) {
                    $join->on('lm.email', '=', 'dt.email')
                         ->where('dt.status', 'APPROVE')
                         ->whereBetween(DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')"), [$startOfMonth, $endOfMonthFormatted]);
                })
                ->leftJoin('report_balance_top_up as rp', function($join) use ($startOfMonth, $todayDate) {
                    $join->on('lm.email', '=', 'rp.email_client')
                         ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate]);
                })
                ->leftJoin('target_canvaser as tc', function($join) use ($currentMonth) {
                    $join->on('u.id', '=', 'tc.user_id')
                         ->where('tc.bulan', $currentMonth);
                })
                ->whereIn('u.id', $canvaserIds)
                ->groupBy('u.id', 'u.name', 'tc.target')
                ->select(
                    'u.id',
                    'u.name',
                    DB::raw("COUNT(DISTINCT CASE WHEN lm.data_type = 'leads' THEN lb.leads_master_id END) as new_leads"),
                    DB::raw("COUNT(DISTINCT CASE WHEN dt.email IS NOT NULL THEN dt.email END) as new_akun"),
                    DB::raw("COUNT(DISTINCT CASE WHEN lm.data_type = 'Eksisting Akun' THEN lb.leads_master_id END) as existing_akun_count"),
                    DB::raw("COUNT(DISTINCT CASE WHEN rp.id IS NOT NULL THEN rp.id END) as top_up_existing_akun_count"),
                    DB::raw("COALESCE(tc.target, 0) as target"),
                    DB::raw("COALESCE(SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))), 0) as acv")
                )
                ->get();

            $result = $chartData->map(function($row) {
                return [
                    'name' => $row->name ?? '-',
                    'new_leads' => (int) $row->new_leads,
                    'new_akun' => (int) $row->new_akun,
                    'existing_akun_count' => (int) $row->existing_akun_count,
                    'top_up_existing_akun_count' => (int) $row->top_up_existing_akun_count,
                    'target' => (float) $row->target,
                    'acv' => (float) $row->acv,
                ];
            })->values()->all();

            return response()->json([
                'canvassers' => $result
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in processRegionalChartData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getRegionalChartDataForPH()
    {
        try {
            // Ambil semua canvasser
            $canvasers = DB::table('users')
                ->where('role', 'PH')
                ->select('id', 'name')
                ->get();

            if ($canvasers->isEmpty()) {
                return response()->json([
                    'canvassers' => []
                ]);
            }

            $today = Carbon::now();
            $todayDate = $today->format('Y-m-d'); // Tanggal hari ini untuk filter transaksi
            $currentMonth = $today->format('Y-m');
            $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endOfMonth = Carbon::now()->endOfMonth();
            // $result = [];


            $regionalMap = [
                'SUMBAGSEL'      => 'Angga Satria Gusti',
                'SUMBAGTENG'     => null,
                'SUMBAGUT'       => 'Abdul Halim',
                'JABAR'          => 'Raden Agie Satria Akbar',
                'JABODETABEK'    => 'Sony Widjaya',
                'JATENG DIY'     => 'Deni Setiawan',
                'JATIM'          => 'Muhammad Arief Syahbana',
                'BALI NUSRA'     => null,
                'KALIMANTAN'     => 'Naqsaybandi',
                'PAPUA MALUKU'   => null,
                'SULAWESI'       => 'Ikrar Dharmawan',
            ];
            $result = [];
            $picAliasMap = [
                'Angga Satria Gusti'        => 'angga_s_gusti@telkomsel.co.id',
                'Abdul Halim'               => 'abdul_halim@telkomsel.co.id',
                'Raden Agie Satria Akbar'   => 'raden_as_akbar@telkomsel.co.id',
                'Sony Widjaya'              => 'sony_widjaya@telkomsel.co.id',
                'Deni Setiawan'             => 'deni_setiawan@telkomsel.co.id',
                'Muhammad Arief Syahbana'   => 'muhammad_a_syahbana@telkomsel.co.id',
                'Naqsaybandi'               => 'naqsyabandi@telkomsel.co.id',
                'Ikrar Dharmawan'           => 'ikrar_dharmawan@telkomsel.co.id',
            ];

            foreach ($regionalMap as $region => $picName) {

                // Ambil user_id dari PIC name
                $userId = null;

                if ($picName) {
                    $userIdByEmail = DB::table('users')
                        ->pluck('id', 'email'); // [email => id]
                    $picEmail = $picAliasMap[$picName] ?? null;
                    $userId   = $picEmail ? ($userIdByEmail[$picEmail] ?? null) : null;
                }

                // 1. New Leads (BERDASARKAN USER_ID)
                $newLeads = 0;

                if ($userId) {

                    $newLeads = DB::table('leads_master as lm')
                        ->where('lm.user_id', $userId)
                        ->where('lm.data_type', 'Leads')
                        ->distinct()
                        ->count('lm.id');
                    // 2. New Akun
                    $newAkun = DB::table('data_registarsi_status_approveorreject as dt')
                        ->join('leads_master as lm', 'dt.email', '=', 'lm.email')
                        ->where('lm.user_id', $userId)
                        ->whereBetween(
                            DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')"),
                            [$startOfMonth, $endOfMonth->format('Y-m-d')]
                        )
                        ->distinct()
                        ->count('dt.email');
                }


                // 3. Existing Akun
                $existingAkunCount = DB::table('logbook as lb')
                    ->join('leads_master as lm', 'lb.leads_master_id', '=', 'lm.id')
                    ->where('lm.regional', $region)
                    ->where('lm.data_type', 'Eksisting Akun')
                    ->distinct()
                    ->count('lb.leads_master_id');

                // 4. Top Up Existing Akun Count
                $topUpExistingAkunCount = DB::table('data_registarsi_status_approveorreject as dt')
                    ->join('leads_master as lm', DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(lm.email)'))
                    ->join('report_balance_top_up as rp', function($join) {
                        $join->on(DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(rp.email_client)'))
                             ->whereRaw("DATE(rp.tgl_transaksi) >= STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')");
                    })
                    ->where('lm.regional', $region)
                    ->where('dt.status', 'APPROVE')
                    ->whereRaw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d') < ?", [$startOfMonth])
                    ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
                    ->distinct()
                    ->count(DB::raw('LOWER(dt.email)'));

                // 5. Target (optional jika masih per user → boleh di-skip)
                $targetData = DB::table('region_target')
                    ->where('region_name', strtoupper($region))
                    ->where('date', now()->startOfMonth()->format('Y-m-d'))
                    ->first();

                $target = $targetData->target_amount ?? 0;

                // 6. ACV
                // $topUpNewAkunRp = DB::table('data_registarsi_status_approveorreject as dt')
                //     ->join('leads_master as lm', 'dt.email', '=', 'lm.email')
                //     ->join('report_balance_top_up as rp', 'dt.email', '=', 'rp.email_client')
                //     ->where('lm.regional', $region)
                //     ->whereRaw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d') > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")
                //     ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
                //     ->sum(DB::raw("CAST(rp.total_settlement_klien AS DECIMAL(15,2))"));

                // $topUpExistingAkunRp = DB::table('leads_master as lm')
                //     ->join('report_balance_top_up as rp', 'lm.email', '=', 'rp.email_client')
                //     ->where('lm.regional', $region)
                //     ->where('lm.data_type', 'Eksisting Akun')
                //     ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
                //     ->sum(DB::raw("CAST(rp.total_settlement_klien AS DECIMAL(15,2))"));
                $topupPerRegion = DB::connection('mysql')
                    ->table(DB::raw("(
                        SELECT
                            CASE
                                WHEN data_province_name IN ('Sumatera Selatan','Jambi','Bengkkulu','Lampung','Bangka Belitung','Kepulauan Bangka Belitung') THEN 'SUMBAGSEL'
                                WHEN data_province_name IN ('Sumatera Barat','Riau','Kepulauan Riau') THEN 'SUMBAGTENG'
                                WHEN data_province_name IN ('Sumatera Utara','Aceh') THEN 'SUMBAGUT'
                                WHEN data_province_name IN ('DKI Jakarta','Banten') THEN 'JABODETABEK'
                                WHEN data_province_name = 'Jawa Barat' THEN 'JABAR'
                                WHEN data_province_name IN ('Jawa Tengah','Yogyakarta','DI Yogyakarta') THEN 'JATENG DIY'
                                WHEN data_province_name = 'Jawa Timur' THEN 'JATIM'
                                WHEN data_province_name IN ('Bali','NTB','NTT') THEN 'BALI NUSRA'
                                WHEN data_province_name IN ('Kalimantan Tengah','Kalimantan Barat','Kalimantan Utara','Kalimantan Timur','Kalimantan Selatan') THEN 'KALIMANTAN'
                                WHEN data_province_name IN ('Sulawesi Utara','Sulawesi Tengah','Gorontalo','Sulawesi Tenggara','Sulawesi Selatan','Maluku Utara') THEN 'SULAWESI'
                                WHEN data_province_name IN ('Maluku','Papua Barat','Papua') THEN 'PAPUA MALUKU'
                                ELSE 'UNKNOWN'
                            END AS region,
                            total_settlement_klien,
                            tgl_transaksi
                        FROM report_balance_top_up
                    ) AS x"))
                    ->whereBetween('tgl_transaksi', [$startOfMonth, $todayDate])
                    ->whereRaw('UPPER(region) = ?', [strtoupper(trim($region))])
                    ->sum('total_settlement_klien');

                // $acv = ($topUpNewAkunRp ?? 0) + ($topUpExistingAkunRp ?? 0);
                $acv = ($topupPerRegion ?? 0);
                $result[] = [
                    'region' => $region,
                    'pic' => $picName ?? '-',
                    'new_leads' => $newLeads,
                    'new_akun' => $newAkun,
                    'existing_akun_count' => $existingAkunCount,
                    'top_up_existing_akun_count' => $topUpExistingAkunCount,
                    'target' => $target,
                    'acv' => $acv,
                ];
                // foreach ($canvasers as $canvaser) {
                //     // 1. New Leads (prospect) - dari table logbook
                //     $newLeads = DB::table('logbook as lb')
                //         ->join('leads_master as lm', 'lb.leads_master_id', '=', 'lm.id')
                //         ->where('lm.user_id', $canvaser->id)
                //         ->where('lm.data_type', 'leads')
                //         ->distinct()
                //         ->count('lb.leads_master_id');

                //     // 2. New Akun (deal) - dari data_registarsi yang disetujui dalam 1 bulan terakhir
                //     $newAkun = DB::table('data_registarsi_status_approveorreject as dt')
                //         ->join('leads_master as lm', 'dt.email', '=', 'lm.email')
                //         ->where('lm.user_id', $canvaser->id)
                //         ->whereRaw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d') > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")
                //         ->distinct()
                //         ->count('dt.email');

                //     // 3. Existing Akun Count (prospect) - dari table logbook
                //     $existingAkunCount = DB::table('logbook as lb')
                //         ->join('leads_master as lm', 'lb.leads_master_id', '=', 'lm.id')
                //         ->where('lm.user_id', $canvaser->id)
                //         ->where('lm.data_type', 'Eksisting Akun')
                //         ->distinct()
                //         ->count('lb.leads_master_id');

                //     // 4. Top Up Existing Akun Count (deal) - jumlah AKUN existing yang melakukan topup (DISTINCT)
                //     $topUpExistingAkunCount = DB::table('leads_master as lm')
                //         ->join('report_balance_top_up as rp', 'lm.email', '=', 'rp.email_client')
                //         ->where('lm.user_id', $canvaser->id)
                //         ->where('lm.data_type', 'Eksisting Akun')
                //         ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
                //         ->distinct()
                //         ->count('lm.email');

                //     // 5. Target dari target_canvaser
                //     $targetData = DB::table('target_canvaser')
                //         ->where('user_id', $canvaser->id)
                //         ->where('bulan', $currentMonth)
                //         ->first();

                //     $target = $targetData->target ?? 0;

                //     // 6. ACV (Actual Achievement Value) - total topup dalam rupiah (new + existing) - filter bulan berjalan
                //     $topUpNewAkunRp = DB::table('data_registarsi_status_approveorreject as dt')
                //         ->join('leads_master as lm', 'dt.email', '=', 'lm.email')
                //         ->join('report_balance_top_up as rp', 'dt.email', '=', 'rp.email_client')
                //         ->where('lm.user_id', $canvaser->id)
                //         ->whereRaw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d') > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)")
                //         ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
                //         ->sum(DB::raw("CAST(rp.total_settlement_klien AS DECIMAL(15,2))"));

                //     $topUpExistingAkunRp = DB::table('leads_master as lm')
                //         ->join('report_balance_top_up as rp', 'lm.email', '=', 'rp.email_client')
                //         ->where('lm.user_id', $canvaser->id)
                //         ->where('lm.data_type', 'Eksisting Akun')
                //         ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
                //         ->sum(DB::raw("CAST(rp.total_settlement_klien AS DECIMAL(15,2))"));

                //     $acv = ($topUpNewAkunRp ?? 0) + ($topUpExistingAkunRp ?? 0);

                //     $result[] = [
                //         'name' => $canvaser->name,
                //         'new_leads' => $newLeads,
                //         'new_akun' => $newAkun,
                //         'existing_akun_count' => $existingAkunCount,
                //         'top_up_existing_akun_count' => $topUpExistingAkunCount,
                //         'target' => $target,
                //         'acv' => $acv,
                //     ];
            }

            return response()->json([
                'canvassers' => $result
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in getRegionalChartData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDailyTopupByProvinceDataTable(Request $request)
    {
        try {
            $monthFilter = $request->get('month');
            $search = $request->input('search.value');

            // Tentukan range tanggal berdasarkan filter bulan
            if ($monthFilter) {
                $startDate = Carbon::createFromFormat('Y-m-d', $monthFilter)->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::createFromFormat('Y-m-d', $monthFilter)->endOfMonth()->format('Y-m-d');
            } else {
                $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            }

            // Base query untuk DataTables
            $baseQuery = DB::table('report_balance_top_up as rp')
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
                ->whereNotNull('rp.email_client')
                ->whereNotNull('rp.total_settlement_klien')
                ->where('rp.payment_method_name', '!=', 'Voucher Bonus')
                ->groupBy('rp.data_province_name', 'rp.user_id', 'rp.email_client')
                ->orderBy('total_settlement_klien', 'desc');

            // Apply search filter
            if ($search) {
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('rp.data_province_name', 'like', "%$search%")
                      ->orWhere('rp.email_client', 'like', "%$search%")
                      ->orWhere('rp.user_id', 'like', "%$search%");
                });
            }

            // Hitung TOTAL keseluruhan data (SEBELUM pagination)
            $allData = (clone $baseQuery)->get();
            $uniqueProvinces = $allData->unique('data_province_name')->count();
            $uniqueUserIds = $allData->unique('user_id')->count();
            $uniqueEmails = $allData->unique('email_client')->count();
            $totalSettlement = $allData->sum('total_settlement_klien');

            $totals = [
                'total_provinces' => $uniqueProvinces,
                'total_user_ids' => $uniqueUserIds,
                'total_emails' => $uniqueEmails,
                'total_settlement' => $totalSettlement,
                'total_settlement_format' => 'Rp ' . number_format($totalSettlement, 0, ',', '.')
            ];

            return datatables()->of($baseQuery)
                ->addColumn('tanggal_format', function ($row) {
                    // Tampilkan bulan saja karena ini sudah di-aggregate per bulan
                    return \Carbon\Carbon::parse($row->tgl_transaksi)->translatedFormat('F Y');
                })
                ->addColumn('total_settlement_format', function ($row) {
                    return 'Rp ' . number_format($row->total_settlement_klien, 0, ',', '.');
                })
                ->rawColumns(['total_settlement_format'])
                ->with('totals', $totals)
                ->make(true);
        } catch (\Exception $e) {
            \Log::error("Error in getDailyTopupByProvinceDataTable: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportDailyTopupByProvince(Request $request)
    {
        try {
            $monthFilter = $request->get('month');

            // Tentukan range tanggal berdasarkan filter bulan
            if ($monthFilter) {
                $startDate = Carbon::createFromFormat('Y-m-d', $monthFilter)->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::createFromFormat('Y-m-d', $monthFilter)->endOfMonth()->format('Y-m-d');
                $displayMonth = Carbon::createFromFormat('Y-m-d', $monthFilter)->translatedFormat('F Y');
            } else {
                $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                $displayMonth = Carbon::now()->translatedFormat('F Y');
            }

            // Query raw data dari report_balance_top_up TANPA GROUP BY
            $data = DB::table('report_balance_top_up as rp')
                ->select(
                    'rp.tgl_transaksi',
                    'rp.data_province_name',
                    'rp.user_id',
                    'rp.email_client',
                    'rp.total_settlement_klien'
                )
                ->whereDate('rp.tgl_transaksi', '>=', $startDate)
                ->whereDate('rp.tgl_transaksi', '<=', $endDate)
                ->whereNotNull('rp.email_client')
                ->whereNotNull('rp.total_settlement_klien')
                ->orderBy('rp.tgl_transaksi', 'desc')
                ->orderBy('rp.data_province_name', 'asc')
                ->orderBy('rp.total_settlement_klien', 'desc')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk diekspor');
            }

            // Prepare export data
            $exportData = [];
            foreach ($data as $row) {
                // Ensure settlement is properly formatted as number with proper decimal places
                $settlement = floatval($row->total_settlement_klien);
                $formattedSettlement = number_format($settlement, 0, ',', '.');
                
                $exportData[] = [
                    'Tanggal' => date('d-m-Y', strtotime($row->tgl_transaksi)),
                    'Provinsi' => $row->data_province_name,
                    'User ID' => $row->user_id,
                    'Email' => $row->email_client,
                    'Total Settlement' => ' ' . $formattedSettlement,
                ];
            }

            // Create Excel file
            $fileName = 'Daily_TopUp_Per_Province_' . $displayMonth . '_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

            return response()->streamDownload(function () use ($exportData) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // Set header
                $headers = ['Tanggal', 'Provinsi', 'User ID', 'Email', 'Total Settlement'];
                $sheet->fromArray($headers, null, 'A1');

                // Style header
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E74A3B']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ];
                $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

                // Add data
                $row = 2;
                foreach ($exportData as $item) {
                    $sheet->fromArray((array)$item, null, 'A' . $row);
                    $row++;
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(20);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error("Error in exportDailyTopupByProvince: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }
}
