<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadsMaster;
use App\Models\LeadsSource;
use App\Models\LogbookModel;
use App\Models\User;
use App\Models\Sector;
use Illuminate\Validation\Rule; 
use DataTables;
use Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LogbookController extends Controller
{
    /**
     * Show the leads master view
     */
    public function index()
    {
        logUserLogin();
        return view('logbook.index', [
            'canvassers' => User::orderBy('name')->where('role', 'cvsr')->get(),
            'sources'    => LeadsSource::orderBy('name')->get(),
            'regionals'  => DB::table('regional_provinces')
                                ->select('regional')
                                ->distinct()
                                ->orderBy('regional')
                                ->pluck('regional'),
        ]);
    }

    /**
     * Datatable server-side response
     */
    public function data(Request $request)
    {
        $search = $request->input('search.value');
        $month = now()->month;
        $year  = now()->year;
        // =======================
        // BASE QUERY + JOIN LOGBOOK
        // =======================
        $query = LeadsMaster::query()
            ->leftJoin('users', 'users.id', '=', 'leads_master.user_id')
            ->join('logbook', 'logbook.leads_master_id', '=', 'leads_master.id')
            ->leftJoin('report_balance_top_up', function ($join) use ($month, $year) {
                    $join->whereRaw(
                        'LOWER(report_balance_top_up.email_client) = LOWER(leads_master.email)'
                    )
                    ->whereMonth('report_balance_top_up.tgl_transaksi', $month)
                    ->whereYear('report_balance_top_up.tgl_transaksi', $year);
                })
            ->select([
                'leads_master.id',
                'users.name as user_name',
                'leads_master.regional',
                'leads_master.company_name',
                'leads_master.myads_account',
                'leads_master.mobile_phone',
                'leads_master.data_type',
                'leads_master.flag_event',
                'logbook.created_at',
                'logbook.komitmen',
                'logbook.plan_min_topup',
                'logbook.status',
                DB::raw('SUM(report_balance_top_up.total_settlement_klien) as total_settlement_klien'),
            ])
            ->groupBy(
                'leads_master.id',
                'users.name',
                'leads_master.regional',
                'leads_master.company_name',
                'leads_master.myads_account',
                'leads_master.mobile_phone',
                'leads_master.data_type',
                'leads_master.flag_event',
                'logbook.created_at',
                'logbook.komitmen',
                'logbook.plan_min_topup',
                'logbook.status'
            )
        ->orderBy('leads_master.created_at', 'desc');

        // =======================
        // 🔐 FILTER ROLE
        // =======================
        if (!auth()->user()->hasRole('Admin')) {
            $query->where('leads_master.user_id', auth()->id());
        }

        // =======================
        // 🔍 FILTER DATATABLE
        // =======================
        if ($request->regional) {
            $query->where('leads_master.regional', $request->regional);
        }

        if ($request->canvasser) {
            $query->where('leads_master.user_id', $request->canvasser);
        }        

        if ($request->month) {
            
            $date = Carbon::createFromFormat('Y-m', $request->month);
            
            $start = $date->copy()->startOfMonth()->toDateTimeString(); // Contoh: 2023-10-01 00:00:00
            $end = $date->copy()->endOfMonth()->toDateTimeString();     // Contoh: 2023-10-31 23:59:59

            $query->whereBetween('logbook.created_at', [$start, $end]);
        }
        

        // =======================
        // DATATABLE RESPONSE
        // =======================
        return datatables()->of($query)
            ->filter(function ($query) use ($request) {
                    // Ambil value search dari input bawaan datatables
                    $search = $request->input('search.value');
                    
                    if (!empty($search)) {
                        $query->where(function($q) use ($search) {
                            $searchTerm = "%" . strtolower($search) . "%";
                            $q->whereRaw("LOWER(users.name) LIKE ?", [$searchTerm])
                            ->orWhereRaw("LOWER(leads_master.regional) LIKE ?", [$searchTerm])
                            ->orWhereRaw("LOWER(leads_master.company_name) LIKE ?", [$searchTerm])
                            ->orWhereRaw("LOWER(leads_master.myads_account) LIKE ?", [$searchTerm])
                            ->orWhereRaw("LOWER(leads_master.mobile_phone) LIKE ?", [$searchTerm]);
                        });
                    }
                })
            ->addColumn('user_name', function ($row) {
                return $row->user_name ?? '-';
            })

            ->addColumn('regional', function ($row) {
                return $row->regional ?? '-';
            })

            ->addColumn('company_name', function ($row) {
                return $row->company_name ?? '-';
            })

            ->addColumn('myads_account', function ($row) {
                return $row->myads_account ?? '-';
            })

            ->addColumn('mobile_phone', function ($row) {
                return $row->mobile_phone ?? '-';
            })

            ->addColumn('data_type', function ($row) {
                return $row->data_type ?? '-';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('Y-m-d');
            })

            ->addColumn('komitmen', function ($row) {
                return $row->komitmen ?? '-';
            })

            ->addColumn('plan_min_topup', function ($row) {
                return $row->plan_min_topup
                    ? number_format($row->plan_min_topup, 0, ',', '.')
                    : '-';
            })
             ->addColumn('total_settlement_klien', function ($row) {
                return $row->total_settlement_klien
                    ? number_format($row->total_settlement_klien, 0, ',', '.')
                    : '-';
            })
             ->addColumn('status', function ($row) {
                return $row->status ?? '-';
            })
             ->addColumn('action', function ($row) {
                return '
                <button 
                    class="btn btn-sm btn-primary btn-edit"
                    data-id="'.$row->id.'"
                    data-komitmen="'.$row->komitmen.'"
                    data-plan="'.$row->plan_min_topup.'"
                    data-status="'.$row->status.'"
                >
                    Edit
                </button>
                
                <button 
                    class="btn btn-sm btn-warning btn-day"
                    data-id="'.$row->id.'"
                >
                    Day
                </button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }
    public function update(Request $request)
    {
        
        $logbook = DB::table('logbook')
            ->where('leads_master_id', $request->id)
            ->update([
                'komitmen' => $request->komitmen,
                'plan_min_topup' => $request->plan_min_topup,
                'status' => $request->status,
                'updated_at' => now(),
            ]);

        DB::table('logbook_daily')
            ->where('leads_master_id', $request->id)
            ->whereDate('created_at', now()->toDateString())
            ->update([
                'komitmen'        => $request->komitmen,
                'plan_min_topup'  => $request->plan_min_topup,
                'status'          => $request->status,
                'updated_at'      => now(),
            ]);

        return response()->json(['success' => true]);
    }
    
    public function insert(Request $request)
    {
        // dd($request->all());
        $exists = DB::table('logbook')
            ->where('leads_master_id', $request->id)
            ->where('bulan', now()->month)   // atau angka bulan (1–12)
            ->where('tahun', now()->year)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Logbook sudah ada!');
        }
   
        DB::table('logbook')->insert([
            'leads_master_id' => $request->id,
            'komitmen'        => $request->komitmen,
            'plan_min_topup'  => $request->plan_min_topup ?? 100000,
            'status'          => $request->status,
            'bulan'           => now()->month,
            'tahun'           => now()->year,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    
        return redirect()->back()->with('success', 'Data berhasil disimpan!');
    }
    public function insertDaily(Request $request)
    {
        // Ambil data logbook berdasarkan leads_master_id
        $logbook = DB::table('logbook')
            ->where('leads_master_id', $request->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$logbook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data logbook tidak ditemukan'
            ], 404);
        }

        // Cegah double input daily di hari yang sama
        $exists = DB::table('logbook_daily')
            ->where('leads_master_id', $request->id)
            ->whereDate('created_at', now())
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun sudah terdaftar di logbook hari ini'
            ], 422);
        }

        // ❌ Cegah prospect lebih dari 5x dalam bulan berjalan
        $countThisMonth = DB::table('logbook_daily')
            ->where('leads_master_id', $request->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        if ($countThisMonth >= 5) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun ini sudah di prospect sebanyak 5x dalam bulan ini silahkan cari akun lain untuk dimasukan ke logbook'
            ], 422);
        }

        // Insert ke logbook_daily
        DB::table('logbook_daily')->insert([
            'leads_master_id' => $request->id,
            'komitmen'        => $logbook->komitmen,
            'plan_min_topup'  => $logbook->plan_min_topup,
            'status'          => $logbook->status,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Logbook daily berhasil ditambahkan'
        ]);
    }


    public function refreshLogbookStatus()
    {
        // Get leads where sum of report_balance_top_up.total_settlement_klien > 0
        $leads = DB::table('leads_master')
            ->join('logbook', 'logbook.leads_master_id', '=', 'leads_master.id')
            ->leftJoin('report_balance_top_up', function($join) {
                $join->whereRaw('LOWER(report_balance_top_up.email_client) = LOWER(leads_master.email)')
                ->whereRaw('MONTH(report_balance_top_up.tgl_transaksi) = logbook.bulan')
                 ->whereRaw('YEAR(report_balance_top_up.tgl_transaksi) = logbook.tahun');
            })
            ->select(
                'leads_master.id',
                'logbook.id as logbook_id',
                DB::raw('SUM(report_balance_top_up.total_settlement_klien) as total_settlement')
            )
            ->groupBy('leads_master.id', 'logbook.id')
            ->havingRaw('total_settlement > 0')
            ->get();

        foreach ($leads as $lead) {
            // Update the logbook status
            $logbook = DB::table('logbook')
                ->where('id', $lead->logbook_id)
                ->update([
                    'status' => 'Topup', // or any status you want
                    'updated_at' => now(),
                ]);
            print($logbook);
        }
    }

    /**
     * Show the leads master view
     */
    public function logbookEvent()
    {
        logUserLogin();
        return view('logbook.logbook-event', [
            'canvassers' => User::orderBy('name')->where('role', 'cvsr')->get(),
            'sources'    => LeadsSource::orderBy('name')->get(),
            'regionals'  => DB::table('regional_provinces')
                                ->select('regional')
                                ->distinct()
                                ->orderBy('regional')
                                ->pluck('regional'),
        ]);
    }

    /**
     * Datatable server-side response
     */
    public function logbookEventData(Request $request)
    {
        $search = $request->input('search.value');
        $selectedMonth = $request->month
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();
        $month = (int) $selectedMonth->month;
        $year  = (int) $selectedMonth->year;
        // =======================
        // BASE QUERY + JOIN LOGBOOK
        // =======================
        $query = LeadsMaster::query()
            ->leftJoin('users', 'users.id', '=', 'leads_master.user_id')
            ->join('logbook', 'logbook.leads_master_id', '=', 'leads_master.id')
            ->leftJoin('report_balance_top_up', function ($join) use ($month, $year) {
                    $join->whereRaw(
                        'LOWER(report_balance_top_up.email_client) = LOWER(leads_master.email)'
                    )
                    ->whereMonth('report_balance_top_up.tgl_transaksi', $month)
                    ->whereYear('report_balance_top_up.tgl_transaksi', $year);
                })
            ->where(function ($q) {
                $q->whereRaw("LOWER(leads_master.flag_event) LIKE ?", ['%leads ramadhan 2026%'])
                ->orWhereRaw("LOWER(leads_master.flag_event) LIKE ?", ['%existing ramadhan 2026%']);
            })
            ->select([
                'leads_master.id',
                'users.name as user_name',
                'leads_master.regional',
                'leads_master.company_name',
                'leads_master.myads_account',
                'leads_master.mobile_phone',
                'leads_master.data_type',
                'logbook.created_at',
                'logbook.komitmen',
                'logbook.plan_min_topup',
                'logbook.status',
                'leads_master.flag_event',
                DB::raw('SUM(report_balance_top_up.total_settlement_klien) as total_settlement_klien'),
            ])
            ->groupBy(
                'leads_master.id',
                'users.name',
                'leads_master.regional',
                'leads_master.company_name',
                'leads_master.myads_account',
                'leads_master.mobile_phone',
                'leads_master.data_type',
                'logbook.created_at',
                'logbook.komitmen',
                'logbook.plan_min_topup',
                'logbook.status',
                'leads_master.flag_event'
            )
        ->orderBy('report_balance_top_up.total_settlement_klien', 'asc');

        // =======================
        // 🔐 FILTER ROLE
        // =======================
        if (!auth()->user()->hasRole('Admin')) {
            $query->where('leads_master.user_id', auth()->id());
        }

        // =======================
        // 🔍 FILTER DATATABLE
        // =======================
        if ($request->regional) {
            $query->where('leads_master.regional', $request->regional);
        }

        if ($request->canvasser) {
            $query->where('leads_master.user_id', $request->canvasser);
        }        

        if ($request->month) {
            $start = $selectedMonth->copy()->startOfMonth()->toDateTimeString();
            $end = $selectedMonth->copy()->endOfMonth()->toDateTimeString();
            $query->whereBetween('logbook.created_at', [$start, $end]);
        }
        

        $summaryRows = (clone $query)->get();

        $summary = [
            'summary_1' => [
                'existing_count' => 0,
                'existing_realisasi_count' => 0,
                'leads_count' => 0,
                'leads_to_eksisting_count' => 0,
            ],
            'summary_2' => [
                'plan' => 0,
                'realisasi' => 0,
                'gap' => 0,
            ],
            'summary_3' => [
                'plan' => 0,
                'realisasi' => 0,
                'gap' => 0,
            ],
        ];

        foreach ($summaryRows as $row) {
            $flagEvent = strtolower(trim((string) ($row->flag_event ?? '')));
            $dataType = strtolower(trim((string) ($row->data_type ?? '')));
            $plan = (float) ($row->plan_min_topup ?? 0);
            $realisasi = (float) ($row->total_settlement_klien ?? 0);

            $isLeadsRamadhan = str_contains($flagEvent, 'leads ramadhan 2026');
            $isExistingRamadhan = str_contains($flagEvent, 'existing ramadhan 2026');
            $isEksistingAkun = ($dataType === 'eksisting akun');

            if ($isExistingRamadhan) {
                $summary['summary_1']['existing_count']++;
                $summary['summary_3']['plan'] += $plan;
                $summary['summary_3']['realisasi'] += $realisasi;

                if ($realisasi > 0) {
                    $summary['summary_1']['existing_realisasi_count']++;
                }
            }

            if ($isLeadsRamadhan) {
                $summary['summary_1']['leads_count']++;
                $summary['summary_2']['plan'] += $plan;
                $summary['summary_2']['realisasi'] += $realisasi;

                if ($isEksistingAkun) {
                    $summary['summary_1']['leads_to_eksisting_count']++;
                }
            }
        }

        $summary['summary_2']['gap'] =
            $summary['summary_2']['realisasi'] - $summary['summary_2']['plan'];
        $summary['summary_3']['gap'] =
            $summary['summary_3']['realisasi'] - $summary['summary_3']['plan'];

        // =======================
        // DATATABLE RESPONSE
        // =======================
        return datatables()->of($query)
            ->with('summary', $summary)
            ->filter(function ($query) use ($request) {
                    // Ambil value search dari input bawaan datatables
                    $search = $request->input('search.value');
                    
                    if (!empty($search)) {
                        $query->where(function($q) use ($search) {
                            $searchTerm = "%" . strtolower($search) . "%";
                            $q->whereRaw("LOWER(users.name) LIKE ?", [$searchTerm])
                            ->orWhereRaw("LOWER(leads_master.regional) LIKE ?", [$searchTerm])
                            ->orWhereRaw("LOWER(leads_master.company_name) LIKE ?", [$searchTerm])
                            ->orWhereRaw("LOWER(leads_master.myads_account) LIKE ?", [$searchTerm])
                            ->orWhereRaw("LOWER(leads_master.mobile_phone) LIKE ?", [$searchTerm]);
                        });
                    }
                })
            ->addColumn('user_name', function ($row) {
                return $row->user_name ?? '-';
            })

            ->addColumn('regional', function ($row) {
                return $row->regional ?? '-';
            })

            ->addColumn('company_name', function ($row) {
                return $row->company_name ?? '-';
            })

            ->addColumn('myads_account', function ($row) {
                return $row->myads_account ?? '-';
            })

            ->addColumn('mobile_phone', function ($row) {
                return $row->mobile_phone ?? '-';
            })

            ->addColumn('data_type', function ($row) {
                return $row->data_type ?? '-';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('Y-m-d');
            })

            ->addColumn('komitmen', function ($row) {
                return $row->komitmen ?? '-';
            })

            ->addColumn('plan_min_topup', function ($row) {
                return $row->plan_min_topup
                    ? number_format($row->plan_min_topup, 0, ',', '.')
                    : '-';
            })
             ->addColumn('total_settlement_klien', function ($row) {
                return $row->total_settlement_klien
                    ? number_format($row->total_settlement_klien, 0, ',', '.')
                    : '-';
            })
             ->addColumn('status', function ($row) {
                return $row->status ?? '-';
            }) 
            ->make(true);
    }
}
