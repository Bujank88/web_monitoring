<?php

namespace App\Http\Controllers;

use App\Models\LeadsCanvasserSbp;
use App\Models\SbpReferral;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PilotSbpController extends Controller
{
    protected const SBP_REFERRAL_SENDER_ID = 'REG-DO-000000662035';
    protected const SBP_MONITORING_EMAIL = 'yung_fen@rajawalicellular.co.id';

    public function index()
    {
        logUserLogin();

        return view('pilot_sbp.index', [
            'pageTitle' => 'Pilot SBP to SME',
        ]);
    }

    public function topupReferralIndex()
    {
        logUserLogin();

        $month = request()->get('month', now()->format('Y-m'));
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

        return view('pilot_sbp.topup_referral', [
            'pageTitle' => 'TopUp Referral SBP',
            'month' => $month,
            'months' => $months,
            'channels' => $this->getSbpReferralChannels(),
        ]);
    }

    public function monitoringSaldoIndex(Request $request)
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

        $history = $this->getMonitoringSaldoHistory($month);

        return view('pilot_sbp.monitoring_saldo', [
            'pageTitle' => 'Monitoring Saldo',
            'month' => $month,
            'months' => $months,
            'monitoringEmail' => self::SBP_MONITORING_EMAIL,
            'senderId' => self::SBP_REFERRAL_SENDER_ID,
            'remainingBalance' => $history['remaining_balance'],
            'openingBalance' => $history['opening_balance'],
            'totalIn' => $history['total_in'],
            'totalOut' => $history['total_out'],
            'endingBalance' => $history['ending_balance'],
            'historyRows' => $history['rows'],
        ]);
    }

    public function topupReferralData(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $rows = $this->getSbpReferralTopupRows($month);

            return datatables()->of(collect($rows))->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in topupReferralData: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function topupReferralDetailData(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));
            $rows = $this->getSbpReferralTopupDetailRows($month);

            return datatables()->of(collect($rows))
                ->addIndexColumn()
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('Error in topupReferralDetailData: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function reportCampaignIndex(Request $request)
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

        return view('pilot_sbp.report_campaign', [
            'pageTitle' => 'Report Campaign',
            'month' => $month,
            'months' => $months,
        ]);
    }

    public function reportCampaignData(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $query = $this->pilotSbpCampaignBaseQuery($month);

        $summaryTotal = (clone $query)->count();

        return datatables()->of($query)
            ->with('summary', [
                'total_campaign' => $summaryTotal,
            ])
            ->editColumn('balance_terpakai', function ($row) {
                return 'Rp ' . number_format((float) $row->balance_terpakai, 0, ',', '.');
            })
            ->make(true);
    }

    public function exportReportCampaign(Request $request)
    {
        try {
            $month = $request->get('month', now()->format('Y-m'));

            $data = $this->pilotSbpCampaignBaseQuery($month)
                ->orderByRaw('COALESCE(campaign.broadcast_date, campaign.tanggal_iklan) DESC')
                ->get();

            if ($data->isEmpty()) {
                return redirect()->back()->with('error', 'Tidak ada data untuk di-export');
            }

            $filename = 'Report_Campaign_Pilot_SBP_' . $month . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return response()->stream(function () use ($data) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

                fputcsv($handle, [
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
                    'Sumber',
                ]);

                foreach ($data as $row) {
                    fputcsv($handle, [
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
                        $row->source_label,
                    ]);
                }

                fclose($handle);
            }, 200, $headers);
        } catch (\Throwable $e) {
            \Log::error('Export Pilot SBP Campaign Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data report campaign');
        }
    }

    public function referralIndex()
    {
        logUserLogin();

        return view('pilot_sbp.referral_index', [
            'pageTitle' => 'Referral Canvasser / Agent',
            'canvasserSbpUsers' => $this->getCanvasserSbpUsers(),
        ]);
    }

    public function referralData(Request $request)
    {
        $query = SbpReferral::query()
            ->leftJoin('users', 'sbp_referrals.user_id', '=', 'users.id')
            ->selectRaw('sbp_referrals.id, sbp_referrals.user_id, sbp_referrals.name as referral_name, sbp_referrals.user_email, sbp_referrals.referral_code, sbp_referrals.status, sbp_referrals.created_at, users.name as canvasser_name')
            ->orderByDesc('id');

        return datatables()->of($query)
            ->addIndexColumn()
            ->editColumn('status', function ($row) {
                $isActive = strtolower((string) $row->status) === 'active';

                return $isActive
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Non Active</span>';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at
                    ? Carbon::parse($row->created_at)->format('d-m-Y H:i')
                    : '-';
            })
            ->addColumn('action', function ($row) {
                $isActive = strtolower((string) $row->status) === 'active';
                $targetStatus = $isActive ? 'non_active' : 'active';
                $buttonLabel = $isActive ? 'Non Active' : 'Active';
                $buttonClass = $isActive ? 'btn-danger' : 'btn-success';

                return '<button type="button" class="btn btn-sm ' . $buttonClass . ' btnToggleReferralStatus" '
                    . 'data-id="' . e($row->id) . '" '
                    . 'data-name="' . e($row->referral_name) . '" '
                    . 'data-status="' . e($targetStatus) . '">'
                    . $buttonLabel
                    . '</button>';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function generateReferralCode(Request $request)
    {
        $name = (string) $request->get('name', '');

        return response()->json([
            'referral_code' => $this->generateUniqueReferralCode($name),
        ]);
    }

    public function storeReferral(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_id' => 'required|integer',
            'referral_code' => 'required|string|max:50|unique:sbp_referrals,referral_code',
        ], [
            'name.required' => 'Nama referral wajib diisi.',
            'user_id.required' => 'User Canvasser SBP wajib dipilih.',
            'referral_code.required' => 'Code referral wajib diisi.',
            'referral_code.unique' => 'Code referral sudah digunakan.',
        ]);

        $selectedUser = $this->getCanvasserSbpUsers()
            ->firstWhere('id', (int) $validated['user_id']);

        if (!$selectedUser) {
            return response()->json([
                'message' => 'User Canvasser SBP tidak valid.',
                'errors' => [
                    'user_id' => ['User Canvasser SBP tidak valid.'],
                ],
            ], 422);
        }

        $existingReferral = SbpReferral::query()
            ->where('user_id', $selectedUser->id)
            ->exists();

        if ($existingReferral) {
            return response()->json([
                'message' => 'User Canvasser SBP tersebut sudah memiliki referral.',
                'errors' => [
                    'user_id' => ['User Canvasser SBP tersebut sudah memiliki referral.'],
                ],
            ], 422);
        }

        SbpReferral::create([
            'user_id' => $selectedUser->id,
            'name' => trim($validated['name']),
            'user_email' => trim((string) $selectedUser->email),
            'referral_code' => strtoupper(trim($validated['referral_code'])),
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Referral SBP berhasil ditambahkan.',
        ]);
    }

    public function updateReferralStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,non_active',
        ], [
            'status.required' => 'Status referral wajib dipilih.',
            'status.in' => 'Status referral tidak valid.',
        ]);

        $updated = SbpReferral::query()
            ->where('id', $id)
            ->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Referral SBP tidak ditemukan atau tidak ada perubahan status.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => $validated['status'] === 'active'
                ? 'Referral SBP berhasil diaktifkan.'
                : 'Referral SBP berhasil dinonaktifkan.',
        ]);
    }

    public function dataLeadsIndex()
    {
        logUserLogin();

        $columns = $this->getDataGoogleMapsDisplayColumns();

        return view('pilot_sbp.data_leads', [
            'pageTitle' => 'Data Leads',
            'columns' => $columns,
            'jenisNomorColumn' => $this->resolveDataGoogleMapsJenisNomorColumn($columns),
            'leadRows' => $this->getDataGoogleMapsRows(request()),
        ]);
    }

    public function dataLeadsData(Request $request)
    {
        try {
            $columns = $this->getDataGoogleMapsDisplayColumns();

            $query = DB::table('data_google_maps')->select($columns);
            $this->applyDataGoogleMapsFilters($query, $request, $columns);

            return datatables()->of($query)
                ->addIndexColumn()
                ->make(true);
        } catch (\Throwable $e) {
            \Log::error('Error in dataLeadsData: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'draw' => (int) $request->get('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportDataLeads(Request $request)
    {
        $columns = $this->getDataGoogleMapsDisplayColumns();

        $query = DB::table('data_google_maps')->select($columns);
        $this->applyDataGoogleMapsFilters($query, $request, $columns);
        $rows = $query->get();

        $filename = 'data_leads_google_maps_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, array_map(function ($column) {
                return ucwords(str_replace('_', ' ', $column));
            }, $columns));

            foreach ($rows as $row) {
                fputcsv($handle, collect($columns)->map(function ($column) use ($row) {
                    return data_get($row, $column);
                })->all());
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function referralLeadMappingIndex()
    {
        logUserLogin();

        return view('pilot_sbp.referral_lead_mapping', [
            'pageTitle' => 'Data Leads by Referral',
            'canFilterAll' => $this->canViewAllReferralLeads(),
            'referralOptions' => $this->getReferralFilterOptions(),
            'canvasserOptions' => $this->getCanvasserFilterOptions(),
            'leadRows' => $this->getReferralLeadRows(),
        ]);
    }

    public function referralLeadMappingData(Request $request)
    {
        try {
            $query = LeadsCanvasserSbp::query()
                ->from('leads_canvasser_sbp as lcs')
                ->leftJoin('users as u', 'lcs.created_by', '=', 'u.id')
                ->leftJoin('sbp_referrals as sr', 'lcs.referral_id', '=', 'sr.id')
                ->select([
                    'lcs.id',
                    'lcs.referral_id',
                    'lcs.referral_code',
                    'lcs.referral_name',
                    'lcs.company_name',
                    'lcs.email_myads',
                    'lcs.mobile_phone',
                    'lcs.created_by',
                    'lcs.created_at',
                    'u.name as canvasser_name',
                    'u.email as canvasser_email',
                    'sr.user_email as referral_user_email',
                ])
                ->orderByDesc('lcs.id');

            if (!$this->canViewAllReferralLeads()) {
                $query->whereRaw('LOWER(TRIM(sr.user_email)) = ?', [strtolower(trim((string) Auth::user()->email))]);
            } else {
                if ($request->filled('referral_id')) {
                    $query->where('lcs.referral_id', (int) $request->referral_id);
                }

                if ($request->filled('created_by')) {
                    $query->where('lcs.created_by', (int) $request->created_by);
                }
            }

            if ($request->filled('search_company')) {
                $searchCompany = trim((string) $request->search_company);
                $query->where('lcs.company_name', 'like', '%' . $searchCompany . '%');
            }

            return datatables()->of($query)
                ->addIndexColumn()
                ->make(true);
        } catch (\Throwable $e) {
            \Log::error('Error in referralLeadMappingData: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'draw' => (int) $request->get('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function inputLeadsIndex()
    {
        logUserLogin();

        return view('pilot_sbp.input_leads', [
            'pageTitle' => 'Input Leads',
            'referrals' => $this->getReferralOptionsForLoggedInUser(),
        ]);
    }

    public function storeInputLeads(Request $request)
    {
        $validated = $request->validate([
            'referral_id' => 'required|integer',
            'company_name' => 'required|string|max:255',
            'email_myads' => 'required|email|max:255',
            'mobile_phone' => 'required|string|max:50',
        ], [
            'referral_id.required' => 'Code Referral - Nama Referral wajib dipilih.',
            'company_name.required' => 'Nama Perusahaan wajib diisi.',
            'email_myads.required' => 'Email Myads wajib diisi.',
            'email_myads.email' => 'Format Email Myads tidak valid.',
            'mobile_phone.required' => 'No Telp wajib diisi.',
        ]);

        $referral = $this->getReferralOptionsForLoggedInUser()
            ->firstWhere('id', (int) $validated['referral_id']);

        if (!$referral) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'referral_id' => 'Referral tidak valid untuk user yang sedang login.',
                ]);
        }

        LeadsCanvasserSbp::create([
            'referral_id' => $referral->id,
            'referral_code' => strtoupper(trim((string) $referral->referral_code)),
            'referral_name' => trim((string) $referral->name),
            'company_name' => trim($validated['company_name']),
            'email_myads' => strtolower(trim($validated['email_myads'])),
            'mobile_phone' => trim($validated['mobile_phone']),
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('pilot-sbp-sme.input-leads')
            ->with('success', 'Leads Canvasser SBP berhasil disimpan.');
    }

    protected function generateUniqueReferralCode(string $name): string
    {
        $prefix = $this->buildReferralPrefixFromName($name);

        do {
            $code = $prefix . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        } while (SbpReferral::query()->where('referral_code', $code)->exists());

        return $code;
    }

    protected function buildReferralPrefixFromName(string $name): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9\\s]/', ' ', $name));
        $parts = preg_split('/\\s+/', trim($normalized), -1, PREG_SPLIT_NO_EMPTY);

        $prefix = collect($parts)
            ->map(function ($part) {
                return substr($part, 0, 2);
            })
            ->implode('');

        $prefix = substr($prefix, 0, 6);

        if ($prefix === '') {
            return 'REF';
        }

        return strlen($prefix) < 3
            ? str_pad($prefix, 3, 'X')
            : $prefix;
    }

    protected function getCanvasserSbpUsers()
    {
        return User::query()
            ->select('id', 'name', 'email')
            ->whereRaw('UPPER(TRIM(role)) = ?', ['CANVASSER SBP'])
            ->orderBy('name')
            ->get();
    }

    protected function getReferralOptionsForLoggedInUser()
    {
        $query = SbpReferral::query()
            ->select('id', 'name', 'referral_code', 'user_email')
            ->where('status', 'active')
            ->orderBy('name');

        if (!$this->canViewAllReferralLeads()) {
            $query->whereRaw('LOWER(TRIM(user_email)) = ?', [strtolower(trim((string) Auth::user()->email))]);
        }

        return $query->get();
    }

    protected function canViewAllReferralLeads(): bool
    {
        $role = strtoupper(trim((string) optional(Auth::user())->role));

        return in_array($role, ['ADMIN', 'SBP'], true);
    }

    protected function getReferralFilterOptions()
    {
        return SbpReferral::query()
            ->select('id', 'name', 'referral_code')
            ->orderBy('name')
            ->get();
    }

    protected function getCanvasserFilterOptions()
    {
        return User::query()
            ->select('id', 'name', 'email')
            ->whereIn('id', function ($query) {
                $query->select('created_by')
                    ->from('leads_canvasser_sbp')
                    ->whereNotNull('created_by');
            })
            ->orderBy('name')
            ->get();
    }

    protected function resolveDataGoogleMapsJenisNomorColumn(array $columns): ?string
    {
        foreach (['jenis_nomor', 'jenis_no', 'type_nomor', 'kategori_nomor'] as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function applyDataGoogleMapsFilters($query, Request $request, array $columns): void
    {
        $jenisNomorColumn = $this->resolveDataGoogleMapsJenisNomorColumn($columns);

        if ($jenisNomorColumn && $request->filled('jenis_nomor')) {
            $query->where($jenisNomorColumn, $request->get('jenis_nomor'));
        }
    }

    protected function getDataGoogleMapsRows(Request $request)
    {
        $columns = $this->getDataGoogleMapsDisplayColumns();

        $query = DB::table('data_google_maps')->select($columns);
        $this->applyDataGoogleMapsFilters($query, $request, $columns);

        return $query->get();
    }

    protected function getDataGoogleMapsDisplayColumns(): array
    {
        $allColumns = collect(Schema::getColumnListing('data_google_maps'))
            ->reject(function ($column) {
                return in_array($column, ['id', 'created_at', 'updated_at'], true);
            })
            ->values()
            ->all();

        $preferredColumns = [
            'kabupaten_kota',
            'lini_bisnis',
            'nama_usaha',
            'rating',
            'total_review',
            'alamat',
            'no_handphone',
            'link_google_maps',
            'jenis_nomor',
        ];

        $filteredColumns = collect($preferredColumns)
            ->filter(function ($column) use ($allColumns) {
                return in_array($column, $allColumns, true);
            })
            ->values()
            ->all();

        return !empty($filteredColumns) ? $filteredColumns : $allColumns;
    }

    protected function getReferralLeadRows()
    {
        $query = LeadsCanvasserSbp::query()
            ->from('leads_canvasser_sbp as lcs')
            ->leftJoin('users as u', 'lcs.created_by', '=', 'u.id')
            ->leftJoin('sbp_referrals as sr', 'lcs.referral_id', '=', 'sr.id')
            ->select([
                'lcs.id',
                'lcs.referral_id',
                'lcs.referral_code',
                'lcs.referral_name',
                'lcs.company_name',
                'lcs.email_myads',
                'lcs.mobile_phone',
                'u.name as canvasser_name',
                'u.email as canvasser_email',
                'sr.user_email as referral_user_email',
            ])
            ->orderByDesc('lcs.id');

        if (!$this->canViewAllReferralLeads()) {
            $query->whereRaw('LOWER(TRIM(sr.user_email)) = ?', [strtolower(trim((string) Auth::user()->email))]);
        }

        return $query->get();
    }

    protected function getSbpReferralChannels(): array
    {
        $channels = [[
            'key' => 'sbp',
            'label' => 'SBP',
            'color' => '#d1ecf1',
            'referral_code' => null,
        ]];

        $referrals = SbpReferral::query()
            ->select('id', 'name', 'referral_code')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        foreach ($referrals as $referral) {
            $channels[] = [
                'key' => 'ref_' . $referral->id,
                'label' => $referral->name,
                'color' => '#fff3cd',
                'referral_code' => strtoupper((string) $referral->referral_code),
            ];
        }

        return $channels;
    }

    protected function getSbpLeadReferralMap()
    {
        return LeadsCanvasserSbp::query()
            ->select('referral_id', 'referral_name', 'referral_code', 'email_myads')
            ->whereNotNull('email_myads')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(function ($lead) {
                $email = strtolower(trim((string) $lead->email_myads));

                if ($email === '') {
                    return [];
                }

                return [
                    $email => [
                        'referral_id' => $lead->referral_id,
                        'referral_name' => trim((string) $lead->referral_name),
                        'referral_code' => strtoupper(trim((string) $lead->referral_code)),
                    ],
                ];
            });
    }

    protected function getSbpReferralTopupRows(string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $startDate = $monthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00');
        $endDate = $monthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59');

        $channels = $this->getSbpReferralChannels();
        $channelKeys = collect($channels)->pluck('key')->values()->all();
        $channelByReferralId = collect($channels)->keyBy(function ($channel) {
            return $channel['key'];
        });
        $leadReferralMap = $this->getSbpLeadReferralMap();

        $grouped = [];
        $periodCursor = $monthDate->copy()->startOfMonth();
        $today = Carbon::today();
        $periodEnd = $monthDate->month === $today->month && $monthDate->year === $today->year
            ? $today->copy()
            : $monthDate->copy()->endOfMonth();

        while ($periodCursor->lte($periodEnd)) {
            $dateKey = $periodCursor->format('Y-m-d');
            $grouped[$dateKey] = [];

            foreach ($channelKeys as $key) {
                $grouped[$dateKey][$key] = [
                    'settlement' => 0,
                    'users' => [],
                ];
            }

            $periodCursor->addDay();
        }

        $transactions = $this->getMatchedSbpTransactions($startDate, $endDate);

        foreach ($transactions as $transaction) {
            $dateKey = (string) $transaction->trx_date;
            $emailClient = strtolower(trim((string) $transaction->email_client));
            $leadReferral = $leadReferralMap->get($emailClient);
            $channelKey = $leadReferral && !empty($leadReferral['referral_id'])
                ? 'ref_' . $leadReferral['referral_id']
                : 'sbp';

            if (!$channelByReferralId->has($channelKey)) {
                $channelKey = 'sbp';
            }

            if (!isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [];
                foreach ($channelKeys as $key) {
                    $grouped[$dateKey][$key] = [
                        'settlement' => 0,
                        'users' => [],
                    ];
                }
            }

            $grouped[$dateKey][$channelKey]['settlement'] += (float) $transaction->deposit_amount;
            $grouped[$dateKey][$channelKey]['users'][] = $emailClient;
        }

        ksort($grouped);

        $rows = [];
        $grandTotals = [];
        foreach ($channelKeys as $key) {
            $grandTotals[$key] = [
                'settlement' => 0,
                'users' => [],
            ];
        }

        foreach ($grouped as $dateKey => $perChannel) {
            $row = [
                'date' => Carbon::parse($dateKey)->translatedFormat('d F Y'),
            ];

            $totalSettlement = 0;
            $totalUsers = [];

            foreach ($channelKeys as $key) {
                $uniqueUsers = array_values(array_unique(array_filter($perChannel[$key]['users'])));
                $settlement = (float) $perChannel[$key]['settlement'];

                $row[$key . '_user'] = count($uniqueUsers);
                $row[$key . '_settle'] = number_format($settlement, 0, ',', '.');

                $totalSettlement += $settlement;
                $totalUsers = array_merge($totalUsers, $uniqueUsers);
                $grandTotals[$key]['settlement'] += $settlement;
                $grandTotals[$key]['users'] = array_merge($grandTotals[$key]['users'], $uniqueUsers);
            }

            $row['total_user'] = count(array_unique($totalUsers));
            $row['total'] = number_format($totalSettlement, 0, ',', '.');
            $rows[] = $row;
        }

        if (!empty($rows)) {
            $totalRow = [
                'date' => 'Total Keseluruhan',
            ];

            $allUsers = [];
            $allSettlement = 0;

            foreach ($channelKeys as $key) {
                $uniqueUsers = array_values(array_unique(array_filter($grandTotals[$key]['users'])));
                $settlement = (float) $grandTotals[$key]['settlement'];

                $totalRow[$key . '_user'] = count($uniqueUsers);
                $totalRow[$key . '_settle'] = number_format($settlement, 0, ',', '.');

                $allUsers = array_merge($allUsers, $uniqueUsers);
                $allSettlement += $settlement;
            }

            $totalRow['total_user'] = count(array_unique($allUsers));
            $totalRow['total'] = number_format($allSettlement, 0, ',', '.');
            $rows[] = $totalRow;
        }

        return $rows;
    }

    protected function getSbpReferralTopupDetailRows(string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $startDate = $monthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00');
        $endDate = $monthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59');

        $leadReferralMap = $this->getSbpLeadReferralMap();

        return $this->getMatchedSbpTransactions($startDate, $endDate)
            ->map(function ($transaction) use ($leadReferralMap) {
                $emailClient = strtolower(trim((string) $transaction->email_client));
                $leadReferral = $leadReferralMap->get($emailClient);
                $channelLabel = !empty($leadReferral['referral_name'])
                    ? $leadReferral['referral_name']
                    : 'SBP';

                return [
                    'paid_date' => $transaction->payment_datetime
                        ? Carbon::parse($transaction->payment_datetime)->format('d-m-Y')
                        : '-',
                    'customer_email' => $transaction->email_client ?: '-',
                    'channel' => $channelLabel,
                    'amount' => number_format((float) $transaction->deposit_amount, 0, ',', '.'),
                ];
            })
            ->values()
            ->all();
    }

    protected function getMatchedSbpTransactions(string $startDate, string $endDate)
    {
        return DB::table('transaksi_balance_transfer as tbt')
            ->select(
                DB::raw('DATE(tbt.tanggal) as trx_date'),
                'tbt.tanggal as payment_datetime',
                'tbt.id_klien_pengirim',
                'tbt.email_penerima as email_client',
                DB::raw('\'\' as voucher_code'),
                DB::raw('CAST(COALESCE(tbt.jumlah, 0) AS DECIMAL(15,2)) as deposit_amount')
            )
            ->whereRaw('LOWER(COALESCE(tbt.status, \'\')) = ?', ['paid'])
            ->where('tbt.id_klien_pengirim', self::SBP_REFERRAL_SENDER_ID)
            ->whereBetween('tbt.tanggal', [$startDate, $endDate])
            ->whereNotNull('tbt.email_penerima')
            ->orderByDesc('tbt.tanggal')
            ->get();
    }

    protected function pilotSbpCampaignBaseQuery(string $month)
    {
        [$year, $monthNum] = explode('-', $month);
        $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

        $transferEmailsSubQuery = DB::table('transaksi_balance_transfer as tbt')
            ->selectRaw('LOWER(TRIM(tbt.email_penerima)) as email_key')
            ->where('tbt.id_klien_pengirim', self::SBP_REFERRAL_SENDER_ID)
            ->whereNotNull('tbt.email_penerima')
            ->where('tbt.email_penerima', '!=', '')
            ->groupBy(DB::raw('LOWER(TRIM(tbt.email_penerima))'));

        return DB::query()->fromSub(
            DB::table('data_registarsi_status_approveorreject as dsa')
                ->joinSub($transferEmailsSubQuery, 'transfer_emails', function ($join) {
                    $join->on(
                        DB::raw('LOWER(TRIM(dsa.email))'),
                        '=',
                        'transfer_emails.email_key'
                    );
                })
                ->join('myads_request_soadb as b', 'dsa.user_id', '=', 'b.user_id')
                ->whereNotNull('dsa.user_id')
                ->where('dsa.user_id', '!=', '')
                ->whereBetween('b.created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(COALESCE(b.broadcast_date, b.created_at)) as tanggal_iklan'),
                    'b.broadcast_date',
                    'dsa.email as email',
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
                    DB::raw("'Transfer SBP' as source_label")
                ),
            'campaign'
        );
    }

    protected function getMonitoringSaldoHistory(string $month): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $startDate = $monthDate->copy()->startOfMonth()->format('Y-m-d 00:00:00');
        $endDate = $monthDate->copy()->endOfMonth()->format('Y-m-d 23:59:59');
        $targetEmail = strtolower(trim(self::SBP_MONITORING_EMAIL));

        $openingIn = (float) DB::table('report_balance_top_up')
            ->whereRaw('LOWER(TRIM(email_client)) = ?', [$targetEmail])
            ->where('tgl_transaksi', '<', $startDate)
            ->sum(DB::raw('CAST(COALESCE(amount, 0) AS DECIMAL(15,2))'));

        $openingOut = (float) DB::table('transaksi_balance_transfer')
            ->where('id_klien_pengirim', self::SBP_REFERRAL_SENDER_ID)
            ->where('tanggal', '<', $startDate)
            ->sum(DB::raw('CAST(COALESCE(jumlah, 0) AS DECIMAL(15,2))'));

        $openingBalance = $openingIn - $openingOut;

        $remainingIn = (float) DB::table('report_balance_top_up')
            ->whereRaw('LOWER(TRIM(email_client)) = ?', [$targetEmail])
            ->sum(DB::raw('CAST(COALESCE(amount, 0) AS DECIMAL(15,2))'));

        $remainingOut = (float) DB::table('transaksi_balance_transfer')
            ->where('id_klien_pengirim', self::SBP_REFERRAL_SENDER_ID)
            ->sum(DB::raw('CAST(COALESCE(jumlah, 0) AS DECIMAL(15,2))'));

        $remainingBalance = $remainingIn - $remainingOut;

        $incomingRows = DB::table('report_balance_top_up')
            ->select([
                DB::raw("'Masuk' as transaction_type"),
                DB::raw("'Top Up' as source"),
                'tgl_transaksi as transaction_datetime',
                'email_client as reference_email',
                DB::raw('CAST(COALESCE(amount, 0) AS DECIMAL(15,2)) as amount_in'),
                DB::raw('CAST(0 AS DECIMAL(15,2)) as amount_out'),
            ])
            ->whereRaw('LOWER(TRIM(email_client)) = ?', [$targetEmail])
            ->whereBetween('tgl_transaksi', [$startDate, $endDate])
            ->get();

        $outgoingRows = DB::table('transaksi_balance_transfer')
            ->select([
                DB::raw("'Keluar' as transaction_type"),
                DB::raw("'Balance Transfer' as source"),
                'tanggal as transaction_datetime',
                'email_penerima as reference_email',
                DB::raw('CAST(0 AS DECIMAL(15,2)) as amount_in'),
                DB::raw('CAST(COALESCE(jumlah, 0) AS DECIMAL(15,2)) as amount_out'),
            ])
            ->where('id_klien_pengirim', self::SBP_REFERRAL_SENDER_ID)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get();

        $rows = $incomingRows
            ->concat($outgoingRows)
            ->sortBy(function ($row) {
                return Carbon::parse($row->transaction_datetime)->timestamp;
            })
            ->values();

        $runningBalance = $openingBalance;
        $totalIn = 0;
        $totalOut = 0;

        $formattedRows = $rows->map(function ($row) use (&$runningBalance, &$totalIn, &$totalOut) {
            $amountIn = (float) $row->amount_in;
            $amountOut = (float) $row->amount_out;

            $totalIn += $amountIn;
            $totalOut += $amountOut;
            $runningBalance += $amountIn - $amountOut;

            return [
                'transaction_date' => $row->transaction_datetime
                    ? Carbon::parse($row->transaction_datetime)->format('d-m-Y H:i')
                    : '-',
                'transaction_type' => $row->transaction_type,
                'source' => $row->source,
                'reference_email' => $row->reference_email ?: '-',
                'amount_in' => $amountIn,
                'amount_out' => $amountOut,
                'running_balance' => $runningBalance,
            ];
        })->all();

        return [
            'remaining_balance' => $remainingBalance,
            'opening_balance' => $openingBalance,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'ending_balance' => $openingBalance + $totalIn - $totalOut,
            'rows' => $formattedRows,
        ];
    }
}
