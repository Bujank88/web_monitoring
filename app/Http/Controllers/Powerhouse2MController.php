<?php

namespace App\Http\Controllers;

use App\Models\LeadsPowerhouse;
use App\Models\LeadsSource;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Powerhouse2MController extends Controller
{
    public function report()
    {
        logUserLogin();

        $year = 2026;
        $months = [
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ];

        $powerhouseQuery = DB::table('users')
            ->whereRaw('UPPER(role) = ?', ['PH'])
            ->orderBy('name');

        $powerhouses = $powerhouseQuery->get(['id', 'name']);
        $powerhouseIds = $powerhouses->pluck('id')->all();

        $leads = DB::table('leads_powerhouse')
            ->whereIn('user_id', $powerhouseIds)
            ->orderBy('company_name')
            ->get(['id', 'user_id', 'company_name', 'email', 'usecase', 'solusi']);

        $emails = $leads->pluck('email')
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values()
            ->all();

        $monthlySettlement = empty($emails)
            ? collect()
            : DB::table('report_balance_top_up as rbt')
                ->selectRaw('LOWER(TRIM(rbt.email_client)) as email')
                ->selectRaw('MONTH(rbt.tgl_transaksi) as month_no')
                ->selectRaw('SUM(CAST(rbt.total_settlement_klien AS DECIMAL(18,2))) as total_settlement')
                ->whereYear('rbt.tgl_transaksi', $year)
                ->whereIn(DB::raw('LOWER(TRIM(rbt.email_client))'), $emails)
                ->whereBetween(DB::raw('MONTH(rbt.tgl_transaksi)'), [7, 12])
                ->groupBy(DB::raw('LOWER(TRIM(rbt.email_client))'), DB::raw('MONTH(rbt.tgl_transaksi)'))
                ->get()
                ->groupBy('email');

        $targetByPowerhouse = DB::table('target_ph_semesters')
            ->where('year', $year)
            ->where('semester', 2)
            ->whereIn('team_powerhouse', $powerhouses->pluck('name')->all())
            ->pluck('target', 'team_powerhouse');

        $defaultTarget = 2000000000;

        $reportRows = $powerhouses->map(function ($powerhouse) use ($leads, $monthlySettlement, $targetByPowerhouse, $months, $defaultTarget) {
            $leadRows = $leads->where('user_id', $powerhouse->id)->values()->map(function ($lead) use ($monthlySettlement, $months) {
                $emailKey = strtolower(trim((string) $lead->email));
                $settlementByMonth = collect($monthlySettlement->get($emailKey, collect()))
                    ->keyBy('month_no');

                $monthValues = [];
                $achievement = 0;

                foreach ($months as $monthNo => $monthLabel) {
                    $value = (float) ($settlementByMonth->get($monthNo)->total_settlement ?? 0);
                    $monthValues[$monthLabel] = $value;
                    $achievement += $value;
                }

                return [
                    'lead_id' => $lead->id,
                    'lead_name' => $lead->company_name ?: ($lead->email ?: 'Tanpa Nama'),
                    'email' => $lead->email,
                    'usecase' => $lead->usecase,
                    'solusi' => $lead->solusi,
                    'months' => $monthValues,
                    'achievement' => $achievement,
                ];
            });

            $target = (float) ($targetByPowerhouse[$powerhouse->name] ?? $defaultTarget);
            $achievementTotal = $leadRows->sum('achievement');
            $percentage = $target > 0 ? ($achievementTotal / $target) * 100 : 0;

            return [
                'powerhouse_name' => $powerhouse->name,
                'target' => $target,
                'achievement_total' => $achievementTotal,
                'percentage' => $percentage,
                'leads' => $leadRows,
                'month_totals' => collect($months)->mapWithKeys(function ($label) use ($leadRows) {
                    return [$label => $leadRows->sum(fn ($row) => $row['months'][$label] ?? 0)];
                })->all(),
            ];
        });

        return view('admin.powerhouse_2m_report', [
            'reportRows' => $reportRows,
            'months' => array_values($months),
            'reportYear' => $year,
        ]);
    }

    public function updateSolusi(Request $request, LeadsPowerhouse $lead)
    {
        $validated = $request->validate([
            'solusi' => 'required|string|max:5000',
        ], [
            'solusi.required' => 'Solusi wajib diisi.',
        ]);

        $lead->update([
            'solusi' => $validated['solusi'],
        ]);

        return redirect()
            ->route('powerhouse.2m.report')
            ->with('success', 'Solusi untuk lead ' . ($lead->company_name ?? $lead->email ?? 'Powerhouse 2M') . ' berhasil disimpan.');
    }

    public function show(LeadsPowerhouse $lead)
    {
        logUserLogin();

        if (Auth::user()->role !== 'Admin' && (int) $lead->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $lead->load(['user', 'source', 'sector']);

        return view('admin.powerhouse_2m_show', compact('lead'));
    }

    public function edit(LeadsPowerhouse $lead)
    {
        logUserLogin();

        if (Auth::user()->role !== 'Admin' && (int) $lead->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $lead->load(['user', 'source', 'sector']);

        $leadSources = LeadsSource::orderBy('name')->get();
        $sectors = Sector::orderBy('name')->get();
        $powerhouses = Cache::remember('users_list_powerhouse_2m_edit', 3600, fn() => User::whereRaw('UPPER(role) = ?', ['PH'])->orderBy('name')->get());

        return view('admin.powerhouse_2m_edit', compact('lead', 'leadSources', 'sectors', 'powerhouses'));
    }

    public function update(Request $request, LeadsPowerhouse $lead)
    {
        if (Auth::user()->role !== 'Admin' && (int) $lead->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'source_id' => 'required|exists:leads_source,id',
            'sector_id' => 'required|exists:sectors,id',
            'company_name' => 'required|string|max:255',
            'mobile_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^62\d{9,14}$/',
                Rule::unique('leads_powerhouse', 'mobile_phone')->ignore($lead->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('leads_powerhouse', 'email')->ignore($lead->id),
                function ($attribute, $value, $fail) use ($lead) {
                    if (DB::table('mitra_sbp')->where('email_myads', $value)->exists()) {
                        $fail('Email sudah terdaftar sebagai Mitra SBP.');
                    }

                    $emailExistsInLeadsMaster = DB::table('leads_master')
                        ->where('email', $value)
                        ->exists();

                    if ($emailExistsInLeadsMaster && strtolower((string) $lead->email) !== strtolower((string) $value)) {
                        $fail('Email sudah terdaftar di Data Leads & Akun.');
                    }
                },
            ],
            'nama' => 'nullable|string|max:255',
            'myads_account' => 'nullable|string|max:255',
            'usecase' => 'required|string',
            'solusi' => 'nullable|string|max:5000',
        ], [
            'mobile_phone.regex' => 'Nomor HP harus diawali dengan kode negara 62 dan hanya angka (9-12 digit).',
            'mobile_phone.unique' => 'Nomor HP sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ]);

        $payload = [
            'source_id' => $validated['source_id'],
            'sector_id' => $validated['sector_id'],
            'company_name' => $validated['company_name'],
            'mobile_phone' => $validated['mobile_phone'],
            'email' => $validated['email'],
            'nama' => $validated['nama'] ?? null,
            'myads_account' => $validated['myads_account'] ?? null,
            'usecase' => $validated['usecase'],
            'solusi' => $validated['solusi'] ?? null,
        ];

        if (Auth::user()->role === 'Admin') {
            $payload['user_id'] = $validated['user_id'];
        }

        $lead->update($payload);

        return redirect()
            ->route('powerhouse.2m.summary')
            ->with('success_with_schedule', 'Leads 2M untuk ' . $lead->company_name . ' berhasil diupdate.');
    }

    public function summary()
    {
        logUserLogin();

        return view('admin.powerhouse_2m_summary_leads', [
            'powerhouses' => Cache::remember('users_list_powerhouse_2m', 3600, fn() => User::whereRaw('UPPER(role) = ?', ['PH'])->orderBy('name')->get()),
            'sources' => Cache::remember('sources_list_powerhouse_2m', 3600, fn() => LeadsSource::orderBy('name')->get()),
            'regionals' => Cache::remember('regionals_list_powerhouse_2m', 3600, fn() =>
                DB::table('regional_provinces')
                    ->select('regional')
                    ->distinct()
                    ->orderBy('regional')
                    ->pluck('regional')
            ),
            'flagEvents' => Cache::remember('flag_events_list_powerhouse_2m', 3600, fn() =>
                DB::table('leads_powerhouse')
                    ->whereNotNull('flag_event')
                    ->where('flag_event', '!=', '')
                    ->distinct()
                    ->orderBy('flag_event')
                    ->pluck('flag_event')
            ),
        ]);
    }

    public function data(Request $request)
    {
        $search = $request->input('search.value');

        $query = DB::table('leads_powerhouse as lp')
            ->leftJoin('users', 'users.id', '=', 'lp.user_id')
            ->leftJoin('report_balance_top_up as rbt', function ($join) {
                $join->on(DB::raw('LOWER(rbt.email_client)'), '=', DB::raw('LOWER(lp.email)'))
                    ->whereMonth('rbt.tgl_transaksi', now()->month)
                    ->whereYear('rbt.tgl_transaksi', now()->year);
            })
            ->leftJoin('saldo_users as su', 'lp.reg_id', '=', 'su.id_user')
            ->where('users.role', 'PH')
            ->select(
                'lp.id as leads_id',
                'lp.user_id',
                'users.name as user_name',
                'lp.regional',
                'lp.company_name',
                'lp.email',
                'lp.mobile_phone',
                'lp.data_type',
                'lp.flag_event',
                'lp.created_at',
                DB::raw('COALESCE(SUM(rbt.total_settlement_klien), 0) as total_settlement_klien'),
                DB::raw('COALESCE(MAX(su.saldo_utama), 0) as saldo_utama')
            )
            ->groupBy(
                'lp.id',
                'lp.user_id',
                'users.name',
                'lp.regional',
                'lp.company_name',
                'lp.email',
                'lp.mobile_phone',
                'lp.data_type',
                'lp.flag_event',
                'lp.created_at'
            )
            ->orderByRaw('COALESCE(SUM(rbt.total_settlement_klien), 0) DESC')
            ->orderByRaw('COALESCE(MAX(su.saldo_utama), 0) DESC');

        if (Auth::user()->role !== 'Admin') {
            $query->where('lp.user_id', Auth::id());
        }

        if ($request->regional) {
            $query->where('lp.regional', $request->regional);
        }

        if ($request->powerhouse) {
            $query->where('lp.user_id', $request->powerhouse);
        }

        if ($request->flag_event) {
            $query->where('lp.flag_event', $request->flag_event);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('lp.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%$search%")
                    ->orWhere('lp.regional', 'like', "%$search%")
                    ->orWhere('lp.company_name', 'like', "%$search%")
                    ->orWhere('lp.email', 'like', "%$search%")
                    ->orWhere('lp.mobile_phone', 'like', "%$search%")
                    ->orWhere('lp.flag_event', 'like', "%$search%");
            });
        }

        return datatables()->of($query)
            ->addColumn('user_name', fn ($row) => $row->user_name ?? '-')
            ->addColumn('regional', fn ($row) => $row->regional ?? '-')
            ->addColumn('company_name', fn ($row) => $row->company_name ?? '-')
            ->addColumn('email', fn ($row) => $row->email ?? '-')
            ->addColumn('mobile_phone', fn ($row) => $row->mobile_phone ?? '-')
            ->addColumn('data_type', function ($row) {
                $type = $row->data_type ?? '-';
                if ($type === 'Eksisting Akun') {
                    return '<span class="badge badge-success">' . $type . '</span>';
                }
                if ($type === 'Leads') {
                    return '<span class="badge badge-primary">' . $type . '</span>';
                }
                return '<span class="badge badge-secondary">' . $type . '</span>';
            })
            ->addColumn('flag_event', fn ($row) => $row->flag_event ?? '-')
            ->editColumn('created_at', fn ($row) => \Carbon\Carbon::parse($row->created_at)->translatedFormat('d M Y'))
            ->addColumn('total_settlement_klien', fn ($row) => 'Rp ' . number_format($row->total_settlement_klien ?? 0, 0, ',', '.'))
            ->addColumn('saldo_utama', fn ($row) => 'Rp ' . number_format($row->saldo_utama ?? 0, 0, ',', '.'))
            ->addColumn('rekomendasi', function ($row) {
                $saldo = $row->saldo_utama ?? 0;
                if ($saldo >= 1000000) {
                    return '<span class="badge badge-warning">Push Campaign</span>';
                }
                return '<span class="badge badge-danger">Push Topup</span>';
            })
            ->addColumn('aksi', function ($row) {
                return '
                    <a href="' . route('powerhouse.2m.show', $row->leads_id) . '" class="btn btn-sm btn-warning mt-1">
                        <i class="fas fa-search"></i> Read
                    </a>
                    <a href="' . route('powerhouse.2m.edit', $row->leads_id) . '" class="btn btn-sm btn-primary mt-1">
                        <i class="fas fa-pencil-alt"></i> Update
                    </a>
                ';
            })
            ->rawColumns(['data_type', 'rekomendasi', 'aksi'])
            ->make(true);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = DB::table('leads_powerhouse as lp')
            ->leftJoin('users', 'users.id', '=', 'lp.user_id')
            ->leftJoin('report_balance_top_up as rbt', function ($join) {
                $join->on(DB::raw('LOWER(rbt.email_client)'), '=', DB::raw('LOWER(lp.email)'))
                    ->whereMonth('rbt.tgl_transaksi', now()->month)
                    ->whereYear('rbt.tgl_transaksi', now()->year);
            })
            ->leftJoin('saldo_users as su', 'lp.reg_id', '=', 'su.id_user')
            ->where('users.role', 'PH')
            ->select(
                'users.name as user_name',
                'lp.regional',
                'lp.company_name',
                'lp.email',
                'lp.mobile_phone',
                'lp.data_type',
                'lp.flag_event',
                'lp.created_at',
                DB::raw('COALESCE(SUM(rbt.total_settlement_klien), 0) as total_settlement_klien'),
                DB::raw('COALESCE(MAX(su.saldo_utama), 0) as saldo_utama')
            )
            ->groupBy(
                'users.name',
                'lp.regional',
                'lp.company_name',
                'lp.email',
                'lp.mobile_phone',
                'lp.data_type',
                'lp.flag_event',
                'lp.created_at'
            )
            ->orderByRaw('COALESCE(SUM(rbt.total_settlement_klien), 0) DESC')
            ->orderByRaw('COALESCE(MAX(su.saldo_utama), 0) DESC');

        if (Auth::user()->role !== 'Admin') {
            $query->where('lp.user_id', Auth::id());
        }

        if ($request->powerhouse) {
            $query->where('lp.user_id', $request->powerhouse);
        }

        if ($request->regional) {
            $query->where('lp.regional', $request->regional);
        }

        if ($request->flag_event) {
            $query->where('lp.flag_event', $request->flag_event);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('lp.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        $filename = 'leads_powerhouse_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = [
            'Powerhouse',
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
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns);

            foreach ($query->cursor() as $row) {
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
                    'Rp ' . number_format($row->total_settlement_klien ?? 0, 0, ',', '.'),
                    'Rp ' . number_format($saldoUtama, 0, ',', '.'),
                    $rekomendasi,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(Request $request)
    {
        $rules = [
            'user_id' => 'required|exists:users,id',
            'source_id' => 'required|exists:leads_source,id',
            'sector_id' => 'required|exists:sectors,id',
            'company_name' => 'required|string|max:255',
            'mobile_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^62\d{9,14}$/',
                'unique:leads_powerhouse,mobile_phone',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:leads_powerhouse,email',
                function ($attribute, $value, $fail) {
                    if (DB::table('mitra_sbp')->where('email_myads', $value)->exists()) {
                        $fail('Email sudah terdaftar sebagai Mitra SBP.');
                    }
                    if (DB::table('leads_master')->where('email', $value)->exists()) {
                        $fail('Email sudah terdaftar di Data Leads & Akun.');
                    }
                },
            ],
            'nama' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'myads_account' => 'nullable|string|max:255',
            'usecase' => 'required|string',
            'schedule_lokasi' => 'nullable|string|max:255',
            'schedule_tanggal' => 'nullable|date',
            'schedule_waktu_mulai' => 'nullable|date_format:H:i',
            'schedule_waktu_selesai' => 'nullable|date_format:H:i',
            'schedule_keterangan' => 'nullable|string|max:1000',
        ];

        $messages = [
            'mobile_phone.regex' => 'Nomor HP harus diawali dengan kode negara 62 dan hanya angka (9-12 digit).',
            'mobile_phone.unique' => 'Nomor HP sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ];

        try {
            $validated = $request->validate($rules, $messages);

            DB::beginTransaction();

            $statusValue = 1;

            $lead = LeadsPowerhouse::create([
                'user_id' => $validated['user_id'],
                'source_id' => $validated['source_id'],
                'sector_id' => $validated['sector_id'],
                'company_name' => $validated['company_name'] ?? '-',
                'mobile_phone' => $validated['mobile_phone'],
                'email' => $validated['email'] ?? null,
                'status' => $statusValue,
                'nama' => $validated['nama'] ?? null,
                'address' => $validated['address'] ?? null,
                'myads_account' => $validated['myads_account'] ?? null,
                'data_type' => 'Leads',
                'usecase' => $validated['usecase'],
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
                    'keterangan' => $validated['schedule_keterangan'] ?? 'Kunjungan dari leads 2M: ' . $validated['company_name'],
                    'warna' => '#8b5cf6',
                    'leads_id' => $lead->id,
                    'type' => 'Powerhouse',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $scheduleDate = \Carbon\Carbon::parse($validated['schedule_tanggal'])->translatedFormat('l, d F Y');
                $scheduleInfo = "Jadwal: {$scheduleDate} ({$validated['schedule_waktu_mulai']} - {$validated['schedule_waktu_selesai']})";
            }

            DB::commit();

            $successMessage = 'Leads 2M untuk ' . $validated['company_name'] . ' berhasil ditambahkan.';
            if ($scheduleInfo) {
                $successMessage .= "\n" . $scheduleInfo;
            }

            return redirect()->route('powerhouse.2m.input-leads')->with('success_with_schedule', $successMessage);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan Leads 2M: ' . $e->getMessage());
        }
    }
}
