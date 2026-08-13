<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadsMaster;
use App\Models\LeadsSource;
use App\Models\User;
use App\Models\Sector;
use Illuminate\Validation\Rule; 
use DataTables;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LeadsMasterController extends Controller
{
    /**
     * Show the leads master view
     */
    public function index()
    {
        logUserLogin();
        return view('leads-master.index', [
            'canvassers' => Cache::remember('users_list_leads', 3600, fn() => User::whereIn('role', ['cvsr', 'PH'])->orderBy('name')->get()),
            'sources'    => Cache::remember('sources_list_leads', 3600, fn() => LeadsSource::orderBy('name')->get()),
            'regionals'  => Cache::remember('regionals_list_leads', 3600, fn() => 
                DB::table('regional_provinces')
                    ->select('regional')
                    ->distinct()
                    ->orderBy('regional')
                    ->pluck('regional')
            ),
            'flagEvents' => Cache::remember('flag_events_list_leads', 3600, fn() =>
                DB::table('detail_leads_summary')
                    ->whereNotNull('flag_event')
                    ->where('flag_event', '!=', '')
                    ->distinct()
                    ->orderBy('flag_event')
                    ->pluck('flag_event')
            ),
        ]);
    }

    /**
     * Datatable server-side response
     * Query dari detail_leads_summary (sudah denormalisasi, cepat)
     */
    public function data(Request $request)
    {
        $search = $request->input('search.value');

        // Base query dari summary table (sudah precomputed, lebih cepat)
        $query = DB::table('detail_leads_summary as dls')
            ->select(
                'dls.*',
                DB::raw("
                    CASE
                        WHEN COALESCE(dls.saldo_utama, 0) >= 1000000 THEN 1
                        ELSE 0
                    END as rekomendasi_sort
                ")
            )
            ->orderBy('dls.total_settlement_klien', 'desc')
            ->orderBy('dls.saldo_utama', 'desc');

        // 🔐 Filter berdasarkan role
        if (!auth()->user()->hasRole('Admin')) {
            $query->where('dls.user_id', auth()->id());
        }

        // =======================
        // 🔍 FILTER DARI DATATABLE
        // =======================
        if ($request->regional) {
            $query->where('dls.regional', $request->regional);
        }
        
        // Filter Canvasser
        if ($request->canvasser) {
            $query->where('dls.user_id', $request->canvasser);
        }

        // Filter Flag Event
        if ($request->flag_event) {
            $query->where('dls.flag_event', $request->flag_event);
        }

        // Filter Tanggal
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('dls.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('dls.user_name', 'like', "%$search%")
                ->orWhere('dls.regional', 'like', "%$search%")
                ->orWhere('dls.company_name', 'like', "%$search%")
                ->orWhere('dls.email', 'like', "%$search%")
                ->orWhere('dls.mobile_phone', 'like', "%$search%")
                ->orWhere('dls.flag_event', 'like', "%$search%");
            });
        }

        return datatables()->of($query)
            ->orderColumn('rekomendasi', 'rekomendasi_sort $1')
            ->addColumn('user_name', function ($row) {
                return $row->user_name ?? '-';
            })            
            ->addColumn('regional', function ($row) {
                return $row->regional ?? '-';
            })
            ->addColumn('company_name', function ($row) {
                return $row->company_name ?? '-';
            })
            ->addColumn('email', function ($row) {
                return $row->email ?? '-';
            })
            ->addColumn('mobile_phone', function ($row) {
                return $row->mobile_phone ?? '-';
            })
            ->addColumn('data_type', function ($row) {
                $type = $row->data_type ?? '-';
                if ($type === 'Eksisting Akun') {
                    return '<span class="badge badge-success">' . $type . '</span>';
                } else if ($type === 'Leads') {
                    return '<span class="badge badge-primary">' . $type . '</span>';
                }
                return '<span class="badge badge-secondary">' . $type . '</span>';
            })
            ->addColumn('flag_event', function ($row) {
                return $row->flag_event ?? '-';
            })
            ->editColumn('created_at', function ($row) {
                return \Carbon\Carbon::parse($row->created_at)->translatedFormat('d M Y');
            })
            ->addColumn('total_settlement_klien', function ($row) {
                $amount = $row->total_settlement_klien ?? 0;
                return 'Rp ' . number_format($amount, 0, ',', '.');
            })
            ->addColumn('saldo_utama', function ($row) {
                $amount = $row->saldo_utama ?? 0;
                return 'Rp ' . number_format($amount, 0, ',', '.');
            })
            ->addColumn('aksi', function ($row) {
                $btn = '
                    <a href="' . route('leads-master.show', $row->leads_master_id) . '" class="btn btn-sm btn-warning mt-1">
                        <i class="fas fa-search"></i> Lihat
                    </a>
                    <a href="' . route('leads-master.edit', $row->leads_master_id) . '" class="btn btn-sm btn-primary mt-1">
                        <i class="fas fa-pencil-alt"></i> Edit
                    </a>
                ';

                if (!empty($row->email)) {
                    $btn .= '
                        <a href="' . route('transaction-detail', ['email' => $row->email]) . '" class="btn btn-sm btn-info mt-1">
                            <i class="fas fa-receipt"></i> Transaction Detail
                        </a>
                    ';
                }

                // hanya admin dan canvasser yang boleh add to logbook
                if (auth()->check() && in_array(auth()->user()->role, ['Admin', 'cvsr'])) {
                    $btn .= '
                        <button type="button" 
                                class="btn btn-sm btn-success btn-add-logbook mt-1" 
                                data-id="' . $row->leads_master_id . '">
                            <i class="fas fa-book"></i> Add to Logbook
                        </button>
                    ';
                }

                return $btn;
            })
            ->addColumn('rekomendasi', function ($row) {
                $saldo = $row->saldo_utama ?? 0;

                if ($saldo >= 1000000) {
                    return '<span class="badge badge-warning">Push Campaign</span>';
                }

                return '<span class="badge badge-danger">Push Topup</span>';
            })
            ->rawColumns(['aksi', 'status', 'data_type', 'rekomendasi'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $query = DB::table('detail_leads_summary as dls')
            ->select(
                'dls.user_name',
                'dls.regional',
                'dls.company_name',
                'dls.email',
                'dls.mobile_phone',
                'dls.data_type',
                'dls.flag_event',
                'dls.created_at',
                'dls.total_settlement_klien',
                'dls.saldo_utama'
            )
            ->orderBy('dls.total_settlement_klien', 'desc')
            ->orderBy('dls.saldo_utama', 'desc');


        // 🔐 ROLE
        if (!auth()->user()->hasRole('Admin')) {
            $query->where('dls.user_id', auth()->id());
        }

        // Filter Canvasser
        if ($request->canvasser) {
            $query->where('dls.user_id', $request->canvasser);
        }

        // Filter Regional
        if ($request->regional) {
            $query->where('dls.regional', $request->regional);
        }

        // Filter Flag Event
        if ($request->flag_event) {
            $query->where('dls.flag_event', $request->flag_event);
        }

        // Filter Tanggal
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('dls.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $filename = 'leads_master_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = [
            'Canvasser',
            'Regional',
            'Nama Perusahaan',
            'Email',
            'No HP',
            'Tipe Data',
            'Flag Event',
            'Tanggal',
            'Total Settlement',
            'Saldo Utama',
            'Rekomendasi',
        ];

        $callback = function () use ($query, $columns) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM (biar Excel tidak rusak)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $columns);

            foreach ($query->cursor() as $row) {
                $totalSettlement = $row->total_settlement_klien ?? 0;
                $saldoUtama = $row->saldo_utama ?? 0;
                $rekomendasi = ($saldoUtama >= 1000000) ? 'Push Campaign' : 'Push Topup';

                fputcsv($file, [
                    $row->user_name ?? '-',
                    $row->regional ?? '-',
                    $row->company_name ?? '-',
                    $row->email ?? '-',
                    $row->mobile_phone ?? '-',
                    $row->data_type ?? '-',
                    $row->flag_event ?? '-',
                    \Carbon\Carbon::parse($row->created_at)->format('Y-m-d'),
                    'Rp ' . number_format($totalSettlement, 0, ',', '.'),
                    'Rp ' . number_format($saldoUtama, 0, ',', '.'),
                    $rekomendasi,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    public function create()
    {
        logUserLogin();
        $leadSources = LeadsSource::all();
        $sectors = Sector::all();

        return view('leads-master.create', compact('leadSources', 'sectors'));
    }
    public function createExisting()
    {
        logUserLogin();
        $leadSources = LeadsSource::all();
        $sectors = Sector::all();

        return view('leads-master.create-existing', compact('leadSources', 'sectors'));
    }

    public function createEnterprise()
    {
        logUserLogin();
        $leadSources = LeadsSource::all();
        $sectors = Sector::all();

        return view('leads-master.create-enterprise', compact('leadSources', 'sectors'));
    }
    public function store(Request $request)
    {
        // Custom validation rules
        $rules = [
            'user_id' => 'required|exists:users,id',
            'source_id' => 'required|exists:leads_source,id',
            'sector_id' => 'required|exists:sectors,id',
            // 'kode_voucher' => 'nullable|string|max:255',
            'company_name' => 'required|string|max:255',
            'mobile_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^62\d{9,14}$/',
                'unique:leads_master,mobile_phone',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:leads_master,email',
                function ($attribute, $value, $fail) {
                    if (\DB::table('mitra_sbp')
                        ->where('email_myads', $value)
                        ->exists()) {
                        $fail('Email sudah terdaftar sebagai Mitra SBP.');
                    }
                },
            ],
            // 'status' => 'required|in:Ok,No',
            'nama' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
            'myads_account' => 'nullable|string|max:255',
            // Schedule fields validation
            'schedule_lokasi' => 'nullable|string|max:255',
            'schedule_tanggal' => 'nullable|date',
            'schedule_waktu_mulai' => 'nullable|date_format:H:i',
            'schedule_waktu_selesai' => 'nullable|date_format:H:i',
            'schedule_keterangan' => 'nullable|string|max:1000'
        ];

        $messages = [
            'mobile_phone.regex' => 'Nomor HP harus diawali dengan kode negara 62 dan hanya angka (9-12 digit).',
            'mobile_phone.unique' => 'Nomor HP sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ];

        $validated = $request->validate($rules, $messages);

        // $statusValue = $validated['status'] === 'Ok' ? 1 : 0;
        $statusValue = 1; // Default ke 1 (Yes) karena field status di form disembunyikan
        $leads = LeadsMaster::create([
            'user_id' => $validated['user_id'],
            'source_id' => $validated['source_id'],
            'sector_id' => $validated['sector_id'] ?? null,
            // 'kode_voucher' => $validated['kode_voucher'],
            'company_name' => $validated['company_name'] ?? '-',
            'mobile_phone' => $validated['mobile_phone'],
            'email' => $validated['email'] ?? null,
            'status' => $statusValue,  // simpan 1 untuk Ok, 0 untuk No
            'nama' => $validated['nama'],
            'address' => $validated['address'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'myads_account' => $validated['myads_account'] ?? null,
            'data_type' => 'Leads',
        ]);

        // Jika ada jadwal kunjungan, simpan ke calendar/bookings
        $scheduleInfo = null;
        if ($request->filled('schedule_tanggal') && $request->filled('schedule_waktu_mulai') && $request->filled('schedule_waktu_selesai')) {
            // Gunakan lokasi yang diinput, bukan nama perusahaan atau nama pelanggan
            $locationName = $validated['schedule_lokasi'] ?? $validated['company_name'] ?? '-';

            DB::table('bookings')->insert([
                'nama' => auth()->user()->name,
                'lokasi' => $locationName,
                'tanggal' => $validated['schedule_tanggal'],
                'waktu_mulai' => $validated['schedule_waktu_mulai'],
                'waktu_selesai' => $validated['schedule_waktu_selesai'],
                'keterangan' => $validated['schedule_keterangan'] ?? 'Kunjungan dari leads: ' . $validated['company_name'],
                'warna' => '#667eea',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Format schedule info untuk ditampilkan di success message
            $scheduleDate = \Carbon\Carbon::parse($validated['schedule_tanggal'])->translatedFormat('l, d F Y');
            $scheduleInfo = "Jadwal: {$scheduleDate} ({$validated['schedule_waktu_mulai']} - {$validated['schedule_waktu_selesai']})";
        }
        // DB::table('logbook')->insert([
        //     'leads_master_id' => $leads->id,
        //     'komitmen'        => 'New Leads',
        //     'plan_min_topup'  => 100000,
        //     'status'          => 'Prospect',
        //     'bulan'           => now()->month,
        //     'tahun'           => now()->year,
        //     'created_at'      => now(),
        //     'updated_at'      => now(),
        // ]);

        // Create session for success message with schedule info
        $successMsg = 'Leads baru untuk ' . $validated['company_name'] . ' berhasil ditambahkan.';
        if ($scheduleInfo) {
            $successMsg .= "\n" . $scheduleInfo;
        }
        
        return redirect()->route('leads-master.index')->with('success_with_schedule', $successMsg);
    }

    public function storeExisting(Request $request)
    {
        // dd('test');
        // Custom validation rules
        $rules = [
            'user_id' => 'required|exists:users,id',
            // 'source_id' => 'required|exists:leads_source,id',
            'sector_id' => 'nullable|exists:sectors,id',
            'company_name' => 'required|string|max:255',
            'mobile_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^62\d{9,14}$/',
                'unique:leads_master,mobile_phone',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:leads_master,email',
                function ($attribute, $value, $fail) {
                    if (\DB::table('mitra_sbp')
                        ->where('email_myads', $value)
                        ->exists()) {
                        $fail('Email sudah terdaftar sebagai Mitra SBP.');
                    }
                },
            ],
            // 'status' => 'required|in:Ok,No',
            'nama' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
            'myads_account' => 'required|string|max:255',
            'schedule_lokasi' => 'nullable|string|max:255',
            'schedule_tanggal' => 'nullable|date',
            'schedule_waktu_mulai' => 'nullable|date_format:H:i',
            'schedule_waktu_selesai' => 'nullable|date_format:H:i',
            'schedule_keterangan' => 'nullable|string|max:1000'
        ];

        $messages = [
            'mobile_phone.regex' => 'Nomor HP harus diawali dengan kode negara 62 dan hanya angka (9-12 digit).',
            'mobile_phone.unique' => 'Nomor HP sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ];

        $validated = $request->validate($rules, $messages);

        // $statusValue = $validated['status'] === 'Ok' ? 1 : 0;
        $statusValue = 1; // Default ke 1 (Yes) karena field status di form disembunyikan
        LeadsMaster::create([
            'user_id' => $validated['user_id'],
            'source_id' => null,
            'sector_id' => $validated['sector_id'] ?? null,
            // 'kode_voucher' => $validated['kode_voucher'],
            'company_name' => $validated['company_name'] ?? '-',
            'mobile_phone' => $validated['mobile_phone'],
            'email' => $validated['email'] ?? null,
            'status' => $statusValue,  // simpan 1 untuk Ok, 0 untuk No
            'nama' => $validated['nama'],
            'address' => $validated['address'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'myads_account' => $validated['myads_account'],
            'data_type' => 'Eksisting Akun',
        ]);

        $scheduleInfo = null;
        if ($request->filled('schedule_tanggal') && $request->filled('schedule_waktu_mulai') && $request->filled('schedule_waktu_selesai')) {
            $locationName = $validated['schedule_lokasi'] ?? $validated['company_name'] ?? '-';

            DB::table('bookings')->insert([
                'nama' => auth()->user()->name,
                'lokasi' => $locationName,
                'tanggal' => $validated['schedule_tanggal'],
                'waktu_mulai' => $validated['schedule_waktu_mulai'],
                'waktu_selesai' => $validated['schedule_waktu_selesai'],
                'keterangan' => $validated['schedule_keterangan'] ?? 'Kunjungan dari akun existing: ' . $validated['company_name'],
                'warna' => '#667eea',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $scheduleDate = \Carbon\Carbon::parse($validated['schedule_tanggal'])->translatedFormat('l, d F Y');
            $scheduleInfo = "Jadwal: {$scheduleDate} ({$validated['schedule_waktu_mulai']} - {$validated['schedule_waktu_selesai']})";
        }

        $successMsg = 'Akun existing untuk ' . $validated['company_name'] . ' berhasil ditambahkan.';
        if ($scheduleInfo) {
            $successMsg .= "\n" . $scheduleInfo;
        }

        return redirect()->route('leads-master.index')->with('success_with_schedule', $successMsg);
    }

    public function storeEnterprise(Request $request)
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'sector_id' => 'nullable|exists:sectors,id',
            'company_name' => 'required|string|max:255',
            'mobile_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^62\d{9,14}$/',
                'unique:leads_master,mobile_phone',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:leads_master,email',
                function ($attribute, $value, $fail) {
                    if (\DB::table('mitra_sbp')
                        ->where('email_myads', $value)
                        ->exists()) {
                        $fail('Email sudah terdaftar sebagai Mitra SBP.');
                    }
                },
            ],
            'nama' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
            'myads_account' => 'required|string|max:255'
        ];

        $messages = [
            'mobile_phone.regex' => 'Nomor HP harus diawali dengan kode negara 62 dan hanya angka (9-12 digit).',
            'mobile_phone.unique' => 'Nomor HP sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ];

        $validated = $request->validate($rules, $messages);

        $statusValue = 1;
        LeadsMaster::create([
            'user_id' => $validated['user_id'],
            'source_id' => null,
            'sector_id' => $validated['sector_id'] ?? null,
            'company_name' => $validated['company_name'] ?? '-',
            'mobile_phone' => $validated['mobile_phone'],
            'email' => $validated['email'] ?? null,
            'status' => $statusValue,
            'nama' => $validated['nama'],
            'address' => $validated['address'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'myads_account' => $validated['myads_account'],
            'data_type' => 'Enterprise Akun',
        ]);

        return redirect()->route('leads-master.index')->with('success', 'Leads baru berhasil disimpan.');
    }

    public function show($id)
    {
        logUserLogin();
        // Load lead beserta relasi
        $lead = LeadsMaster::with(['user', 'source', 'sector'])->findOrFail($id);

        return view('leads-master.show', compact('lead'));
    }

    public function edit(LeadsMaster $lead)
    {
        logUserLogin();
        
        $leadSources = LeadsSource::all();
        $sectors = Sector::all();
        $canvassers = Cache::remember('users_list_leads', 3600, fn() => User::orderBy('name')->get());
        return view('leads-master.edit', compact('lead', 'leadSources', 'sectors', 'canvassers'));
    }

    public function update(Request $request, LeadsMaster $lead)
    {
        $lead = LeadsMaster::findOrFail($lead->id);
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'source_id' => 'nullable|exists:leads_source,id',
            'sector_id' => 'required|exists:sectors,id',
            // 'kode_voucher' => 'string|max:255',
            'company_name' => 'nullable|string|max:255',
            'mobile_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^62\d{9,14}$/',
                Rule::unique('leads_master', 'mobile_phone')->ignore($lead->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('leads_master', 'email')->ignore($lead->id),
            ],
            // 'status' => 'required|in:Ok,No',
            'nama' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
            'myads_account' => 'nullable|string|max:255'
        ]);

        $userId = auth()->user()->role === 'Admin' ? $request->user_id : $lead->user_id;

        $lead->update([
            'user_id' => $userId,
            // 'kode_voucher' => $request->kode_voucher,
            'company_name' => $request->company_name,
            'mobile_phone' => $request->mobile_phone,
            'email' => $request->email,
            'source_id' => $request->source_id,
            'nama' => $request->nama,
            'sector_id' => $request->sector_id,
            // 'status' => $request->status == 'Ok' ? 1 : 0,
            'remarks' => $request->remarks,
            'myads_account' => $request->myads_account,
        ]);

        return redirect()->route('leads-master.index')->with('success', 'Lead berhasil diupdate');
    }

    /**
     * Sinkronisasi leads_master dengan data_registarsi_status_approveorreject
     * - Update data_type ke 'Eksisting Akun' jika email sudah terdaftar
     * - Isi myads_account dengan email dari registrasi
     * - Cek juga akun yang sudah lebih dari 1 bulan di tanggal_approval_aktivasi
     */
    public function syncLeadsWithRegistration()
    {
        $oneMonthAgo = now()->subMonth();

        // 1. Sinkronisasi: Email leads_master yang ada di data_registarsi_status_approveorreject
        $syncedCount = DB::table('leads_master as lm')
            ->join('data_registarsi_status_approveorreject as dsa', 'lm.email', '=', 'dsa.email')
            ->where('dsa.status', 'APPROVE')
            ->where('lm.data_type', '!=', 'Eksisting Akun') // Hindari update yang sudah ter-sync
            ->update([
                'lm.data_type' => 'Eksisting Akun',
                'lm.myads_account' => DB::raw('dsa.email'),
                'lm.updated_at' => now(),
            ]);

        // 2. Update leads yang emailnya cocok dan sudah lebih dari 1 bulan approval
        $syncedOldCount = DB::table('leads_master as lm')
            ->join('data_registarsi_status_approveorreject as dsa', 'lm.email', '=', 'dsa.email')
            ->where('dsa.status', 'APPROVE')
            ->where('dsa.tanggal_approval_aktivasi', '<', $oneMonthAgo)
            ->where('lm.data_type', '!=', 'Eksisting Akun') // Hindari update yang sudah ter-sync
            ->update([
                'lm.data_type' => 'Eksisting Akun',
                'lm.myads_account' => DB::raw('dsa.email'),
                'lm.updated_at' => now(),
            ]);

        // 3. Sinkronisasi reg_id dari user_id registrasi berdasarkan email
        $syncedRegIdCount = DB::table('leads_master as lm')
            ->join('data_registarsi_status_approveorreject as dsa', 'lm.email', '=', 'dsa.email')
            ->where('dsa.status', 'APPROVE')
            ->whereNotNull('dsa.user_id')
            ->where('dsa.user_id', '!=', '')
            ->update([
                'lm.reg_id' => DB::raw('dsa.user_id'),
                'lm.updated_at' => now(),
            ]);

        \Log::info('Leads Master Sync - Email matched: ' . $syncedCount . ' records, Old accounts (1+ month): ' . $syncedOldCount . ' records, Reg ID synced: ' . $syncedRegIdCount . ' records');

        return response()->json([
            'success' => true,
            'message' => "Sinkronisasi selesai. Email cocok: {$syncedCount}, Akun lama (>1 bulan): {$syncedOldCount}, Reg ID: {$syncedRegIdCount}",
            'synced_email_count' => $syncedCount,
            'synced_old_account_count' => $syncedOldCount,
            'synced_reg_id_count' => $syncedRegIdCount,
        ]);
    }

    /**
     * Sinkronisasi regional di leads_master
     * Kondisi 1: Cocokkan email leads_master dengan email_client di report_balance_top_up
     *           - Ambil province name dari report_balance_top_up
     *           - Cocokkan province dengan regional_provinces
     * Kondisi 2: Jika email tidak ditemukan di report_balance_top_up, cek di data_registarsi_status_approveorreject
     *           - Ambil provinsi dari data_registarsi_status_approveorreject
     *           - Cocokkan province dengan regional_provinces
     */
    public function syncLeadsWithRegional()
    {
        // Kondisi 1: Sinkronisasi dari report_balance_top_up
        $regionalFromTopup = DB::table('report_balance_top_up as rbt')
            ->join('regional_provinces as rp', DB::raw('LOWER(rbt.data_province_name)'), '=', DB::raw('LOWER(rp.province)'))
            ->select(
                DB::raw('LOWER(rbt.email_client) as email_lower'),
                'rp.regional'
            )
            ->distinct()
            ->orderBy('rbt.tgl_transaksi', 'desc');

        $syncedCountFromTopup = DB::table('leads_master as lm')
            ->joinSub($regionalFromTopup, 'rt', function ($join) {
                $join->on(DB::raw('LOWER(lm.email)'), '=', 'rt.email_lower');
            })
            ->whereNull('lm.regional') // Hanya update yang belum punya regional
            ->update([
                'lm.regional' => DB::raw('rt.regional'),
                'lm.updated_at' => now(),
            ]);

        // Kondisi 2: Sinkronisasi dari data_registarsi_status_approveorreject untuk email yang belum ketemu di report_balance_top_up
        $regionalFromRegistrasi = DB::table('data_registarsi_status_approveorreject as dsa')
            ->join('regional_provinces as rp', DB::raw('LOWER(dsa.provinsi)'), '=', DB::raw('LOWER(rp.province)'))
            ->select(
                DB::raw('LOWER(dsa.email) as email_lower'),
                'rp.regional'
            )
            ->distinct();

        $syncedCountFromRegistrasi = DB::table('leads_master as lm')
            ->joinSub($regionalFromRegistrasi, 'rr', function ($join) {
                $join->on(DB::raw('LOWER(lm.email)'), '=', 'rr.email_lower');
            })
            ->whereNull('lm.regional') // Hanya update yang belum punya regional
            ->update([
                'lm.regional' => DB::raw('rr.regional'),
                'lm.updated_at' => now(),
            ]);

        $totalSyncedCount = $syncedCountFromTopup + $syncedCountFromRegistrasi;

        \Log::info('Leads Master Regional Sync - From TopUp: ' . $syncedCountFromTopup . ' records, From Registrasi: ' . $syncedCountFromRegistrasi . ' records');

        return response()->json([
            'success' => true,
            'message' => "Sinkronisasi regional selesai. Dari TopUp: {$syncedCountFromTopup}, Dari Registrasi: {$syncedCountFromRegistrasi}",
            'synced_count_from_topup' => $syncedCountFromTopup,
            'synced_count_from_registrasi' => $syncedCountFromRegistrasi,
            'total_synced_count' => $totalSyncedCount,
        ]);
    }

    /**
     * Populate/Refresh detail_leads_summary table
     * Denormalisasi data dari leads_master + joins untuk performa lebih baik
     * Berjalan setiap 5 menit agar selalu up-to-date
     */
    public function refreshDetailLeadsSummary()
    {
        $month = now()->month;
        $year  = now()->year;

        // Subquery untuk settlement bulan ini
        $settlementSubquery = DB::table('report_balance_top_up as rbt')
            ->select('email_client', DB::raw('SUM(total_settlement_klien) as total_settlement_klien'))
            ->whereMonth('tgl_transaksi', $month)
            ->whereYear('tgl_transaksi', $year)
            ->groupBy('email_client');

        // Subquery saldo utama berdasarkan reg_id (leads_master) = id_user (saldo_users)
        $saldoUtamaSubquery = DB::table('saldo_users as su')
            ->select(
                'su.id_user',
                DB::raw('COALESCE(su.saldo_utama, 0) as saldo_utama')
            );

        $baseQuery = LeadsMaster::with(['user'])
            ->leftJoinSub(
                $settlementSubquery,
                'rbt',
                function ($join) {
                    $join->on(DB::raw('LOWER(rbt.email_client)'), '=', DB::raw('LOWER(leads_master.email)'));
                }
            )
            ->leftJoinSub(
                $saldoUtamaSubquery,
                'su',
                function ($join) {
                    $join->on('leads_master.reg_id', '=', 'su.id_user');
                }
            )
            ->select(
                'leads_master.id as leads_master_id',
                'leads_master.user_id',
                'leads_master.source_id',
                'leads_master.sector_id',
                'leads_master.regional',
                'leads_master.company_name',
                'leads_master.mobile_phone',
                'leads_master.email',
                'leads_master.status',
                'leads_master.nama',
                'leads_master.address',
                'leads_master.myads_account',
                'leads_master.data_type',
                'leads_master.komitmen',
                'leads_master.plan_min_topup',
                'leads_master.remarks',
                'leads_master.flag_event',
                'leads_master.created_at',
                'leads_master.updated_at',
                DB::raw('COALESCE(rbt.total_settlement_klien, 0) as total_settlement_klien'),
                DB::raw('COALESCE(su.saldo_utama, 0) as saldo_utama')
            )
            ->orderBy('leads_master.id');

        $totalInserted = 0;

        DB::transaction(function () use ($baseQuery, &$totalInserted) {
            // Gunakan delete dalam transaction supaya tabel tidak tertinggal setengah jadi
            // saat job gagal atau koneksi DB sempat putus di tengah proses refresh.
            DB::table('detail_leads_summary')->delete();

            $baseQuery->chunkById(500, function ($leadsData) use (&$totalInserted) {
                $summaryData = $leadsData->map(function ($lead) {
                    return [
                        'leads_master_id' => $lead->leads_master_id,
                        'user_id' => $lead->user_id,
                        'user_name' => $lead->user->name ?? null,
                        'source_id' => $lead->source_id,
                        'sector_id' => $lead->sector_id,
                        'regional' => $lead->regional,
                        'company_name' => $lead->company_name,
                        'mobile_phone' => $lead->mobile_phone,
                        'email' => $lead->email,
                        'status' => $lead->status,
                        'nama' => $lead->nama,
                        'address' => $lead->address,
                        'myads_account' => $lead->myads_account,
                        'data_type' => $lead->data_type,
                        'komitmen' => $lead->komitmen,
                        'plan_min_topup' => $lead->plan_min_topup,
                        'remarks' => $lead->remarks,
                        'flag_event' => $lead->flag_event,
                        'total_settlement_klien' => $lead->total_settlement_klien,
                        'saldo_utama' => $lead->saldo_utama,
                        'created_at' => $lead->created_at,
                        'updated_at' => $lead->updated_at,
                    ];
                })->toArray();

                if (!empty($summaryData)) {
                    DB::table('detail_leads_summary')->insert($summaryData);
                    $totalInserted += count($summaryData);
                }
            }, 'leads_master.id', 'leads_master_id');
        });

        \Log::info('Detail Leads Summary - Refreshed: ' . $totalInserted . ' records');

        return response()->json([
            'success' => true,
            'message' => "Summary direfresh. Total: " . $totalInserted . " records",
            'total_records' => $totalInserted,
        ]);
    }

    /**
     * Update detail_leads_summary untuk satu record (dipanggil dari Event Listener saat ada update)
     */
    public function updateSummaryRecord($leadId)
    {
        $month = now()->month;
        $year  = now()->year;

        // Get settlement untuk email ini
        $settlement = DB::table('report_balance_top_up')
            ->where(DB::raw('LOWER(email_client)'), '=', DB::raw("LOWER((SELECT email FROM leads_master WHERE id = ?))"))
            ->setBindings([$leadId])
            ->whereMonth('tgl_transaksi', $month)
            ->whereYear('tgl_transaksi', $year)
            ->sum('total_settlement_klien');

        // Get lead data
        $lead = LeadsMaster::with(['user'])->findOrFail($leadId);
        $saldoUtama = DB::table('saldo_users')
            ->where('id_user', $lead->reg_id)
            ->value('saldo_utama');

        // Update atau insert ke summary table
        DB::table('detail_leads_summary')->updateOrInsert(
            ['leads_master_id' => $leadId],
            [
                'user_id' => $lead->user_id,
                'user_name' => $lead->user->name ?? null,
                'source_id' => $lead->source_id,
                'sector_id' => $lead->sector_id,
                'regional' => $lead->regional,
                'company_name' => $lead->company_name,
                'mobile_phone' => $lead->mobile_phone,
                'email' => $lead->email,
                'status' => $lead->status,
                'nama' => $lead->nama,
                'address' => $lead->address,
                'myads_account' => $lead->myads_account,
                'data_type' => $lead->data_type,
                'komitmen' => $lead->komitmen,
                'plan_min_topup' => $lead->plan_min_topup,
                'remarks' => $lead->remarks,
                'flag_event' => $lead->flag_event,
                'total_settlement_klien' => $settlement ?? 0,
                'saldo_utama' => $saldoUtama ?? 0,
                'created_at' => $lead->created_at,
                'updated_at' => now(),
            ]
        );
    }
    public function syncLeadsFromTopUp()
    {
        // 1️⃣ Referral code yang valid
        $validReferralCodes = [
            'EXTRA1','EXTRA2','EXTRA3','EXTRA4','EXTRA5','EXTRA6','EXTRA7',
            'EXTRA8','EXTRA9','EXTRA10','EXTRA11','EXTRA12','EXTRA13','EXTRA14','EXTRA15',
            'SUPER1','SUPER2','SUPER3','SUPER4','SUPER5','SUPER6','SUPER7','SUPER8',
            'HEBAT1','HEBAT2','HEBAT3','HEBAT4','HEBAT5','HEBAT6','HEBAT7',
            'HEBAT8','HEBAT9','HEBAT10','HEBAT11','HEBAT12','HEBAT13',
        ];

        // 2️⃣ Ambil data top up + referral_code user
        $topUps = DB::table('report_balance_top_up as r')
            ->join('users as u', 'u.referral_code', '=', 'r.voucher_code')
            ->whereIn(DB::raw('UPPER(u.referral_code)'), $validReferralCodes)
            ->whereNotNull('r.email_client')
            ->select(
                'u.id as user_id',
                'r.company_name',
                'r.email_client',
                'r.alamat',
                DB::raw('UPPER(u.referral_code) as referral_code')
            )
            ->get();
            
        foreach ($topUps as $topUp) {
            
            // 3️⃣ CEK EMAIL — kalau sudah ada, skip
            $emailExists = LeadsMaster::where('email', $topUp->email_client)->exists();
            if ($emailExists) {
                continue;
            }

            $existsInMitraSbp = DB::table('mitra_sbp')
                ->where('email_myads', $topUp->email_client)
                ->exists();
            if ($existsInMitraSbp) {
                continue;
            }
            
            // 4️⃣ Insert ke leads_master
            LeadsMaster::create([
                'user_id'        => $topUp->user_id,
                'source_id'      => null,
                'sector_id'      => 2, // Default ke sektor "Lain-lain"
                'company_name'   => $topUp->company_name,
                'mobile_phone'   => '-',
                'email'          => $topUp->email_client,
                'status'         => 1,
                'nama'           => $topUp->company_name ?? 'Unknown',
                'address'        => $topUp->alamat,
                'remarks'        => 'Automate Create by Referral Code: ' . $topUp->referral_code,
                'myads_account'  => null,
                'data_type'      => 'Leads'
            ]);
        \Log::info('Sync Leads from TopUp - Total new leads added: ' . $topUps->count());
        }
        return response()->json([
            'success' => true,
            'total'   => $topUps->count(),
            'message' => 'Sync leads selesai (email duplicate di-skip)',
        ]);
    }

}
