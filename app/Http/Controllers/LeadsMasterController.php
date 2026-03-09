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
                'dls.*'
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
                ->orWhere('dls.mobile_phone', 'like', "%$search%");
            });
        }

        return datatables()->of($query)
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
        $query = LeadsMaster::with(['user', 'source', 'sector'])
            ->orderBy('created_at', 'asc');

        // 🔐 ROLE
        if (!auth()->user()->hasRole('Admin')) {
            $query->where('user_id', auth()->id());
        }

        // Filter Canvasser
        if ($request->canvasser) {
            $query->where('user_id', $request->canvasser);
        }

        // Filter Regional
        if ($request->regional) {
            $query->where('regional', $request->regional);
        }

        // Filter Tanggal
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
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
            'Status',
            'Canvasser',
            'Regional',
            'Nama Perusahaan',
            'Email',
            'No HP',
            'Tipe Data',
            'Tanggal',
        ];

        $callback = function () use ($query, $columns) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM (biar Excel tidak rusak)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $columns);

            foreach ($query->cursor() as $row) {
                fputcsv($file, [
                    $row->status == 1 ? 'Deal' : 'No Deal',
                    $row->user->name ?? '-',
                    $row->regional ?? '-',
                    $row->company_name ?? '-',
                    $row->email ?? '-',
                    $row->mobile_phone ?? '-',
                    $row->data_type ?? '-',
                    $row->created_at->format('Y-m-d'),
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
            'myads_account' => 'required|string|max:255'
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
     * 
     * ✅ OPTIMIZED: Batch processing untuk mencegah table lock
     */
    public function syncLeadsWithRegistration()
    {
        set_time_limit(300); // 5 menit max
        ini_set('memory_limit', '512M');
        
        $startTime = now();
        $oneMonthAgo = now()->subMonth();
        $syncedCount = 0;
        $syncedOldCount = 0;
        $syncedRegIdCount = 0;

        try {
            // 1. Get email yang perlu di-sync (hanya yang belum ter-sync)
            $leadsToSync = DB::table('leads_master as lm')
                ->join('data_registarsi_status_approveorreject as dsa', 'lm.email', '=', 'dsa.email')
                ->where('dsa.status', 'APPROVE')
                ->where('lm.data_type', '!=', 'Eksisting Akun')
                ->select('lm.id', 'dsa.email', 'dsa.user_id', 'dsa.tanggal_approval_aktivasi')
                ->limit(500) // Max 500 rows per run untuk safety
                ->get();

            if ($leadsToSync->isEmpty()) {
                \Log::info('Leads Master Sync - No records to sync');
                return response()->json(['success' => true, 'message' => 'No records to sync']);
            }

            \Log::info("Leads Master Sync - Processing {$leadsToSync->count()} records...");

            // 2. Process in batches of 100 (disable events untuk performa)
            LeadsMaster::withoutEvents(function () use ($leadsToSync, &$syncedCount, &$syncedOldCount, &$syncedRegIdCount, $oneMonthAgo) {
                $leadsToSync->chunk(100)->each(function ($batch, $index) use (&$syncedCount, &$syncedOldCount, &$syncedRegIdCount, $oneMonthAgo) {
                    DB::transaction(function () use ($batch, &$syncedCount, &$syncedOldCount, &$syncedRegIdCount, $oneMonthAgo) {
                        foreach ($batch as $lead) {
                            try {
                                $updateData = [
                                    'data_type' => 'Eksisting Akun',
                                    'myads_account' => $lead->email,
                                    'updated_at' => now(),
                                ];

                                // Tambahkan reg_id jika ada user_id dari registrasi
                                if (!empty($lead->user_id)) {
                                    $updateData['reg_id'] = $lead->user_id;
                                    $syncedRegIdCount++;
                                }

                                DB::table('leads_master')
                                    ->where('id', $lead->id)
                                    ->update($updateData);

                                $syncedCount++;

                                // Count old accounts (>1 month)
                                if ($lead->tanggal_approval_aktivasi && Carbon::parse($lead->tanggal_approval_aktivasi)->lt($oneMonthAgo)) {
                                    $syncedOldCount++;
                                }
                            } catch (\Exception $e) {
                                \Log::error("Sync lead ID {$lead->id} error: " . $e->getMessage());
                            }
                        }
                    });

                    \Log::info("Batch " . ($index + 1) . " completed - Synced: {$syncedCount}");
                    usleep(100000); // Sleep 100ms antar batch untuk kurangi load
                });
            });

            $duration = now()->diffInSeconds($startTime);
            \Log::info("Leads Master Sync - Completed in {$duration}s - Email matched: {$syncedCount}, Old accounts: {$syncedOldCount}, Reg ID: {$syncedRegIdCount}");

            return response()->json([
                'success' => true,
                'message' => "Sync selesai. Processed: {$syncedCount} records in {$duration}s",
                'synced_email_count' => $syncedCount,
                'synced_old_account_count' => $syncedOldCount,
                'synced_reg_id_count' => $syncedRegIdCount,
                'duration_seconds' => $duration,
            ]);
        } catch (\Exception $e) {
            \Log::error('Sync Leads With Registration Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sinkronisasi regional di leads_master
     * Kondisi 1: Cocokkan email leads_master dengan email_client di report_balance_top_up
     *           - Ambil province name dari report_balance_top_up
     *           - Cocokkan province dengan regional_provinces
     * Kondisi 2: Jika email tidak ditemukan di report_balance_top_up, cek di data_registarsi_status_approveorreject
     *           - Ambil provinsi dari data_registarsi_status_approveorreject
     *           - Cocokkan province dengan regional_provinces
     * 
     * ✅ OPTIMIZED: Batch processing untuk mencegah table lock
     */
    public function syncLeadsWithRegional()
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        $startTime = now();
        $syncedCountFromTopup = 0;
        $syncedCountFromRegistrasi = 0;

        try {
            // 1. Get leads yang belum punya regional (limit 500 per run)
            $leadsWithoutRegional = DB::table('leads_master')
                ->whereNull('regional')
                ->whereNotNull('email')
                ->select('id', 'email')
                ->limit(500)
                ->get();

            if ($leadsWithoutRegional->isEmpty()) {
                \Log::info('Regional Sync - No records to sync');
                return response()->json(['success' => true, 'message' => 'No records to sync']);
            }

            \Log::info("Regional Sync - Processing {$leadsWithoutRegional->count()} records...");

            // 2. Build mapping email -> regional dari TopUp
            $regionalMapTopup = DB::table('report_balance_top_up as rbt')
                ->join('regional_provinces as rp', DB::raw('LOWER(rbt.data_province_name)'), '=', DB::raw('LOWER(rp.province)'))
                ->select(DB::raw('LOWER(rbt.email_client) as email'), 'rp.regional')
                ->distinct()
                ->orderBy('rbt.tgl_transaksi', 'desc')
                ->pluck('regional', 'email')
                ->toArray();

            // 3. Build mapping email -> regional dari Registrasi
            $regionalMapRegistrasi = DB::table('data_registarsi_status_approveorreject as dsa')
                ->join('regional_provinces as rp', DB::raw('LOWER(dsa.provinsi)'), '=', DB::raw('LOWER(rp.province)'))
                ->select(DB::raw('LOWER(dsa.email) as email'), 'rp.regional')
                ->distinct()
                ->pluck('regional', 'email')
                ->toArray();

            // 4. Process batch dengan disable events
            LeadsMaster::withoutEvents(function () use ($leadsWithoutRegional, $regionalMapTopup, $regionalMapRegistrasi, &$syncedCountFromTopup, &$syncedCountFromRegistrasi) {
                $leadsWithoutRegional->chunk(100)->each(function ($batch, $index) use ($regionalMapTopup, $regionalMapRegistrasi, &$syncedCountFromTopup, &$syncedCountFromRegistrasi) {
                    DB::transaction(function () use ($batch, $regionalMapTopup, $regionalMapRegistrasi, &$syncedCountFromTopup, &$syncedCountFromRegistrasi) {
                        foreach ($batch as $lead) {
                            $emailLower = strtolower($lead->email);
                            $regional = null;

                            // Cek di TopUp dulu
                            if (isset($regionalMapTopup[$emailLower])) {
                                $regional = $regionalMapTopup[$emailLower];
                                $syncedCountFromTopup++;
                            }
                            // Kalau gak ada, cek di Registrasi
                            elseif (isset($regionalMapRegistrasi[$emailLower])) {
                                $regional = $regionalMapRegistrasi[$emailLower];
                                $syncedCountFromRegistrasi++;
                            }

                            if ($regional) {
                                try {
                                    DB::table('leads_master')
                                        ->where('id', $lead->id)
                                        ->update([
                                            'regional' => $regional,
                                            'updated_at' => now(),
                                        ]);
                                } catch (\Exception $e) {
                                    \Log::error("Regional sync lead ID {$lead->id} error: " . $e->getMessage());
                                }
                            }
                        }
                    });

                    \Log::info("Regional Batch " . ($index + 1) . " completed - TopUp: {$syncedCountFromTopup}, Registrasi: {$syncedCountFromRegistrasi}");
                    usleep(100000); // Sleep 100ms
                });
            });

            $totalSyncedCount = $syncedCountFromTopup + $syncedCountFromRegistrasi;
            $duration = now()->diffInSeconds($startTime);
            
            \Log::info("Regional Sync - Completed in {$duration}s - TopUp: {$syncedCountFromTopup}, Registrasi: {$syncedCountFromRegistrasi}");

            return response()->json([
                'success' => true,
                'message' => "Regional sync selesai. Total: {$totalSyncedCount} records in {$duration}s",
                'synced_count_from_topup' => $syncedCountFromTopup,
                'synced_count_from_registrasi' => $syncedCountFromRegistrasi,
                'total_synced_count' => $totalSyncedCount,
                'duration_seconds' => $duration,
            ]);
        } catch (\Exception $e) {
            \Log::error('Regional Sync Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Populate/Refresh detail_leads_summary table
     * Denormalisasi data dari leads_master + joins untuk performa lebih baik
     * 
     * ✅ OPTIMIZED: Incremental update (tidak truncate), batch processing
     */
    public function refreshDetailLeadsSummary()
    {
        set_time_limit(600); // 10 menit max (ini yang paling berat)
        ini_set('memory_limit', '1G');
        
        $startTime = now();
        $month = now()->month;
        $year  = now()->year;
        $processedCount = 0;

        try {
            \Log::info('Detail Leads Summary - Starting refresh...');

            // 1. Build settlement mapping (email -> total_settlement)
            $settlementMap = DB::table('report_balance_top_up')
                ->select('email_client', DB::raw('SUM(total_settlement_klien) as total'))
                ->whereMonth('tgl_transaksi', $month)
                ->whereYear('tgl_transaksi', $year)
                ->groupBy('email_client')
                ->pluck('total', 'email_client')
                ->mapWithKeys(function ($value, $key) {
                    return [strtolower($key) => $value];
                })
                ->toArray();

            // 2. Build saldo mapping (reg_id -> saldo_utama)
            $saldoMap = DB::table('saldo_users')
                ->pluck('saldo_utama', 'id_user')
                ->toArray();

            \Log::info("Settlement map: " . count($settlementMap) . " records, Saldo map: " . count($saldoMap) . " records");

            // 3. Process leads in chunks (INCREMENTAL UPDATE, tidak truncate!)
            DB::table('leads_master')
                ->orderBy('id')
                ->chunk(200, function ($leads) use ($settlementMap, $saldoMap, &$processedCount, $startTime) {
                    $batchData = [];

                    foreach ($leads as $lead) {
                        // Get settlement dari map
                        $emailLower = strtolower($lead->email ?? '');
                        $settlement = $settlementMap[$emailLower] ?? 0;

                        // Get saldo dari map
                        $saldo = $saldoMap[$lead->reg_id ?? ''] ?? 0;

                        // Get user name
                        $userName = null;
                        if ($lead->user_id) {
                            $user = Cache::remember("user_{$lead->user_id}", 3600, function () use ($lead) {
                                return DB::table('users')->where('id', $lead->user_id)->first();
                            });
                            $userName = $user->name ?? null;
                        }

                        $batchData[] = [
                            'leads_master_id' => $lead->id,
                            'user_id' => $lead->user_id,
                            'user_name' => $userName,
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
                            'total_settlement_klien' => $settlement,
                            'saldo_utama' => $saldo,
                            'created_at' => $lead->created_at,
                            'updated_at' => $lead->updated_at,
                        ];
                    }

                    // Upsert batch (update jika ada, insert jika baru)
                    if (!empty($batchData)) {
                        try {
                            DB::transaction(function () use ($batchData) {
                                foreach (array_chunk($batchData, 100) as $chunk) {
                                    foreach ($chunk as $data) {
                                        DB::table('detail_leads_summary')
                                            ->updateOrInsert(
                                                ['leads_master_id' => $data['leads_master_id']],
                                                $data
                                            );
                                    }
                                }
                            });
                            $processedCount += count($batchData);
                        } catch (\Exception $e) {
                            \Log::error('Summary batch insert error: ' . $e->getMessage());
                        }
                    }

                    // Log progress setiap batch
                    $elapsed = now()->diffInSeconds($startTime);
                    \Log::info("Summary refresh - Processed: {$processedCount} records in {$elapsed}s");

                    usleep(200000); // Sleep 200ms antar batch
                });

            // 4. Delete summary rows yang leads_master-nya sudah dihapus
            $deletedCount = DB::table('detail_leads_summary as dls')
                ->leftJoin('leads_master as lm', 'dls.leads_master_id', '=', 'lm.id')
                ->whereNull('lm.id')
                ->delete();

            if ($deletedCount > 0) {
                \Log::info("Cleaned up {$deletedCount} orphaned summary records");
            }

            $duration = now()->diffInSeconds($startTime);
            \Log::info("Detail Leads Summary - Completed in {$duration}s - Processed: {$processedCount} records");

            return response()->json([
                'success' => true,
                'message' => "Summary refreshed. Total: {$processedCount} records in {$duration}s",
                'total_records' => $processedCount,
                'deleted_orphaned' => $deletedCount,
                'duration_seconds' => $duration,
            ]);
        } catch (\Exception $e) {
            \Log::error('Refresh Detail Leads Summary Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update detail_leads_summary untuk satu record (dipanggil dari Event Listener saat ada update)
     * 
     * ⚡ OPTIMIZED: Cache query, simplified logic
     */
    public function updateSummaryRecord($leadId)
    {
        try {
            $month = now()->month;
            $year  = now()->year;

            // Get lead data (cache untuk performa)
            $lead = Cache::remember("lead_data_{$leadId}", 300, function () use ($leadId) {
                return LeadsMaster::with(['user'])->find($leadId);
            });

            if (!$lead) {
                \Log::warning("updateSummaryRecord: Lead ID {$leadId} not found");
                return;
            }

            // Get settlement (cache 5 menit)
            $cacheKey = "settlement_{$lead->email}_{$month}_{$year}";
            $settlement = Cache::remember($cacheKey, 300, function () use ($lead, $month, $year) {
                if (!$lead->email) return 0;
                
                return DB::table('report_balance_top_up')
                    ->where(DB::raw('LOWER(email_client)'), strtolower($lead->email))
                    ->whereMonth('tgl_transaksi', $month)
                    ->whereYear('tgl_transaksi', $year)
                    ->sum('total_settlement_klien') ?? 0;
            });

            // Get saldo (cache 5 menit)
            $saldoUtama = 0;
            if ($lead->reg_id) {
                $saldoUtama = Cache::remember("saldo_{$lead->reg_id}", 300, function () use ($lead) {
                    return DB::table('saldo_users')
                        ->where('id_user', $lead->reg_id)
                        ->value('saldo_utama') ?? 0;
                });
            }

            // Update summary (gunakan transaction untuk konsistensi)
            DB::transaction(function () use ($leadId, $lead, $settlement, $saldoUtama) {
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
                        'total_settlement_klien' => $settlement,
                        'saldo_utama' => $saldoUtama,
                        'created_at' => $lead->created_at,
                        'updated_at' => now(),
                    ]
                );
            });
        } catch (\Exception $e) {
            \Log::error("updateSummaryRecord error for lead ID {$leadId}: " . $e->getMessage());
        }
    }
    /**
     * Sync leads dari TopUp berdasarkan referral code
     * 
     * ✅ OPTIMIZED: Batch insert, disable events
     */
    public function syncLeadsFromTopUp()
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        $startTime = now();
        $addedCount = 0;
        $skippedCount = 0;

        try {
            // 1. Referral code yang valid
            $validReferralCodes = [
                'EXTRA1','EXTRA2','EXTRA3','EXTRA4','EXTRA5','EXTRA6','EXTRA7',
                'EXTRA8','EXTRA9','EXTRA10','EXTRA11','EXTRA12','EXTRA13','EXTRA14','EXTRA15',
                'SUPER1','SUPER2','SUPER3','SUPER4','SUPER5','SUPER6','SUPER7','SUPER8',
            ];

            // 2. Ambil data top up hari ini (limit 200 untuk safety)
            $topUps = DB::table('report_balance_top_up as r')
                ->join('users as u', 'u.referral_code', '=', 'r.voucher_code')
                ->whereIn(DB::raw('UPPER(u.referral_code)'), $validReferralCodes)
                ->whereNotNull('r.email_client')
                ->whereDate('r.tgl_transaksi', Carbon::today())
                ->select(
                    'u.id as user_id',
                    'r.company_name',
                    'r.email_client',
                    'r.alamat',
                    DB::raw('UPPER(u.referral_code) as referral_code')
                )
                ->limit(200)
                ->get();

            if ($topUps->isEmpty()) {
                \Log::info('Sync Leads from TopUp - No new leads to process');
                return response()->json(['success' => true, 'message' => 'No new leads']);
            }

            \Log::info("Sync TopUp - Processing {$topUps->count()} potential leads...");

            // 3. Get existing emails untuk filter duplicate
            $existingEmails = DB::table('leads_master')
                ->whereIn('email', $topUps->pluck('email_client')->toArray())
                ->pluck('email')
                ->map(fn($email) => strtolower($email))
                ->toArray();

            // 4. Process batch dengan disable events
            LeadsMaster::withoutEvents(function () use ($topUps, $existingEmails, &$addedCount, &$skippedCount) {
                $topUps->chunk(50)->each(function ($batch, $index) use ($existingEmails, &$addedCount, &$skippedCount) {
                    DB::transaction(function () use ($batch, $existingEmails, &$addedCount, &$skippedCount) {
                        foreach ($batch as $topUp) {
                            // Skip jika email sudah ada
                            if (in_array(strtolower($topUp->email_client), $existingEmails)) {
                                $skippedCount++;
                                continue;
                            }

                            try {
                                DB::table('leads_master')->insert([
                                    'user_id'        => $topUp->user_id,
                                    'source_id'      => null,
                                    'sector_id'      => 2,
                                    'company_name'   => $topUp->company_name,
                                    'mobile_phone'   => '-',
                                    'email'          => $topUp->email_client,
                                    'status'         => 1,
                                    'nama'           => $topUp->company_name ?? 'Unknown',
                                    'address'        => $topUp->alamat,
                                    'remarks'        => 'Auto-created from TopUp (Ref: ' . $topUp->referral_code . ')',
                                    'myads_account'  => null,
                                    'data_type'      => 'Leads',
                                    'created_at'     => now(),
                                    'updated_at'     => now(),
                                ]);
                                
                                $addedCount++;
                                // Add to existing untuk prevent duplicate di batch berikutnya
                                $existingEmails[] = strtolower($topUp->email_client);
                            } catch (\Exception $e) {
                                \Log::error("Insert lead from TopUp error: " . $e->getMessage());
                                $skippedCount++;
                            }
                        }
                    });

                    \Log::info("TopUp Batch " . ($index + 1) . " completed - Added: {$addedCount}, Skipped: {$skippedCount}");
                    usleep(100000); // Sleep 100ms
                });
            });

            $duration = now()->diffInSeconds($startTime);
            \Log::info("Sync TopUp - Completed in {$duration}s - Added: {$addedCount}, Skipped: {$skippedCount}");

            return response()->json([
                'success' => true,
                'message' => "Sync TopUp selesai. Added: {$addedCount}, Skipped: {$skippedCount} in {$duration}s",
                'added' => $addedCount,
                'skipped' => $skippedCount,
                'duration_seconds' => $duration,
            ]);
        } catch (\Exception $e) {
            \Log::error('Sync Leads From TopUp Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

}
