<?php

namespace App\Http\Controllers;

use App\Models\AkunPanenPoinV5;
use App\Models\User;
use App\Models\UserPanenPoinV5;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PanenPoinV5Controller extends Controller
{
    private const PROGRAM_PERIOD_START = '2026-11-01';
    private const PROGRAM_PERIOD_END = '2026-12-06';
    private const PROGRAM_PERIOD_LABEL = '01 Nov 2026 - 06 Des 2026';
    private const PREVIOUS_PERIOD_START = '2026-09-01';
    private const PREVIOUS_PERIOD_END = '2026-10-09';
    private const PREVIOUS_PERIOD_LABEL = '01 Sep 2026 - 09 Okt 2026';

    public function index()
    {
        logUserLogin();
        return view('panenpoinv2.inputdatapoin', [
            'routePrefix' => 'panenpoinv5',
            'programLabel' => 'Panen Poin V5',
        ]);
    }

    public function listAkun()
    {
        logUserLogin();
        return view('panenpoinv2.listakun', [
            'routePrefix' => 'panenpoinv5',
            'programLabel' => 'Panen Poin V5',
        ]);
    }

    public function report()
    {
        logUserLogin();
        return view('panenpoinv2.reportpoin', [
            'months' => $this->getReportPeriods(),
            'pointPeriodDescription' => 'Poin dihitung untuk periode ' . self::PROGRAM_PERIOD_LABEL . ', tanpa membawa saldo poin sebelumnya.',
            'routePrefix' => 'panenpoinv5',
            'programLabel' => 'Panen Poin V5',
        ]);
    }

    public function reportCanvasser()
    {
        logUserLogin();
        return view('panenpoinv2.report-canvasser-panenpoinv2', [
            'months' => $this->getReportPeriods(),
            'routePrefix' => 'panenpoinv5',
            'programLabel' => 'Panen Poin V5',
        ]);
    }

    public function reportPowerhouse()
    {
        logUserLogin();
        return view('panenpoinv2.report-ph-panenpoinv2', [
            'months' => $this->getReportPeriods(),
            'routePrefix' => 'panenpoinv5',
            'programLabel' => 'Panen Poin V5',
        ]);
    }

    public function getAkunData(Request $request)
    {
        $query = AkunPanenPoinV5::query();

        if (Auth::user()->role === 'cvsr') {
            $query->where('user_id', Auth::id());
        }

        $data = $query->select('id', 'nama_akun', 'email_client', 'user_id', 'created_at')
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $nomorHp = DB::table('user_panen_poin_v5')
                    ->where('user_id', $item->user_id)
                    ->where('akun_myads_pelanggan', $item->email_client)
                    ->value('nomor_hp_pelanggan');

                if (!$nomorHp) {
                    $nomorHp = DB::table('leads_master')
                        ->where('user_id', $item->user_id)
                        ->where('email', $item->email_client)
                        ->value('mobile_phone');
                }

                return [
                    'id' => $item->id,
                    'nama_akun' => $item->nama_akun,
                    'email_client' => $item->email_client,
                    'nomor_hp' => $nomorHp ?? '-',
                    'nama_canvasser' => (int) $item->user_id <= 0 ? 'Agency' : ($item->user->name ?? '-'),
                    'created_at' => optional($item->created_at)->format('d M Y H:i'),
                ];
            });

        return datatables()->of(collect($data))->make(true);
    }

    public function store(Request $request)
    {
        $request->merge(['akun_myads_pelanggan' => strtolower(trim((string) $request->akun_myads_pelanggan))]);

        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'akun_myads_pelanggan' => [
                'required', 'email', 'max:255',
                Rule::unique('akun_panen_poin_v5', 'email_client')
                    ->where(fn ($query) => $query->where('user_id', '<>', Auth::id())),
            ],
            'nomor_hp_pelanggan' => 'required|string|max:20',
        ], [
            'akun_myads_pelanggan.unique' => 'Email pelanggan sudah terdaftar pada pengguna lain di Panen Poin V5.',
        ]);

        $emailClient = strtolower(trim($request->akun_myads_pelanggan));

        DB::beginTransaction();
        try {
            $existing = AkunPanenPoinV5::where('user_id', Auth::id())
                ->where('email_client', $emailClient)
                ->first();

            $isNewAccount = false;
            if (!$existing) {
                $akun = AkunPanenPoinV5::create([
                    'user_id' => Auth::id(),
                    'nama_akun' => $request->nama_pelanggan,
                    'email_client' => $emailClient,
                    'password' => bcrypt('123456'),
                    'source' => 'user_panen_poin_v5',
                ]);

                UserPanenPoinV5::create([
                    'user_id' => Auth::id(),
                    'nama_pelanggan' => $request->nama_pelanggan,
                    'akun_myads_pelanggan' => $emailClient,
                    'nomor_hp_pelanggan' => $request->nomor_hp_pelanggan,
                ]);

                $isNewAccount = true;
            } else {
                $akun = $existing;
            }

            $this->refreshSummaryForSingleUser(Auth::id(), $emailClient);

            DB::commit();

            return redirect()->route('panenpoinv5.index')
                ->with('success', $isNewAccount ? 'Data pelanggan berhasil disimpan!' : 'Akun sudah ada di Panen Poin V5.')
                ->with('is_existing_account', !$isNewAccount);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Gagal menyimpan peserta Panen Poin V5', ['exception' => $e]);
            return redirect()->back()->with('error', 'Data belum berhasil disimpan. Silakan coba lagi.')->withInput();
        }
    }

    public function getReportData(Request $request)
    {
        return datatables()->of(collect($this->calculateData($request->tanggal)))->make(true);
    }

    public function getReportCanvasserData(Request $request)
    {
        return datatables()->of($this->buildReportByRole('cvsr', $request->tanggal))->addIndexColumn()->make(true);
    }

    public function getReportPowerhouseData(Request $request)
    {
        return datatables()->of($this->buildReportByRole('PH', $request->tanggal))->addIndexColumn()->make(true);
    }

    public function export(Request $request)
    {
        $data = $this->calculateData($request->tanggal);
        $period = $this->resolveRequestedPeriod($request->tanggal);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'LAPORAN PANEN POIN V5 - ' . strtoupper($period['label']));
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['No', 'Nama Canvasser', 'Email Client', 'Nomor HP Client', 'Source', 'Total Settlement', 'Total Poin', 'Poin Redeem', 'Poin Sisa', 'Remark'];
        $sheet->fromArray($headers, null, 'A3');

        $row = 4;
        foreach ($data as $index => $item) {
            $sheet->fromArray([
                $index + 1,
                $item['nama_canvasser'],
                $item['email_client'],
                $item['nomor_hp_client'],
                $item['source'],
                $item['total_settlement'],
                $item['poin'],
                $item['poin_redeem'],
                $item['poin_sisa'],
                $item['remark'],
            ], null, 'A' . $row++);
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'Laporan_Panen_Poin_V5_' . str_replace(' ', '_', $period['label']) . '.xlsx');
    }

    public function refreshSummaryPanenPoinV5()
    {
        $period = $this->currentPeriod();
        $canvassers = User::whereIn('role', ['cvsr', 'PH'])->get();
        $mitraClients = $this->getRegisteredMitraClients();
        foreach ($mitraClients as $client) {
            DB::table('summary_panen_poin_v5')
                ->whereRaw('LOWER(TRIM(email_client)) = ?', [$client->akun_myads_pelanggan])
                ->where('user_id', '<>', $client->user_id)
                ->whereDate('period_start', $period['start']->toDateString())
                ->whereDate('period_end', $period['end']->toDateString())
                ->delete();
        }
        $canvassers = $canvassers->toBase();
        foreach ($mitraClients->pluck('user_id')->merge([0, -1])->unique() as $userId) {
            if (!$canvassers->contains('id', $userId)) {
                $canvassers->push((object) ['id' => $userId, 'name' => $userId <= 0 ? 'Agency' : (User::find($userId)?->name ?? '-')]);
            }
        }
        $canvasserIds = $canvassers->pluck('id')->all();

        if (empty($canvasserIds)) {
            return response()->json(['success' => true, 'message' => 'Summary Panen Poin V5 berhasil direfresh.']);
        }

        $manualInputs = UserPanenPoinV5::whereIn('user_id', $canvasserIds)
            ->get(['user_id', 'akun_myads_pelanggan', 'nomor_hp_pelanggan']);

        $leads = DB::table('leads_master')
            ->whereIn('user_id', $canvasserIds)
            ->get(['user_id', 'email', 'mobile_phone']);

        $mitraEmails = $mitraClients->pluck('akun_myads_pelanggan')->flip();
        $manualInputs = $manualInputs->toBase()->reject(fn ($row) => $mitraEmails->has(strtolower(trim($row->akun_myads_pelanggan))))->merge($mitraClients);
        $leads = $leads->reject(fn ($row) => $mitraEmails->has(strtolower(trim((string) $row->email))));

        $allClientEmails = collect()
            ->merge($manualInputs->pluck('akun_myads_pelanggan'))
            ->merge($leads->pluck('email'))
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values()
            ->all();

        $settlementsByEmail = empty($allClientEmails)
            ? []
            : $this->eligibleSettlementQuery()
                ->select(
                    DB::raw('LOWER(TRIM(email_client)) as email'),
                    DB::raw('SUM(CAST(total_settlement_klien AS DECIMAL(15,2))) as total')
                )
                ->whereBetween(DB::raw('DATE(tgl_transaksi)'), [$period['start']->toDateString(), $period['end']->toDateString()])
                ->whereNotNull('total_settlement_klien')
                ->whereIn(DB::raw('LOWER(TRIM(email_client))'), $allClientEmails)
                ->groupBy(DB::raw('LOWER(TRIM(email_client))'))
                ->pluck('total', 'email')
                ->toArray();

        $packagePointsByEmail = empty($allClientEmails)
            ? []
            : DB::table('data_paket_seasonal as a')
                ->join('panen_poin_package_v2 as b', function ($join) {
                    $join->on(
                        DB::raw('LOWER(TRIM(a.name))'),
                        '=',
                        DB::raw('LOWER(TRIM(b.code))')
                    );
                })
                ->selectRaw('LOWER(TRIM(a.email)) as email, SUM(COALESCE(b.point, 0)) as point')
                ->whereBetween(DB::raw('DATE(a.created_at)'), [$period['start']->toDateString(), $period['end']->toDateString()])
                ->whereIn(DB::raw('LOWER(TRIM(a.email))'), $allClientEmails)
                ->groupBy(DB::raw('LOWER(TRIM(a.email))'))
                ->pluck('point', 'email')
                ->toArray();

        $akunByEmail = AkunPanenPoinV5::query()
            ->select('id', 'email_client')
            ->get()
            ->keyBy(fn ($row) => strtolower(trim($row->email_client)));

        $redeemByAkunId = DB::table('prize_redeems_v5')
            ->select('user_id', DB::raw('SUM(point_used) as total_redeem'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$period['start']->toDateString(), $period['end']->toDateString()])
            ->groupBy('user_id')
            ->pluck('total_redeem', 'user_id');

        $existingSummary = DB::table('summary_panen_poin_v5')
            ->select('id', 'user_id', 'email_client')
            ->whereIn('user_id', $canvasserIds)
            ->whereDate('period_start', $period['start']->toDateString())
            ->whereDate('period_end', $period['end']->toDateString())
            ->get()
            ->keyBy(fn ($row) => $row->user_id . '|' . strtolower(trim($row->email_client)));

        $manualByUser = $manualInputs->groupBy('user_id');
        $leadsByUser = $leads->groupBy('user_id');

        foreach ($canvassers as $canvasser) {
            $clients = $this->resolveClientPoolFromCollections(
                $manualByUser->get($canvasser->id, collect()),
                $leadsByUser->get($canvasser->id, collect())
            );

            if (empty($clients)) {
                continue;
            }

            foreach ($clients as $client) {
                $email = $client['email'];
                $totalSettlement = (float) ($settlementsByEmail[$email] ?? 0);
                $poinAkumulasi = 0;
                $poinPackage = (int) ($packagePointsByEmail[$email] ?? 0);
                $akun = $akunByEmail->get($email);
                $poinRedeem = $akun ? (int) ($redeemByAkunId[$akun->id] ?? 0) : 0;

                if ($totalSettlement == 0 && $poinAkumulasi == 0 && $poinPackage == 0) {
                    $existingKey = $canvasser->id . '|' . $email;
                    if ($existingSummary->has($existingKey)) {
                        DB::table('summary_panen_poin_v5')
                            ->where('id', $existingSummary[$existingKey]->id)
                            ->delete();
                    }
                    continue;
                }

                $poinBulanIni = (int) floor($totalSettlement / 250000);
                $poin = $poinBulanIni + $poinAkumulasi;
                $poinSisa = $poin - $poinRedeem;
                $payload = [
                    'user_id' => $canvasser->id,
                    'nama_canvasser' => $canvasser->name,
                    'email_client' => $email,
                    'nomor_hp_client' => $client['nomor_hp'],
                    'source' => $client['source'],
                    'total_settlement' => $totalSettlement,
                    'poin_bulan_ini' => $poinBulanIni,
                    'poin_akumulasi' => $poinAkumulasi,
                    'poin' => $poin,
                    'poin_package' => $poinPackage,
                    'poin_redeem' => $poinRedeem,
                    'remark' => $this->calculateRemark($poinSisa + $poinPackage),
                    'period_start' => $period['start']->toDateString(),
                    'period_end' => $period['end']->toDateString(),
                    'periode_label' => $period['label'],
                    'updated_at' => now(),
                ];

                $existing = $existingSummary->get($canvasser->id . '|' . $email);

                if ($existing) {
                    DB::table('summary_panen_poin_v5')->where('id', $existing->id)->update($payload);
                } else {
                    $payload['created_at'] = now();
                    $insertedId = DB::table('summary_panen_poin_v5')->insertGetId($payload);
                    $existingSummary->put($canvasser->id . '|' . $email, (object) [
                        'id' => $insertedId,
                        'user_id' => $canvasser->id,
                        'email_client' => $email,
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Summary Panen Poin V5 berhasil direfresh.']);
    }

    public function redeemPrize(Request $request)
    {
        $request->validate([
            'akun_id' => 'required|integer',
            'prize_id' => 'required|integer',
        ]);

        $period = $this->currentPeriod();

        DB::beginTransaction();
        try {
            $akun = AkunPanenPoinV5::findOrFail($request->akun_id);
            $prize = DB::table('prizes_v5')->where('id', $request->prize_id)->lockForUpdate()->first();
            if (!$prize || (int) $prize->stock <= 0) {
                throw new \Exception('Hadiah tidak tersedia.');
            }

        $summary = DB::table('summary_panen_poin_v5')
                ->whereRaw('LOWER(TRIM(email_client)) = ?', [strtolower(trim($akun->email_client))])
                ->whereDate('period_start', $period['start']->toDateString())
                ->whereDate('period_end', $period['end']->toDateString())
                ->first();

            if (!$summary) {
                throw new \Exception('Summary poin akun ini tidak ditemukan.');
            }

            $availablePoints = (int) $summary->poin - (int) ($summary->poin_redeem ?? 0) + (int) ($summary->poin_package ?? 0);
            if ($availablePoints < (int) $prize->point) {
                throw new \Exception('Poin tidak cukup untuk redeem hadiah ini.');
            }

            DB::table('prize_redeems_v5')->insert([
                'user_id' => $akun->id,
                'prize_id' => $prize->id,
                'point_used' => $prize->point,
                'period_start' => $period['start']->toDateString(),
                'period_end' => $period['end']->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('prizes_v5')->where('id', $prize->id)->update([
                'stock' => DB::raw('stock - 1'),
                'updated_at' => now(),
            ]);

            $this->updateSummaryAfterRedeem($akun->id, $period);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Redeem berhasil disimpan.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    protected function updateSummaryAfterRedeem(int $akunId, array $period): void
    {
        $totalRedeem = (int) DB::table('prize_redeems_v5')
            ->where('user_id', $akunId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$period['start']->toDateString(), $period['end']->toDateString()])
            ->sum('point_used');

        $akun = AkunPanenPoinV5::findOrFail($akunId);
        $summaries = DB::table('summary_panen_poin_v5')
            ->whereRaw('LOWER(TRIM(email_client)) = ?', [strtolower(trim($akun->email_client))])
            ->whereDate('period_start', $period['start']->toDateString())
            ->whereDate('period_end', $period['end']->toDateString())
            ->get();

        foreach ($summaries as $summary) {
            $poinSisa = (int) $summary->poin - $totalRedeem + (int) ($summary->poin_package ?? 0);
            DB::table('summary_panen_poin_v5')->where('id', $summary->id)->update([
                'poin_redeem' => $totalRedeem,
                'remark' => $this->calculateRemark($poinSisa),
                'updated_at' => now(),
            ]);
        }
    }

    protected function calculateData($periodStart = null): array
    {
        $period = $this->resolveRequestedPeriod($periodStart);
        $query = DB::table('summary_panen_poin_v5')
            ->select(
                DB::raw("CASE WHEN summary_panen_poin_v5.user_id IN (0, -1) THEN 'Agency' ELSE summary_panen_poin_v5.nama_canvasser END as nama_canvasser"),
                'summary_panen_poin_v5.email_client',
                'akun_panen_poin_v5.id as akun_id',
                'summary_panen_poin_v5.nomor_hp_client',
                'summary_panen_poin_v5.source',
                DB::raw('CAST(summary_panen_poin_v5.total_settlement AS DECIMAL(15,2)) as total_settlement_raw'),
                'summary_panen_poin_v5.poin_bulan_ini',
                'summary_panen_poin_v5.poin_akumulasi',
                'summary_panen_poin_v5.poin',
                'summary_panen_poin_v5.poin_package',
                DB::raw('COALESCE(summary_panen_poin_v5.poin_redeem, 0) as poin_redeem'),
                DB::raw('(summary_panen_poin_v5.poin - COALESCE(summary_panen_poin_v5.poin_redeem, 0) + COALESCE(summary_panen_poin_v5.poin_package, 0)) as poin_sisa'),
                'summary_panen_poin_v5.remark',
                'summary_panen_poin_v5.periode_label as bulan'
            )
            ->join('akun_panen_poin_v5', 'summary_panen_poin_v5.email_client', '=', 'akun_panen_poin_v5.email_client')
            ->whereDate('summary_panen_poin_v5.period_start', $period['start']->toDateString())
            ->whereDate('summary_panen_poin_v5.period_end', $period['end']->toDateString());

        if (Auth::user()->role === 'cvsr') {
            $query->where('summary_panen_poin_v5.user_id', Auth::id());
        }

        if (request()->filled('source')) {
            $query->where('summary_panen_poin_v5.source', request()->source);
        }

        if (request()->filled('remark')) {
            $query->where('summary_panen_poin_v5.remark', request()->remark);
        }

        return $query->orderByRaw('(summary_panen_poin_v5.poin_package + summary_panen_poin_v5.poin - COALESCE(summary_panen_poin_v5.poin_redeem, 0)) DESC')
            ->get()
            ->map(function ($item) {
                return [
                    'akun_id' => $item->akun_id,
                    'nama_canvasser' => $item->nama_canvasser,
                    'email_client' => $item->email_client,
                    'nomor_hp_client' => $item->nomor_hp_client,
                    'source' => $item->source,
                    'total_settlement' => number_format($item->total_settlement_raw, 0, ',', '.'),
                    'total_settlement_raw' => $item->total_settlement_raw,
                    'poin_bulan_ini' => $item->poin_bulan_ini,
                    'poin_akumulasi' => $item->poin_akumulasi,
                    'poin_package' => $item->poin_package,
                    'poin' => $item->poin,
                    'poin_redeem' => $item->poin_redeem,
                    'poin_sisa' => $item->poin_sisa,
                    'remark' => $item->remark,
                    'bulan' => $item->bulan,
                ];
            })->toArray();
    }

    protected function buildReportByRole(string $role, $periodStart = null)
    {
        $period = $this->resolveRequestedPeriod($periodStart);

        $query = DB::table('users')
            ->select(
                'users.id as user_id',
                'users.name as nama_canvasser',
                DB::raw('COALESCE(COUNT(CASE WHEN akun_panen_poin_v5.email_client IS NOT NULL THEN summary_panen_poin_v5.id END), 0) as jumlah_terdaftar'),
                DB::raw('COALESCE(SUM(CASE WHEN akun_panen_poin_v5.email_client IS NOT NULL AND (COALESCE(summary_panen_poin_v5.poin, 0) > 0 OR COALESCE(summary_panen_poin_v5.poin_package, 0) > 0) THEN 1 ELSE 0 END), 0) as jumlah_akun_punya_poin'),
                DB::raw('COALESCE(SUM(CASE WHEN akun_panen_poin_v5.email_client IS NOT NULL THEN summary_panen_poin_v5.poin ELSE 0 END), 0) as jumlah_poin')
            )
            ->where('users.role', $role)
            ->leftJoin('summary_panen_poin_v5', function ($join) use ($period) {
                $join->on('summary_panen_poin_v5.user_id', '=', 'users.id')
                    ->whereDate('summary_panen_poin_v5.period_start', $period['start']->toDateString())
                    ->whereDate('summary_panen_poin_v5.period_end', $period['end']->toDateString());
            })
            ->leftJoin('akun_panen_poin_v5', 'summary_panen_poin_v5.email_client', '=', 'akun_panen_poin_v5.email_client')
            ->groupBy('users.id', 'users.name')
            ->orderByRaw('COALESCE(SUM(CASE WHEN akun_panen_poin_v5.email_client IS NOT NULL THEN summary_panen_poin_v5.poin ELSE 0 END), 0) DESC');

        if (Auth::user()->role === $role) {
            $query->where('users.id', Auth::id());
        }

        return $query;
    }

    protected function getReportPeriods(): array
    {
        $current = $this->currentPeriod();
        return [[
            'value' => $current['start']->toDateString(),
            'label' => $current['label'],
            'selected' => true,
        ]];
    }

    protected function currentPeriod(): array
    {
        return [
            'start' => Carbon::createFromFormat('Y-m-d', self::PROGRAM_PERIOD_START, 'Asia/Jakarta')->startOfDay(),
            'end' => Carbon::createFromFormat('Y-m-d', self::PROGRAM_PERIOD_END, 'Asia/Jakarta')->startOfDay(),
            'label' => self::PROGRAM_PERIOD_LABEL,
        ];
    }

    protected function previousPeriod(Carbon $currentStart): array
    {
        $previousStart = Carbon::createFromFormat('Y-m-d', self::PREVIOUS_PERIOD_START, 'Asia/Jakarta')->startOfDay();
        $previousEnd = Carbon::createFromFormat('Y-m-d', self::PREVIOUS_PERIOD_END, 'Asia/Jakarta')->startOfDay();

        return [
            'start' => $previousStart,
            'end' => $previousEnd,
            'label' => self::PREVIOUS_PERIOD_LABEL,
        ];
    }

    protected function resolveRequestedPeriod($periodStart = null): array
    {
        return $this->currentPeriod();
    }

    protected function resolvePeriodFromDate(Carbon $date): array
    {
        return $this->currentPeriod();
    }

    protected function refreshSummaryForSingleUser($userId, $emailClient): void
    {
        $period = $this->currentPeriod();

        $clientData = UserPanenPoinV5::where('user_id', $userId)
            ->where('akun_myads_pelanggan', $emailClient)
            ->select('akun_myads_pelanggan', 'nomor_hp_pelanggan')
            ->first();

        if (!$clientData) {
            $clientData = DB::table('leads_master')
                ->where('user_id', $userId)
                ->where('email', $emailClient)
                ->select('email as akun_myads_pelanggan', 'mobile_phone as nomor_hp_pelanggan')
                ->first();
        }

        if (!$clientData) {
            return;
        }

        $email = strtolower(trim($clientData->akun_myads_pelanggan));
        $settlement = $this->eligibleSettlementQuery()
            ->select(DB::raw('SUM(CAST(total_settlement_klien AS DECIMAL(15,2))) as total'))
            ->whereBetween(DB::raw('DATE(tgl_transaksi)'), [$period['start']->toDateString(), $period['end']->toDateString()])
            ->where(DB::raw('LOWER(TRIM(email_client))'), $email)
            ->first();

        $totalSettlement = (float) ($settlement->total ?? 0);
        $poinAkumulasi = 0;
        $poinPackage = (int) (DB::table('data_paket_seasonal as a')
            ->join('panen_poin_package_v2 as b', function ($join) {
                $join->on(
                    DB::raw('LOWER(TRIM(a.name))'),
                    '=',
                    DB::raw('LOWER(TRIM(b.code))')
                );
            })
            ->whereBetween(DB::raw('DATE(a.created_at)'), [$period['start']->toDateString(), $period['end']->toDateString()])
            ->where(DB::raw('LOWER(TRIM(a.email))'), $email)
            ->sum(DB::raw('COALESCE(b.point, 0)')) ?? 0);
        $akun = AkunPanenPoinV5::whereRaw('LOWER(TRIM(email_client)) = ?', [$email])->first();
        $poinRedeem = $akun ? (int) DB::table('prize_redeems_v5')
            ->where('user_id', $akun->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$period['start']->toDateString(), $period['end']->toDateString()])
            ->sum('point_used') : 0;

        if ($totalSettlement == 0 && $poinAkumulasi == 0 && $poinPackage == 0) {
            DB::table('summary_panen_poin_v5')
                ->where('user_id', $userId)
                ->where('email_client', $email)
                ->whereDate('period_start', $period['start']->toDateString())
                ->whereDate('period_end', $period['end']->toDateString())
                ->delete();
            return;
        }

        $poinBulanIni = (int) floor($totalSettlement / 250000);
        $poin = $poinBulanIni + $poinAkumulasi;

        $payload = [
            'user_id' => $userId,
            'nama_canvasser' => $userId <= 0 ? 'Agency' : (User::find($userId)?->name ?? '-'),
            'email_client' => $email,
            'nomor_hp_client' => $clientData->nomor_hp_pelanggan ?? '-',
            'source' => $this->getSourceFromUserData($userId, $email),
            'total_settlement' => $totalSettlement,
            'poin_bulan_ini' => $poinBulanIni,
            'poin_akumulasi' => $poinAkumulasi,
            'poin' => $poin,
            'poin_package' => $poinPackage,
            'poin_redeem' => $poinRedeem,
            'remark' => $this->calculateRemark(($poin - $poinRedeem) + $poinPackage),
            'period_start' => $period['start']->toDateString(),
            'period_end' => $period['end']->toDateString(),
            'periode_label' => $period['label'],
            'updated_at' => now(),
        ];

        $existing = DB::table('summary_panen_poin_v5')
            ->where('user_id', $userId)
            ->where('email_client', $email)
            ->whereDate('period_start', $period['start']->toDateString())
            ->whereDate('period_end', $period['end']->toDateString())
            ->first();

        if ($existing) {
            DB::table('summary_panen_poin_v5')->where('id', $existing->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('summary_panen_poin_v5')->insert($payload);
        }
    }

    protected function eligibleSettlementQuery()
    {
        return DB::table('report_balance_top_up')
            ->whereRaw("LOWER(TRIM(COALESCE(payment_method_name, ''))) <> ?", ['voucher bonus']);
    }

    protected function getRegisteredMitraClients()
    {
        $accounts = AkunPanenPoinV5::all()->keyBy(fn ($account) => strtolower(trim($account->email_client)));
        return DB::table('mitra_sbp')->pluck('email_myads')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => $email !== '' && $accounts->has($email))->unique()->map(function ($email) use ($accounts) {
                $account = $accounts->get($email);
                return (object) [
                    'user_id' => $account->user_id ?? -1,
                    'akun_myads_pelanggan' => $email,
                    'nomor_hp_pelanggan' => DB::table('user_panen_poin_v5')
                        ->where('user_id', $account->user_id)->where('akun_myads_pelanggan', $email)->value('nomor_hp_pelanggan')
                        ?? DB::table('leads_master')->where('user_id', $account->user_id)->where('email', $email)->value('mobile_phone') ?? '-',
                    'source' => 'mitra_sbp',
                ];
            })->values();
    }

    protected function resolveClientPool(int $userId): array
    {
        return $this->resolveClientPoolFromCollections(
            UserPanenPoinV5::where('user_id', $userId)->get(),
            DB::table('leads_master')->where('user_id', $userId)->get()
        );
    }

    protected function resolveClientPoolFromCollections($manualRows, $leadRows): array
    {
        $clients = [];
        $leadsMap = [];

        foreach ($manualRows as $row) {
            $email = strtolower(trim((string) $row->akun_myads_pelanggan));
            if ($email === '') {
                continue;
            }

            $clients[] = [
                'email' => $email,
                'nomor_hp' => $row->nomor_hp_pelanggan,
                'source' => $row->source ?? 'user_panen_poin_v5',
            ];
        }

        foreach ($leadRows as $lead) {
            $email = strtolower(trim((string) $lead->email));
            if ($email === '') {
                continue;
            }

            $leadsMap[$email] = true;
            $clients[] = [
                'email' => $email,
                'nomor_hp' => $lead->mobile_phone ?? '-',
                'source' => 'leads_master',
            ];
        }

        return collect($clients)
            ->reject(function ($client) use ($leadsMap) {
                return $client['source'] === 'user_panen_poin_v5' && isset($leadsMap[$client['email']]);
            })
            ->unique(fn ($client) => $client['email'] . '|' . $client['source'])
            ->values()
            ->all();
    }

    protected function calculateRemark($poinSisa): string
    {
        if ($poinSisa >= 301) {
            return 'Champion';
        }
        if ($poinSisa >= 101) {
            return 'Rising Star';
        }
        return 'Rookie';
    }

    protected function getSourceFromUserData($userId, $email): string
    {
        if (DB::table('mitra_sbp')->whereRaw('LOWER(TRIM(email_myads)) = ?', [strtolower(trim($email))])->exists()) {
            return 'mitra_sbp';
        }

        $isManualInput = DB::table('user_panen_poin_v5')
            ->where('user_id', $userId)
            ->where('akun_myads_pelanggan', $email)
            ->exists();

        if ($isManualInput) {
            return 'user_panen_poin_v5';
        }

        return DB::table('leads_master')
            ->where('user_id', $userId)
            ->where('email', $email)
            ->value('source') ?? 'leads_master';
    }
}
