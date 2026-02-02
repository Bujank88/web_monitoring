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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
                'logbook_daily.id as logbook_id',
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
                'logbook_daily.realisasi_topup',
                'logbook_daily.realisasi_photo',
                'logbook_daily.realisasi_discus',
                'logbook_daily.realisasi_method',
            ])
            ->distinct()
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

        if ($request->start_date) {
            $query->whereBetween('logbook_daily.created_at', [
                $request->start_date . ' 00:00:00',
                $request->start_date   . ' 23:59:59',
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
            ->addColumn('followup', function ($row) {
                if (!empty($row->realisasi_photo)) {
                    return '
                        <span class="badge badge-success">
                            <i class="fas fa-check-circle"></i> Done
                        </span>';
                }
                else{
                    return '
                        <span class="badge badge-danger"> 
                            <i class="fas fa-minus-circle"></i> Not Yet
                        </span>';
                }
            })
            ->addColumn('action', function ($row) {
            // SUDAH REALISASI
            if (!empty($row->realisasi_photo)) {
                return '
                    <button 
                        class="btn btn-sm btn-info btn-view-realisasi"
                        data-photo="'.$row->realisasi_photo.'"
                        data-method="'.$row->realisasi_method.'"
                        data-discus="'.$row->realisasi_discus.'"
                    >
                        Lihat
                    </button>
                ';
            }

            // BELUM REALISASI
            return '
                <button 
                    class="btn btn-sm btn-success btn-realisasi"
                    data-id="'.$row->logbook_id.'"
                >
                    Realisasi Logbook
                </button>
            ';
        })
                
            //     <button 
            //         class="btn btn-sm btn-warning btn-day"
            //         data-id="'.$row->id.'"
            //     >
            //         Day
            //     </button>';
            // })
            ->rawColumns(['action', 'followup'])
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
            if ($request->start_date) {
                $query->whereBetween('logbook_daily.created_at', [
                    $request->start_date . ' 00:00:00',
                    $request->start_date . ' 23:59:59'
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
        public function realisasiLogbook(Request $request)
        {
            $request->validate([
                'id'          => 'required|exists:logbook_daily,id',
                'metode'      => 'required|in:offline,online',
                'pembahasan'  => 'required|string',
                'selfie'      => 'required|string', // base64
            ]);

            // === GET LOGBOOK DATA UNTUK USER_ID ===
            $logbook = DB::table('logbook_daily')
                ->join('leads_master', 'leads_master.id', '=', 'logbook_daily.leads_master_id')
                ->select('leads_master.user_id')
                ->where('logbook_daily.id', $request->id)
                ->first();

            if (!$logbook) {
                return redirect()->back()->with('error', 'Logbook tidak ditemukan');
            }

            // === HANDLE SELFIE BASE64 ===
            $image = $request->selfie;

            // remove base64 header (jpeg/png)
            $image = preg_replace('#^data:image/\w+;base64,#i', '', $image);
            $image = str_replace(' ', '+', $image);

            // folder per bulan
            $monthFolder = Carbon::now()->format('Y-m');
            $storagePath = "selfie-logbook/{$monthFolder}";

            // nama file: user_id_selfie_MM_HH:mm
            $now = Carbon::now();
            $imageName = $logbook->user_id . '_selfie_' . $now->format('d_H:i') . '.jpg';
            
            // simpan file ke storage
            Storage::disk('public')->put(
                $storagePath . '/' . $imageName,
                base64_decode($image)
            );

            // path untuk disimpan ke DB
            $dbPath = $storagePath . '/' . $imageName;

            // === UPDATE LOGBOOK ===
            DB::table('logbook_daily')
                ->where('id', $request->id)
                ->update([
                    'realisasi_method' => $request->metode,
                    'realisasi_discus' => $request->pembahasan,
                    'realisasi_photo'  => $dbPath,
                    'realisasi_at'     => Carbon::now(),
                    'updated_at'       => Carbon::now(),
                ]);

            // === SEND WA NOTIFICATION ===
            $this->sendLogbookNotification($request->id);

            return redirect()->back()->with('success', 'Realisasi logbook berhasil disimpan');
        }

    /**
     * Function: Ambil pending notifications untuk logbook yang belum terkirim
     */
    public function getPendingLogbookNotifications($logbookDailyId)
    {
        $logbook = DB::table('logbook_daily')
            ->where('id', $logbookDailyId)
            ->first();
        
        if (!$logbook) {
            return [];
        }

        $pending = [];

        // Check apakah ada realisasi dan belum terkirim notifikasi
        if (!empty($logbook->realisasi_photo) && ($logbook->is_sent_logbook === false || $logbook->is_sent_logbook === null)) {
            $pending[] = 'realisasi';
        }

        return $pending;
    }

    /**
     * Function: Send logbook notification via WhatsApp
     */
    public function sendLogbookNotification($logbookDailyId)
    {
        try {
            $logbook = DB::table('logbook_daily')
                ->join('leads_master', 'leads_master.id', '=', 'logbook_daily.leads_master_id')
                ->join('users', 'users.id', '=', 'leads_master.user_id')
                ->select([
                    'logbook_daily.id',
                    'logbook_daily.komitmen',
                    'logbook_daily.plan_min_topup',
                    'logbook_daily.status',
                    'logbook_daily.realisasi_method',
                    'logbook_daily.realisasi_discus',
                    'logbook_daily.realisasi_photo',
                    'logbook_daily.created_at',
                    'users.name as nama_canvasser',
                    'users.nohp',
                ])
                ->where('logbook_daily.id', $logbookDailyId)
                ->first();

            if (!$logbook) {
                \Log::error("Logbook daily ID {$logbookDailyId} not found");
                return false;
            }

            $pending = $this->getPendingLogbookNotifications($logbookDailyId);

            if (empty($pending)) {
                return true; // Tidak ada yang perlu dikirim
            }

            $waBot = env('WA_BOT_URL');
            $botPhone = env('WA_BOT_PHONE');
            $botGroupId = env('WA_BOT_GROUP_ID'); // Group ID untuk "All MyAds canvasser Team" atau sejenis

            if (!$waBot || !$botPhone) {
                \Log::error('WA_BOT_URL or WA_BOT_PHONE not configured');
                return false;
            }

            // Format phone
            $phone = preg_replace('/^0/', '62', $botPhone);
            if (!str_starts_with($phone, '62')) {
                $phone = '62' . $phone;
            }

            foreach ($pending as $action) {
                if ($action === 'realisasi') {
                    $postData = [
                        'phone' => $phone,
                        'nama_canvasser' => $logbook->nama_canvasser,
                        'tanggal' => Carbon::parse($logbook->created_at)->locale('id')->translatedFormat('d F Y'),
                        'komitmen' => $logbook->komitmen,
                        'plan_min_topup' => $logbook->plan_min_topup,
                        'status' => $logbook->status,
                        'metode' => $logbook->realisasi_method,
                        'pembahasan' => $logbook->realisasi_discus,
                    ];

                    // Add foto jika ada
                    if ($logbook->realisasi_photo && Storage::disk('public')->exists($logbook->realisasi_photo)) {
                        $postData['foto_base64'] = base64_encode(Storage::disk('public')->get($logbook->realisasi_photo));
                        $postData['foto_mime'] = Storage::disk('public')->mimeType($logbook->realisasi_photo);
                    }

                    // Add group ID jika ada
                    if ($botGroupId) {
                        $postData['group_id'] = $botGroupId;
                    }

                    $response = Http::timeout(60)->post($waBot . '/api/send-wa-logbook', $postData);

                    if ($response->successful()) {
                        // Update status jadi true
                        DB::table('logbook_daily')
                            ->where('id', $logbookDailyId)
                            ->update([
                                'is_sent_logbook' => true,
                                'last_send_attempt' => now(),
                                'updated_at' => now(),
                            ]);
                        
                        \Log::info("Logbook {$logbookDailyId} notification sent successfully");
                        return true;
                    } else {
                        \Log::warning("Failed to send logbook {$logbookDailyId} notification: " . $response->body());
                        
                        // Update last_send_attempt
                        DB::table('logbook_daily')
                            ->where('id', $logbookDailyId)
                            ->update([
                                'last_send_attempt' => now(),
                            ]);
                        
                        return false;
                    }
                }
            }

            return true;

        } catch (\Exception $e) {
            \Log::error('Send logbook notification error: ' . $e->getMessage());
            
            // Update last_send_attempt
            DB::table('logbook_daily')
                ->where('id', $logbookDailyId)
                ->update([
                    'last_send_attempt' => now(),
                ]);
            
            return false;
        }
    }

    /**
     * Function: Get foto logbook untuk viewing
     * Support both old (public_path) dan new (storage) format
     */
    public function getFoto($filePath)
    {
        // Normalize path - replace URL-encoded slashes with actual slashes
        $filePath = str_replace(['%2F', '%5C'], '/', $filePath);
        
        // Security: check if valid selfie-logbook path
        if (!Str::startsWith($filePath, 'selfie-logbook/')) {
            \Log::warning("Unauthorized foto access attempt: {$filePath}");
            abort(404, 'Foto tidak ditemukan');
        }
        
        // Try storage disk first (new format)
        if (Storage::disk('public')->exists($filePath)) {
            return response()->file(Storage::disk('public')->path($filePath));
        }
        
        // Fallback ke public_path (old format) untuk backward compatibility
        $publicPath = public_path($filePath);
        if (File::exists($publicPath)) {
            return response()->file($publicPath);
        }
        
        \Log::warning("Foto tidak ditemukan di storage maupun public: {$filePath}");
        abort(404, 'Foto tidak ditemukan');
    }
}
