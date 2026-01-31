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

class LogbookDailyController extends Controller
{
    public function index()
    {
        logUserLogin();
        return view('logbook.daily', [
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
        // =======================
        // BASE QUERY + JOIN LOGBOOK
        // =======================
        $query = LeadsMaster::query()
            ->leftJoin('users', 'users.id', '=', 'leads_master.user_id')
            ->join('logbook_daily', 'logbook_daily.leads_master_id', '=', 'leads_master.id')
            ->leftJoin('report_balance_top_up', function ($join) {
                $join->whereRaw('LOWER(report_balance_top_up.email_client) = LOWER(leads_master.email)');
            })
            ->leftJoin('manual_upload_topup', function ($join) {
                $join->whereRaw('LOWER(manual_upload_topup.email) = LOWER(leads_master.email)');
            })
            ->select([
                'leads_master.id',
                'users.name as user_name',
                'leads_master.regional',
                'leads_master.company_name',
                'leads_master.myads_account',
                'leads_master.mobile_phone',
                'leads_master.data_type',
                'logbook_daily.created_at',
                'logbook_daily.komitmen',
                'logbook_daily.plan_min_topup',
                'logbook_daily.status',
                'logbook_daily.realisasi_topup'
            ])
            ->distinct()
            // ->orderBy('leads_master.created_at', 'desc')
            ->orderBy('logbook_daily.realisasi_topup', 'desc');


        // =======================
        // 🔐 FILTER ROLE
        // =======================
        if (!auth()->user()->hasRole('Admin')) {
            $query->where('leads_master.user_id', auth()->id());
        }

        // =======================
        // 🔍 FILTER DATATABLE
        // =======================
        // if ($request->regional) {
        //     $query->where('leads_master.regional', $request->regional);
        // }

        if ($request->canvasser) {
            $query->where('leads_master.user_id', $request->canvasser);
        }

        // if ($request->company) {
        //     $query->where('leads_master.company_name', 'like', '%' . $request->company . '%');
        // }

        // if ($request->email) {
        //     $query->where('leads_master.email', 'like', '%' . $request->email . '%');
        // }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('logbook_daily.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }


        // if ($request->source) {
        //     $query->whereHas('source', function ($q) use ($request) {
        //         $q->where('id', $request->source);
        //     });
        // }

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
                return \Carbon\Carbon::parse($row->created_at)->format('Y-m-d');
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
            ->addColumn('realisasi_topup', function ($row) {
                return $row->realisasi_topup
                    ? number_format($row->realisasi_topup, 0, ',', '.')
                    : '-';
            })
             ->addColumn('action', function ($row) {
                return '
                <button 
                    class="btn btn-sm btn-success btn-realisasi"
                    data-id="'.$row->id.'"
                >
                    Realisasi Logbook
                </button>';
            })
                
            //     <button 
            //         class="btn btn-sm btn-warning btn-day"
            //         data-id="'.$row->id.'"
            //     >
            //         Day
            //     </button>';
            // })
            ->rawColumns(['action'])
            ->make(true);
        }
        public function refreshLogbookDaily()
        {
            $today = Carbon::today()->toDateString(); // YYYY-MM-DD
            $month = now()->month;
            $year  = now()->year;

            // --------------------------------
            // 1️⃣ Hari ini → pakai manual topup saja
            // --------------------------------
            $topupsToday = DB::table('leads_master')
                ->leftJoin('manual_upload_topup', function ($join) use ($month, $year, $today) {
                    $join->whereRaw('LOWER(manual_upload_topup.email) = LOWER(leads_master.email)')
                        ->whereMonth('manual_upload_topup.tanggal', $month)
                        ->whereYear('manual_upload_topup.tanggal', $year)
                        ->whereDate('manual_upload_topup.tanggal', $today); // Hanya hari ini
                })
                ->select(
                    'leads_master.id as leads_master_id',
                    DB::raw('COALESCE(SUM(manual_upload_topup.total), 0) AS realisasi_topup')
                )
                ->groupBy('leads_master.id')
                ->having('realisasi_topup', '>', 0)
                ->get();

            // update logbook hari ini
            foreach ($topupsToday as $data) {
                DB::table('logbook_daily')
                    ->where('leads_master_id', $data->leads_master_id)
                    ->whereDate('created_at', $today)
                    ->update([
                        'status'          => 'Topup',
                        'realisasi_topup' => $data->realisasi_topup,
                        'updated_at'      => now(),
                    ]);
                print($data->leads_master_id . '    ');
            }

            // --------------------------------
            // 2️⃣ Hari sebelumnya → pakai report_balance_top_up saja
            // --------------------------------
            $topupsPast = DB::table('leads_master')
                ->leftJoin('report_balance_top_up', function ($join) use ($month, $year, $today) {
                    $join->whereRaw('LOWER(report_balance_top_up.email_client) = LOWER(leads_master.email)')
                        ->whereMonth('report_balance_top_up.tgl_transaksi', $month)
                        ->whereYear('report_balance_top_up.tgl_transaksi', $year)
                        ->whereDate('report_balance_top_up.tgl_transaksi', '<', $today); // hanya sebelum hari ini
                })
                ->select(
                    'leads_master.id as leads_master_id',
                    DB::raw('COALESCE(SUM(report_balance_top_up.total_settlement_klien), 0) AS realisasi_topup')
                )
                ->groupBy('leads_master.id')
                ->having('realisasi_topup', '>', 0)
                ->get();

            // update logbook hari sebelumnya
            foreach ($topupsPast as $data) {
                DB::table('logbook_daily')
                    ->where('leads_master_id', $data->leads_master_id)
                    ->whereDate('created_at', '<', $today)
                    ->update([
                        'status'          => 'Topup',
                        'realisasi_topup' => $data->realisasi_topup,
                        'updated_at'      => now(),
                    ]);
                print($data->leads_master_id . '    ');
            }
        }


        public function summary(Request $request)
        {
            $query = DB::table('logbook_daily')
                ->join('leads_master', 'leads_master.id', '=', 'logbook_daily.leads_master_id')
                ->join('users', 'users.id', '=', 'leads_master.user_id');

            // 🔐 Role filter
            if (auth()->user()->role === 'cvsr') {
                $query->where('users.id', auth()->id());
            } elseif ($request->canvasser && auth()->user()->role == 'Admin') {
                $query->where('users.id', $request->canvasser);
            }

            // 📅 Month filter
            if ($request->start_date && $request->end_date) {
                $query->whereBetween('logbook_daily.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59'
                ]);
            }

            // 🌍 Regional
            if ($request->regional) {
                $query->where('leads_master.regional', $request->regional);
            }

            $data = $query->selectRaw("
                SUM(CASE WHEN logbook_daily.komitmen = 'New Leads' THEN logbook_daily.plan_min_topup ELSE 0 END) AS new_leads,
                SUM(CASE WHEN logbook_daily.komitmen = '100%' THEN logbook_daily.plan_min_topup ELSE 0 END) AS full,
                SUM(CASE WHEN logbook_daily.komitmen = '50%' THEN logbook_daily.plan_min_topup ELSE 0 END) AS half,
                SUM(CASE WHEN logbook_daily.komitmen = '<50%' THEN logbook_daily.plan_min_topup ELSE 0 END) AS less_half,
                SUM(logbook_daily.plan_min_topup) AS total,

                SUM(CASE WHEN logbook_daily.komitmen = 'New Leads' THEN coalesce(logbook_daily.realisasi_topup, 0) ELSE 0 END) AS real_new_leads,
                SUM(CASE WHEN logbook_daily.komitmen = '100%' THEN coalesce(logbook_daily.realisasi_topup, 0) ELSE 0 END) AS real_full,
                SUM(CASE WHEN logbook_daily.komitmen = '50%' THEN coalesce(logbook_daily.realisasi_topup, 0) ELSE 0 END) AS real_half,
                SUM(CASE WHEN logbook_daily.komitmen = '<50%' THEN coalesce(logbook_daily.realisasi_topup, 0) ELSE 0 END) AS real_less_half,
                SUM(coalesce(logbook_daily.realisasi_topup, 0)) AS real_total
            ")->first();

            return response()->json($data);
        }

}
