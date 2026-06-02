<?php

namespace App\Http\Controllers;

use App\Models\CreatorPartner;
use App\Models\EventSponsorhip;
use App\Models\PadiUmkm;
use App\Models\RahasiaBisnis;
use App\Models\ReferralChampionAm;
use App\Models\RekruterKol;
use App\Models\SimpatiTiktok;
use App\Models\SultamRacing;
use Illuminate\Http\Request;
use App\Models\User;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Session;
use Carbon\Carbon;
use Dflydev\DotAccessData\Data;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;


class BackController extends Controller
{
    private function resolveSemesterPeriod(?string $semesterValue): array
    {
        $now = Carbon::now();
        $defaultSemester = $now->month <= 6 ? 1 : 2;

        if (!preg_match('/^(\d{4})-(1|2)$/', (string) $semesterValue, $matches)) {
            $year = (int) $now->year;
            $semester = (int) $defaultSemester;
        } else {
            $year = (int) $matches[1];
            $semester = (int) $matches[2];
        }

        $startMonth = $semester === 1 ? 1 : 7;
        $endMonth = $semester === 1 ? 6 : 12;
        $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
        $endDate = Carbon::create($year, $endMonth, 1)->endOfMonth();

        return [
            'year' => $year,
            'semester' => $semester,
            'value' => $year . '-' . $semester,
            'label' => sprintf(
                'Semester %d %d (%s - %s)',
                $semester,
                $year,
                $startDate->translatedFormat('F'),
                $endDate->translatedFormat('F')
            ),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function buildPowerHouseDealTopupMomResult(
        Carbon $startDate,
        Carbon $endDate,
        callable $targetResolver,
        ?Carbon $momReferenceDate = null
    ): array {
        $startDateFormatted = $startDate->copy()->startOfDay()->format('Y-m-d');
        $endDateFormatted = $endDate->copy()->endOfDay()->format('Y-m-d');

        $phUsers = DB::table('users')
            ->where('role', 'PH')
            ->where('name', '!=', 'self service')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        if ($phUsers->isEmpty()) {
            return [];
        }

        $allTeamUserIds = $phUsers->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $topUpStatsByUser = collect();
        $topUpNewAkunByUser = collect();
        $topUpExistingAkunByUser = collect();
        $momByUser = collect();
        $topUpAggByUser = collect();
        $leadAggByUser = collect();
        $visitAggByName = collect();
        $targetByTeam = collect($targetResolver($phUsers, $startDate, $endDate));
        
        if (!empty($allTeamUserIds)) {
            $topUpStatsByUser = DB::table('report_balance_top_up as rp')
                ->join('leads_master as lm', DB::raw('LOWER(rp.email_client)'), '=', DB::raw('LOWER(lm.email)'))
                ->whereIn('lm.user_id', $allTeamUserIds)
                ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startDateFormatted, $endDateFormatted])
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("COUNT(rp.id) as top_up_count"),
                    DB::raw("SUM(CAST(rp.amount AS DECIMAL(15,2))) as total_top_up_rp")
                )
                ->get()
                ->keyBy('user_id');

            $topUpAggByUser = DB::table('report_balance_top_up as rp')
                ->join('leads_master as lm', DB::raw('LOWER(rp.email_client)'), '=', DB::raw('LOWER(lm.email)'))
                ->whereIn('lm.user_id', $allTeamUserIds)
                ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startDateFormatted, $endDateFormatted])
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("COUNT(DISTINCT LOWER(rp.email_client)) as jumlah_akun"),
                    DB::raw("MAX(rp.tgl_transaksi) as tgl_transaksi_terakhir")
                )
                ->get()
                ->keyBy('user_id');

            $topUpNewAkunByUser = DB::table('data_registarsi_status_approveorreject as dt')
                ->join('report_balance_top_up as rp', function ($join) {
                    $join->on(DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(rp.email_client)'))
                        ->whereRaw("DATE(rp.tgl_transaksi) >= STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')");
                })
                ->join('leads_master as lm', DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(lm.email)'))
                ->whereIn('lm.user_id', $allTeamUserIds)
                ->where('dt.status', 'APPROVE')
                ->whereBetween(
                    DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')"),
                    [$startDateFormatted, $endDateFormatted]
                )
                ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startDateFormatted, $endDateFormatted])
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("COUNT(DISTINCT rp.id) as top_up_count"),
                    DB::raw("SUM(CAST(rp.amount AS DECIMAL(15,2))) as top_up_new_akun_rp")
                )
                ->get()
                ->keyBy('user_id');

            $topUpExistingAkunByUser = DB::table('data_registarsi_status_approveorreject as dt')
                ->join('leads_master as lm', DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(lm.email)'))
                ->join('report_balance_top_up as rp', function ($join) {
                    $join->on(DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(rp.email_client)'))
                        ->whereRaw("DATE(rp.tgl_transaksi) >= STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')");
                })
                ->whereIn('lm.user_id', $allTeamUserIds)
                ->where('dt.status', 'APPROVE')
                ->whereRaw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d') < ?", [$startDateFormatted])
                ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startDateFormatted, $endDateFormatted])
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("COUNT(rp.id) as top_up_existing_akun_count"),
                    DB::raw("SUM(CAST(rp.amount AS DECIMAL(15,2))) as top_up_existing_akun_rp")
                )
                ->get()
                ->keyBy('user_id');

            $momReference = ($momReferenceDate ?: Carbon::today())->copy()->endOfDay();
            $currentMonthStart = $momReference->copy()->startOfMonth()->format('Y-m-d');
            $currentMonthUntilRef = $momReference->copy()->format('Y-m-d');
            $prevMonthRef = $momReference->copy()->subMonthNoOverflow();
            $prevMonthStart = $prevMonthRef->copy()->startOfMonth()->format('Y-m-d');
            $prevMonthSameDay = $prevMonthRef->copy()->format('Y-m-d');
            $prevMonthEnd = $prevMonthRef->copy()->endOfMonth()->format('Y-m-d');
            $prevMonthRemainingStart = $prevMonthRef->copy()->addDay()->format('Y-m-d');

            $momByUser = DB::table('report_balance_top_up as rp')
                ->join('leads_master as lm', DB::raw('LOWER(rp.email_client)'), '=', DB::raw('LOWER(lm.email)'))
                ->whereIn('lm.user_id', $allTeamUserIds)
                ->groupBy('lm.user_id')
                ->select(
                    'lm.user_id',
                    DB::raw("SUM(CASE WHEN DATE(rp.tgl_transaksi) BETWEEN '{$prevMonthStart}' AND '{$prevMonthSameDay}' THEN CAST(rp.amount AS DECIMAL(15,2)) ELSE 0 END) as mom_prev_partial"),
                    DB::raw("SUM(CASE WHEN DATE(rp.tgl_transaksi) BETWEEN '{$currentMonthStart}' AND '{$currentMonthUntilRef}' THEN CAST(rp.amount AS DECIMAL(15,2)) ELSE 0 END) as mom_current_partial"),
                    DB::raw("SUM(CASE WHEN DATE(rp.tgl_transaksi) BETWEEN '{$prevMonthRemainingStart}' AND '{$prevMonthEnd}' THEN CAST(rp.amount AS DECIMAL(15,2)) ELSE 0 END) as mom_prev_remaining")
                )
                ->get()
                ->keyBy('user_id');
        }

        $leadAggByUser = DB::table('leads_master')
            ->whereIn('user_id', $allTeamUserIds)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as jumlah_leads'))
            ->get()
            ->keyBy('user_id');

        $visitAggByName = DB::table('bookings')
            ->whereIn('nama', $phUsers->pluck('name')->values()->all())
            ->whereBetween('tanggal', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('nama')
            ->select('nama', DB::raw('COUNT(*) as jumlah_visit'))
            ->get()
            ->keyBy('nama');

        $result = [];
        foreach ($phUsers as $phUser) {
            $userId = (int) $phUser->id;

            $topUpNewAkunCount = (int) ($topUpNewAkunByUser[$userId]->top_up_count ?? 0);
            $topUpExistingAkunCount = (int) ($topUpExistingAkunByUser[$userId]->top_up_existing_akun_count ?? 0);
            $topUpNewAkunRp = (float) ($topUpNewAkunByUser[$userId]->top_up_new_akun_rp ?? 0);
            $topUpExistingAkunRp = (float) ($topUpExistingAkunByUser[$userId]->top_up_existing_akun_rp ?? 0);
            $totalTopUpFromStats = (float) ($topUpStatsByUser[$userId]->total_top_up_rp ?? 0);
            $momPrevPartial = (float) ($momByUser[$userId]->mom_prev_partial ?? 0);
            $momCurrentPartial = (float) ($momByUser[$userId]->mom_current_partial ?? 0);
            $momPrevRemaining = (float) ($momByUser[$userId]->mom_prev_remaining ?? 0);

            $splitTotal = $topUpNewAkunRp + $topUpExistingAkunRp;
            $totalTopup = $splitTotal > 0 ? $splitTotal : $totalTopUpFromStats;
            if ($totalTopUpFromStats > 0 && $splitTotal < $totalTopUpFromStats) {
                $difference = $totalTopUpFromStats - $splitTotal;
                $topUpExistingAkunRp += $difference;
                $totalTopup = $totalTopUpFromStats;
            }

            $momGap = $momCurrentPartial - $momPrevPartial;
            $poin = floor($totalTopup / 1000000);

            $jumlahAkun = (int) ($topUpAggByUser[$userId]->jumlah_akun ?? 0);
            $jumlahLeads = (int) ($leadAggByUser[$userId]->jumlah_leads ?? 0);
            $jumlahVisit = (int) ($visitAggByName[$phUser->name]->jumlah_visit ?? 0);
            $target = (float) ($targetByTeam[$userId]->target ?? 0);
            $tglFormatted = !empty($topUpAggByUser[$userId]->tgl_transaksi_terakhir)
                ? Carbon::parse($topUpAggByUser[$userId]->tgl_transaksi_terakhir)->format('d M Y')
                : '-';
            
            $result[] = [
                'team_powerhouse' => $phUser->name,
                'jumlah_akun' => $jumlahAkun,
                'jumlah_leads' => $jumlahLeads,
                'jumlah_visit' => $jumlahVisit,
                'deal_topup_new_akun' => $topUpNewAkunCount,
                'deal_topup_existing_akun' => $topUpExistingAkunCount,
                'top_up_new_akun_rp' => $topUpNewAkunRp,
                'top_up_existing_akun_rp' => $topUpExistingAkunRp,
                'total_topup' => $totalTopup,
                'target' => $target,
                'mom_prev_partial' => $momPrevPartial,
                'mom_current_partial' => $momCurrentPartial,
                'mom_prev_remaining' => $momPrevRemaining,
                'mom_gap' => $momGap,
                'poin' => $poin,
                'tgl_transaksi_terakhir' => $tglFormatted,
            ];
        }

        return collect($result)
            ->sort(function ($a, $b) {
                if ($a['total_topup'] === $b['total_topup']) {
                    return strcmp($a['team_powerhouse'], $b['team_powerhouse']);
                }
                return $b['total_topup'] <=> $a['total_topup'];
            })
            ->values()
            ->all();
    }

    public function registerStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'nope' => 'required',
            'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
            'role' => 'required|in:Admin,Tsel,TCD,Internal,b2b',
        ]);
        $data = [
            'name' => $request->name,
            'nohp' => $request->nope,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make('123456'),
            'status' => 'Aktif'
        ];
        $nope = $request->nope;
        if (Str::startsWith($nope, '08')) {
            $nope = '62' . substr($nope, 1);
        }
        $data['nohp'] = $nope;
        User::create($data);
        return redirect()->back()->with('success', 'User berhasil didaftarkan!');
    }
    public function login(Request $request)
    {
        // 1. Validasi form
        $request->validate([
            'email' => [
                'required',
                'email',
                // 'regex:/^[a-zA-Z0-9._%+-]+@telkomsel\.co\.id$/', // hanya email @telkomsel.co.id
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // semua domain
            ],
            'password' => 'required',
        ], [
            'email.regex' => 'Email harus menggunakan domain @telkomsel.co.id',
        ]);

        // 2. Coba login
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            // 3. Cek status
            if ($user->status !== 'Aktif') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akun Anda belum aktif.',
                ])->withInput();
            }

            // 4. Log user login
            logUserLogin();

            // 5. Arahkan sesuai role
            switch ($user->role) {
                case 'Admin':
                case 'Tsel':
                    return redirect()->route('admin.home');
                case 'Treg':
                    return redirect()->route('race_summary_treg');
                case 'cvsr':
                    return redirect()->route('presensi.index');
                case 'MPCC':
                    return redirect()->route('mpcc.report');
                case 'TCD':
                    return redirect()->route('report-agency-advertising');
                case 'Maxim':
                    return redirect()->route('report-maxim');
                case 'Automatech':
                    return redirect()->route('report-automatech');
                case 'Internal':
                    return redirect()->route('mitra-sbp');
                case 'CDSI':
                    return redirect()->route('report-cdsi');
                case 'b2b':
                    return redirect()->route('amlevelup.index');
                default:
                    return redirect()->route('home'); // fallback
            }
        }

        // 6. Kalau gagal login
        return back()->withErrors([
            'email' => 'Email atau Password Anda salah.',
        ])->withInput();
    }

    public function storeUploadAutomatechReport(Request $request)
    {
        $validated = $request->validate([
            'report_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'report_file.required' => 'File report wajib dipilih.',
            'report_file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).',
            'report_file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $validated['report_file'];
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName);

        if (empty($safeName)) {
            $safeName = 'report-automatech';
        }

        $fileName = $safeName . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs('automatech-report-uploads', $fileName);
        $uploadBatch = now()->format('YmdHis');

        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        if (count($rows) <= 1) {
            return redirect()
                ->route('admin.upload.automatech-report')
                ->with('error', 'File Excel tidak memiliki data yang bisa diproses.');
        }

        $payload = [];
        foreach (array_slice($rows, 1) as $row) {
            $idIklan = trim((string) ($row['A'] ?? ''));
            $tglTayang = $this->parseAutomatechReportDate($row['B'] ?? null);
            $judulPesanIklan = trim((string) ($row['C'] ?? ''));
            $operatorSeluler = trim((string) ($row['D'] ?? ''));
            $kategoriIklan = trim((string) ($row['E'] ?? ''));
            $tipeKanal = trim((string) ($row['F'] ?? ''));
            $detilStatus = trim((string) ($row['G'] ?? ''));
            $refunded = $this->parseAutomatechReportInteger($row['H'] ?? 0);
            $read = $this->parseAutomatechReportInteger($row['I'] ?? 0);
            $click = $this->parseAutomatechReportInteger($row['J'] ?? 0);
            $totalHarga = $this->parseAutomatechReportInteger($row['K'] ?? 0);

            if ($idIklan === '' && $judulPesanIklan === '' && $detilStatus === '') {
                continue;
            }

            if ($idIklan === '') {
                continue;
            }

            [$sukses, $gagal] = $this->parseAutomatechReportStatus($detilStatus);

            $payload[] = [
                'id_iklan' => $idIklan,
                'tgl_tayang' => $tglTayang,
                'judul_pesan_iklan' => $judulPesanIklan !== '' ? $judulPesanIklan : null,
                'operator_seluler' => $operatorSeluler !== '' ? $operatorSeluler : null,
                'kategori_iklan' => $kategoriIklan !== '' ? $kategoriIklan : null,
                'tipe_kanal' => $tipeKanal !== '' ? $tipeKanal : null,
                'detil_status' => $detilStatus !== '' ? $detilStatus : null,
                'sukses' => $sukses,
                'gagal' => $gagal,
                'refunded' => $refunded,
                'read' => $read,
                'click' => $click,
                'total_harga' => $totalHarga,
                'source_file_name' => $file->getClientOriginalName(),
                'upload_batch' => $uploadBatch,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($payload)) {
            return redirect()
                ->route('admin.upload.automatech-report')
                ->with('error', 'Tidak ada baris valid yang bisa diimport dari file Excel.');
        }

        DB::transaction(function () use ($payload) {
            DB::table('automatech_reports')->delete();
            DB::table('automatech_reports')->insert($payload);
        });

        return redirect()
            ->route('admin.upload.automatech-report')
            ->with('success', count($payload) . ' baris report Automatech berhasil diimport dari ' . $storedPath . '.');
    }


    public function storeUploadCdsiReport(Request $request)
    {
        $validated = $request->validate([
            'report_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'report_file.required' => 'File report wajib dipilih.',
            'report_file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).',
            'report_file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $validated['report_file'];
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName);

        if (empty($safeName)) {
            $safeName = 'report-cdsi';
        }

        $fileName = $safeName . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs('cdsi-report-uploads', $fileName);
        $uploadBatch = now()->format('YmdHis');

        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        if (count($rows) <= 1) {
            return redirect()
                ->route('admin.upload.cdsi-report')
                ->with('error', 'File Excel tidak memiliki data yang bisa diproses.');
        }

        $payload = [];
        foreach (array_slice($rows, 1) as $row) {
            $idIklan = trim((string) ($row['A'] ?? ''));
            $tglTayang = $this->parseAutomatechReportDate($row['B'] ?? null);
            $judulPesanIklan = trim((string) ($row['C'] ?? ''));
            $operatorSeluler = trim((string) ($row['D'] ?? ''));
            $kategoriIklan = trim((string) ($row['E'] ?? ''));
            $tipeKanal = trim((string) ($row['F'] ?? ''));
            $detilStatus = trim((string) ($row['G'] ?? ''));
            $refunded = $this->parseAutomatechReportInteger($row['H'] ?? 0);
            $read = $this->parseAutomatechReportInteger($row['I'] ?? 0);
            $click = $this->parseAutomatechReportInteger($row['J'] ?? 0);
            $totalHarga = $this->parseAutomatechReportInteger($row['K'] ?? 0);

            if ($idIklan === '' && $judulPesanIklan === '' && $detilStatus === '') {
                continue;
            }

            if ($idIklan === '') {
                continue;
            }

            [$sukses, $gagal] = $this->parseAutomatechReportStatus($detilStatus);

            $payload[] = [
                'id_iklan' => $idIklan,
                'tgl_tayang' => $tglTayang,
                'judul_pesan_iklan' => $judulPesanIklan !== '' ? $judulPesanIklan : null,
                'operator_seluler' => $operatorSeluler !== '' ? $operatorSeluler : null,
                'kategori_iklan' => $kategoriIklan !== '' ? $kategoriIklan : null,
                'tipe_kanal' => $tipeKanal !== '' ? $tipeKanal : null,
                'detil_status' => $detilStatus !== '' ? $detilStatus : null,
                'sukses' => $sukses,
                'gagal' => $gagal,
                'refunded' => $refunded,
                'read' => $read,
                'click' => $click,
                'total_harga' => $totalHarga,
                'source_file_name' => $file->getClientOriginalName(),
                'upload_batch' => $uploadBatch,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($payload)) {
            return redirect()
                ->route('admin.upload.cdsi-report')
                ->with('error', 'Tidak ada baris valid yang bisa diimport dari file Excel.');
        }

        DB::transaction(function () use ($payload) {
            DB::table('cdsi_reports')->delete();
            DB::table('cdsi_reports')->insert($payload);
        });

        return redirect()
            ->route('admin.upload.cdsi-report')
            ->with('success', count($payload) . ' baris report CDSI berhasil diimport dari ' . $storedPath . '.');
    }
    private function parseAutomatechReportDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            }

            $dateString = trim((string) $value);

            foreach (['m/d/Y', 'n/j/Y', 'd/m/Y', 'j/n/Y'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $dateString)->format('Y-m-d');
                } catch (\Throwable $e) {
                }
            }

            return Carbon::parse($dateString)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseAutomatechReportInteger($value): int
    {
        $normalized = preg_replace('/[^\d]/', '', (string) $value);

        return $normalized === '' ? 0 : (int) $normalized;
    }

    private function parseAutomatechReportStatus(?string $detailStatus): array
    {
        $success = 0;
        $failed = 0;
        $detailStatus = (string) $detailStatus;

        if (preg_match('/Sukses\s*:\s*([\d\.,]+)/i', $detailStatus, $matches)) {
            $success = $this->parseAutomatechReportInteger($matches[1]);
        }

        if (preg_match('/Gagal\s*:\s*([\d\.,]+)/i', $detailStatus, $matches)) {
            $failed = $this->parseAutomatechReportInteger($matches[1]);
        }

        return [$success, $failed];
    }

    public function storeUploadMaximReport(Request $request)
    {
        $validated = $request->validate([
            'report_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'report_file.required' => 'File report wajib dipilih.',
            'report_file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).',
            'report_file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $validated['report_file'];
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName);

        if (empty($safeName)) {
            $safeName = 'report-maxim';
        }

        $fileName = $safeName . '-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs('maxim-report-uploads', $fileName);
        $uploadBatch = now()->format('YmdHis');

        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        if (count($rows) <= 1) {
            return redirect()
                ->route('admin.upload.maxim-report')
                ->with('error', 'File Excel tidak memiliki data yang bisa diproses.');
        }

        $payload = [];
        foreach (array_slice($rows, 1) as $row) {
            $idIklan = trim((string) ($row['A'] ?? ''));
            $tglTayang = $this->parseAutomatechReportDate($row['B'] ?? null);
            $judulPesanIklan = trim((string) ($row['C'] ?? ''));
            $operatorSeluler = trim((string) ($row['D'] ?? ''));
            $kategoriIklan = trim((string) ($row['E'] ?? ''));
            $tipeKanal = trim((string) ($row['F'] ?? ''));
            $detilStatus = trim((string) ($row['G'] ?? ''));
            $refunded = $this->parseAutomatechReportInteger($row['H'] ?? 0);
            $read = $this->parseAutomatechReportInteger($row['I'] ?? 0);
            $click = $this->parseAutomatechReportInteger($row['J'] ?? 0);
            $totalHarga = $this->parseAutomatechReportInteger($row['K'] ?? 0);

            if ($idIklan === '' && $judulPesanIklan === '' && $detilStatus === '') {
                continue;
            }

            if ($idIklan === '') {
                continue;
            }

            [$sukses, $gagal] = $this->parseAutomatechReportStatus($detilStatus);

            $payload[] = [
                'id_iklan' => $idIklan,
                'tgl_tayang' => $tglTayang,
                'judul_pesan_iklan' => $judulPesanIklan !== '' ? $judulPesanIklan : null,
                'operator_seluler' => $operatorSeluler !== '' ? $operatorSeluler : null,
                'kategori_iklan' => $kategoriIklan !== '' ? $kategoriIklan : null,
                'tipe_kanal' => $tipeKanal !== '' ? $tipeKanal : null,
                'detil_status' => $detilStatus !== '' ? $detilStatus : null,
                'sukses' => $sukses,
                'gagal' => $gagal,
                'refunded' => $refunded,
                'read' => $read,
                'click' => $click,
                'total_harga' => $totalHarga,
                'source_file_name' => $file->getClientOriginalName(),
                'upload_batch' => $uploadBatch,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($payload)) {
            return redirect()
                ->route('admin.upload.maxim-report')
                ->with('error', 'Tidak ada baris valid yang bisa diimport dari file Excel.');
        }

        DB::transaction(function () use ($payload) {
            DB::table('maxim_reports')->delete();
            DB::table('maxim_reports')->insert($payload);
        });

        return redirect()
            ->route('admin.upload.maxim-report')
            ->with('success', count($payload) . ' baris report Maxim berhasil diimport dari ' . $storedPath . '.');
    }

    public function getPadiUmkmData(Request $request)
    {
        if ($request->has('tanggal') && !empty($request->tanggal)) {
            $tanggal = $request->tanggal;
            $date = Carbon::parse($tanggal);
            $month = $date->month;
            $year = $date->year;
            $data = DB::table('summary_padi_umkm')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $data = DB::table('summary_padi_umkm')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }
    public function getPadiUmkmSummary(Request $request)
    {
        $query = DB::table('summary_padi_umkm');

        if ($request->has('tanggal') && !empty($request->tanggal)) {
            $tanggal = $request->tanggal;
            $date = Carbon::parse($tanggal);
            $month = $date->month;
            $year = $date->year;
            $query->whereMonth('created_at', $month)
                ->whereYear('created_at', $year);
        }

        $totalForm = $query->count();

        // Clone query for aggregation
        $topupQuery = clone $query;

        $jumlahTopup = $topupQuery->sum('jumlah_topup');
        $totalTopup = $topupQuery->sum('total_topup');

        return response()->json([
            'total_form'    => $totalForm,
            'jumlah_topup'  => $jumlahTopup,
            'total_topup'   => $totalTopup,
        ]);
    }
    public function getEventSponsorship(Request $request)
    {
        $data = EventSponsorhip::orderBy('created_at', 'desc')->get();

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }


    public function getCreatorPartner(Request $request)
    {
        // Ambil semua creator + total_invited (subquery)
        $creators = CreatorPartner::select(
            'creator_partner.*',
            DB::raw('(SELECT COUNT(*) FROM rekruter_kol WHERE rekruter_kol.referral_code = creator_partner.referral_code) as total_invited')
        )
            ->when($request->area, function ($query, $area) {
                $query->where('creator_partner.area', $area);
            })
            ->when($request->region, function ($query, $region) {
                $query->where('creator_partner.regional', $region);
            })
            ->when($request->jenis_kol, function ($query, $jenis_kol) {
                $query->where('creator_partner.jenis_kol', $jenis_kol);
            })
            ->orderBy('creator_partner.created_at', 'desc')
            ->get();

        // Hitung tier untuk setiap creator partner
        $creators->transform(function ($creator) use ($request) {
            // ambil list email rekruter untuk referral ini
            $rekruterEmails = DB::table('rekruter_kol')
                ->where('referral_code', $creator->referral_code)
                ->pluck('email')
                ->toArray();

            if (empty($rekruterEmails)) {
                $creator->tier = '-';
            } else {
                // ambil total topup per email
                $topupPerAkun = DB::table('revenue_kol')
                    ->whereIn('email', $rekruterEmails)
                    ->select('email', DB::raw('SUM(jumlah_top_up) as total_topup'))
                    ->groupBy('email')
                    ->pluck('total_topup', 'email');

                // set minimum sesuai jenis_kol
                $minTopup = 0;
                if ($creator->jenis_kol === 'KOL as a Buzzer') {
                    $minTopup = 250000;
                } elseif ($creator->jenis_kol === 'KOL as a Seller Online/Afiliate') {
                    $minTopup = 200000;
                }

                // hitung berapa akun yang memenuhi minimum
                $eligibleAccounts = collect($topupPerAkun)->filter(function ($total) use ($minTopup) {
                    return $total >= $minTopup;
                })->count();

                // tentukan tier
                if ($eligibleAccounts >= 30) {
                    $creator->tier = 'Platinum';
                } elseif ($eligibleAccounts >= 20) {
                    $creator->tier = 'Gold';
                } elseif ($eligibleAccounts >= 10) {
                    $creator->tier = 'Silver';
                } elseif ($eligibleAccounts >= 5) {
                    $creator->tier = 'Bronze';
                } else {
                    $creator->tier = '-';
                }
            }

            // Filter berdasarkan tier (kalau user pilih filter tier)
            if ($request->tier && $creator->tier !== $request->tier) {
                $creator->hide = true; // kasih flag biar nanti dihapus
            }

            return $creator;
        });

        // Buang data yang tidak sesuai tier filter
        if ($request->tier) {
            $creators = $creators->reject(function ($creator) {
                return isset($creator->hide) && $creator->hide === true;
            });
        }

        // Kembalikan collection ke DataTables
        return DataTables::of($creators)->make(true);
    }
    public function getRekrutBuzzer(Request $request)
    {
        // Ambil referral_code semua creator dengan jenis KOL = Buzzer
        $buzzerReferralCodes = DB::table('creator_partner')
            ->where('jenis_kol', 'KOL as a Buzzer')
            ->pluck('referral_code');

        // Ambil semua rekruter yang referral_code-nya ada di list buzzer
        $rekrut = DB::table('rekruter_kol')
            ->whereIn('referral_code', $buzzerReferralCodes)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform hasil biar sesuai table kamu
        $rekrut->transform(function ($item) {
            // Hitung total topup untuk email rekruter ini
            $totalTopup = DB::table('revenue_kol')
                ->where('email', $item->email)
                ->sum('jumlah_top_up');

            // Tentukan nilai minimal topup & remarks
            $minTopup = 250000; // karena jenis_kol = Buzzer
            $item->nilai_min_topup = $minTopup;
            $item->jumlah_top_up = $totalTopup;
            $item->remarks = $totalTopup >= $minTopup ? 'Eligible' : 'Not Eligible';

            return $item;
        });

        return DataTables::of($rekrut)
            ->addColumn('nilai_min_topup', function ($row) {
                return number_format($row->nilai_min_topup, 0, ',', '.');
            })
            ->addColumn('jumlah_top_up', function ($row) {
                return number_format($row->jumlah_top_up, 0, ',', '.');
            })
            ->addColumn('remarks', function ($row) {
                return $row->remarks;
            })
            ->make(true);
    }
    public function getRekruterInfluencer(Request $request)
    {
        // Ambil referral_code semua creator dengan jenis KOL = Influencer
        $influencerReferralCodes = DB::table('creator_partner')
            ->where('jenis_kol', 'KOL as a Seller Online/Afiliate')
            ->pluck('referral_code');

        // Ambil semua rekruter yang referral_code-nya ada di list influencer
        $rekrut = DB::table('rekruter_kol')
            ->whereIn('referral_code', $influencerReferralCodes)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform hasil biar sesuai table kamu
        $rekrut->transform(function ($item) {
            // Hitung total topup untuk email rekruter ini
            $totalTopup = DB::table('revenue_kol')
                ->where('email', $item->email)
                ->sum('jumlah_top_up');

            // Tentukan nilai minimal topup & remarks
            $minTopup = 200000; // karena jenis_kol = Influencer
            $item->nilai_min_topup = $minTopup;
            $item->jumlah_top_up = $totalTopup;
            $item->remarks = $totalTopup >= $minTopup ? 'Eligible' : 'Not Eligible';

            return $item;
        });

        return DataTables::of($rekrut)
            ->addColumn('nilai_min_topup', function ($row) {
                return number_format($row->nilai_min_topup, 0, ',', '.');
            })
            ->addColumn('jumlah_top_up', function ($row) {
                return number_format($row->jumlah_top_up, 0, ',', '.');
            })
            ->addColumn('remarks', function ($row) {
                return $row->remarks;
            })
            ->make(true);
    }

    public function getAreaMarcom(Request $request)
    {
        $bulanIni = now()->format('Y-m'); // e.g. 2025-09

        // Ambil statistik per area (unikkan kol dengan COUNT DISTINCT)
        $areas = DB::table('creator_partner as cp')
            ->leftJoin('rekruter_kol as rk', DB::raw('cp.referral_code COLLATE utf8mb4_unicode_ci'), '=', DB::raw('rk.referral_code COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('revenue_kol as rv', DB::raw('rk.email COLLATE utf8mb4_unicode_ci'), '=', DB::raw('rv.email COLLATE utf8mb4_unicode_ci'))
            ->select(
                'cp.area',
                DB::raw("COUNT(DISTINCT cp.id) as total_kol"),
                DB::raw("SUM(CASE WHEN cp.jenis_kol = 'KOL as a Buzzer' THEN 1 ELSE 0 END) as jumlah_buzzer"),
                DB::raw("SUM(CASE WHEN cp.jenis_kol = 'KOL as a Seller Online/Afiliate' THEN 1 ELSE 0 END) as jumlah_influencer"),
                DB::raw("COUNT(DISTINCT rk.id) as total_rekruter"),
                DB::raw("COALESCE(SUM(rv.jumlah_top_up), 0) as total_topup"),
                // max topup dari revenue_kol yang terjadi pada bulan ini (untuk rule 3)
                DB::raw("MAX(CASE WHEN DATE_FORMAT(rv.created_at, '%Y-%m') = '{$bulanIni}' THEN rv.jumlah_top_up ELSE 0 END) as max_topup_bulan_ini")
            )
            ->groupBy('cp.area')
            ->orderBy('cp.area', 'asc')
            ->get();

        // Untuk setiap area: hitung bintang sesuai aturan:
        // 1) ada KOL -> 1 bintang
        // 2) ada minimal 1 creator di area yang punya >=5 akun rekruter eligible (eligible = akun punya total_topup >= minTopup tergantung jenis_kol)
        // 3) ada akun yang topup >= 1.000.000 pada bulan ini -> 1 bintang
        foreach ($areas as $areaRow) {
            $bintang = 0;

            // Rule 2: cek apakah ada creator di area yang sudah mencapai Tier >= Bronze
            // (Tier Bronze: ada >=5 rekruter dengan total_topup >= minTopup; minTopup berbeda per jenis_kol)
            $adaTierBronze = false;

            // Ambil semua creator di area (referral_code + jenis_kol)
            $creators = DB::table('creator_partner')
                ->where('area', $areaRow->area)
                ->select('referral_code', 'jenis_kol')
                ->get();

            foreach ($creators as $creator) {
                if (empty($creator->referral_code)) continue;

                // ambil list email rekruter untuk referral ini
                $emails = DB::table('rekruter_kol')
                    ->where('referral_code', $creator->referral_code)
                    ->pluck('email')
                    ->filter() // hapus null/empty
                    ->unique()
                    ->values()
                    ->toArray();

                if (empty($emails)) continue;

                // set minimum per jenis_kol
                $minTopup = $creator->jenis_kol === 'KOL as a Buzzer' ? 250000 : 200000;

                // ambil total topup per email (all-time)
                $topupPerEmail = DB::table('revenue_kol')
                    ->whereIn('email', $emails)
                    ->select('email', DB::raw('SUM(jumlah_top_up) as total'))
                    ->groupBy('email')
                    ->pluck('total')
                    ->toArray();

                // hitung berapa email yang memenuhi minTopup
                $eligibleCount = collect($topupPerEmail)->filter(fn($tot) => $tot >= $minTopup)->count();

                if ($eligibleCount >= 5) {
                    $adaTierBronze = true;
                    break; // cukup ada 1 creator yang memenuhi -> area dapat bintang tambahan
                }
            }

            if ($adaTierBronze) $bintang += 2;

            // Rule 3: ada akun rekruter yang topup >= 1jt pada bulan ini?
            if ((int)$areaRow->max_topup_bulan_ini >= 1000000) {
                $bintang++;
            }

            $areaRow->remarks = $bintang; // hanya kirim angka 0..3
        }

        return DataTables()->of($areas)
            ->addIndexColumn()
            ->make(true);
    }


    public function getSimpatiTiktok(Request $request)
    {
        $data = DB::table('summary_simpati_tiktok as sst')
            ->select(
                'sst.*'
            )
            ->orderBy('sst.created_at', 'desc')
            ->get();

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }

    public function getReferralChampionAm(Request $request)
    {
        $data = ReferralChampionAm::orderBy('created_at', 'desc')->get();

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }
    public function getSultamRacing(Request $request)
    {
        $data = SultamRacing::orderBy('created_at', 'desc')->get();

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }
    public function getVoucherStats()
    {
        // Menghitung total semua voucher
        $totalVoucher = DB::table('myads_voucher')->count();

        // Menghitung voucher yang sudah diklaim (user_id tidak null)
        $totalClaimed = DB::table('myads_voucher')->whereNotNull('user_id')->count();

        // Menghitung sisa voucher yang belum diklaim
        $totalNotClaimed = $totalVoucher - $totalClaimed;

        $data = [
            'total_voucher'   => $totalVoucher,
            'total_claimed'   => $totalClaimed,
            'total_not_claim' => $totalNotClaimed,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getVouchers(Request $request)
    {
        // Mengecek apakah ini adalah request AJAX dari DataTables
        if ($request->ajax()) {
            $data = DB::table('myads_voucher')
                ->select('id', 'voucher', 'created_at', 'user_id')
                ->orderBy('created_at', 'desc'); // Mengurutkan dari yang terbaru

            return DataTables::of($data)
                ->addColumn('status_klaim', function ($row) {
                    // Logika untuk menampilkan status klaim
                    if ($row->user_id) {
                        return 'claimed'; // Kirim 'claimed' jika sudah diklaim
                    }
                    return 'not_claimed'; // Kirim 'not_claimed' jika belum
                })
                ->addColumn('aksi', function ($row) {
                    // Menambahkan tombol Edit dan Hapus dengan ikon di setiap baris
                    $btn = '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-primary btn-sm editVoucher"><i class="fas fa-edit"></i> Edit</a> ';
                    $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-danger btn-sm hapusVoucher"><i class="fas fa-trash-alt"></i> Hapus</a>';
                    return $btn;
                })
                ->rawColumns(['aksi']) // Memberitahu DataTables bahwa kolom 'aksi' berisi HTML
                ->make(true);
        }
    }

    /**
     * Function 2: Menyimpan data voucher baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tambahVoucher(Request $request)
    {
        // Validasi input dari form
        $validator = Validator::make($request->all(), [
            'voucher' => 'required|string|max:255|unique:myads_voucher,voucher',
        ], [
            'voucher.required' => 'Kolom voucher wajib diisi.',
            'voucher.unique'   => 'Kode voucher ini sudah ada.',
        ]);

        // Jika validasi gagal, kembalikan pesan error
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Jika validasi berhasil, simpan data ke database
        DB::table('myads_voucher')->insert([
            'voucher'    => $request->voucher,
            // user_id bisa ditambahkan jika perlu, contoh: 'user_id' => auth()->id()
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['success' => 'Voucher berhasil ditambahkan.']);
    }

    /**
     * Function 3: Mengupdate data voucher yang sudah ada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVoucher(Request $request, $id)
    {
        // Validasi input, pastikan voucher unik tapi abaikan id saat ini
        $validator = Validator::make($request->all(), [
            'voucher' => 'required|string|max:255|unique:myads_voucher,voucher,' . $id,
        ], [
            'voucher.required' => 'Kolom voucher wajib diisi.',
            'voucher.unique'   => 'Kode voucher ini sudah digunakan oleh data lain.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cari voucher berdasarkan ID
        $voucher = DB::table('myads_voucher')->where('id', $id)->first();

        // Jika voucher tidak ditemukan
        if (!$voucher) {
            return response()->json(['error' => 'Data voucher tidak ditemukan.'], 404);
        }

        // Update data di database
        DB::table('myads_voucher')->where('id', $id)->update([
            'voucher'    => $request->voucher,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['success' => 'Voucher berhasil diperbarui.']);
    }

    /**
     * Function 4: Menghapus data voucher.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function hapusVoucher($id)
    {
        // Cari voucher berdasarkan ID
        $voucher = DB::table('myads_voucher')->where('id', $id)->first();

        // Jika voucher tidak ditemukan
        if (!$voucher) {
            return response()->json(['error' => 'Data voucher tidak ditemukan.'], 404);
        }

        // Hapus data dari database
        DB::table('myads_voucher')->where('id', $id)->delete();

        return response()->json(['success' => 'Voucher berhasil dihapus.']);
    }

    public function getClaimedVouchers(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('myads_voucher as mv')
                ->join('myads_user as mu', 'mv.user_id', '=', 'mu.id')
                ->whereNotNull('mv.user_id') // Hanya tampilkan voucher yang sudah ada user_id-nya
                ->select(
                    'mu.id as user_id', // ID dari tabel user untuk proses update
                    'mv.id as voucher_id', // ID dari tabel voucher untuk proses 'unclaim'
                    'mu.created_at as tanggal_daftar',
                    'mu.nama',
                    'mu.usaha',
                    'mu.email',
                    'mu.nomor_hp',
                    'mv.voucher as kode_voucher'
                );

            return DataTables::of($data)
                ->addColumn('aksi', function ($row) {
                    // Tombol Edit merujuk pada user_id, Tombol Hapus merujuk pada voucher_id
                    $btn = '<a href="javascript:void(0)" data-user-id="' . $row->user_id . '" class="btn btn-primary btn-sm editUser"><i class="fas fa-edit"></i> Edit User</a> ';
                    $btn .= '<a href="javascript:void(0)" data-voucher-id="' . $row->voucher_id . '" class="btn btn-warning btn-sm unclaimVoucher"><i class="fas fa-unlink"></i> Lepas Klaim</a>';
                    return $btn;
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }
    }

    /**
     * FUNGSI UPDATE: Mengupdate data user yang telah klaim voucher.
     */
    public function updateUser(Request $request, $user_id)
    {
        // Validasi input, pastikan email unik tapi abaikan email user saat ini
        $validator = Validator::make($request->all(), [
            'nama'      => 'required|string|max:191',
            'usaha'     => 'nullable|string|max:191',
            'email'     => 'required|email|max:191|unique:myads_user,email,' . $user_id,
            'nomor_hp'  => 'nullable|string|max:32',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique'  => 'Email ini sudah digunakan oleh user lain.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cari user berdasarkan ID
        $user = DB::table('myads_user')->where('id', $user_id)->first();
        if (!$user) {
            return response()->json(['error' => 'Data user tidak ditemukan.'], 404);
        }

        // Update data di tabel myads_user
        DB::table('myads_user')->where('id', $user_id)->update([
            'nama'       => $request->nama,
            'usaha'      => $request->usaha,
            'email'      => $request->email,
            'nomor_hp'   => $request->nomor_hp,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['success' => 'Data user berhasil diperbarui.']);
    }

    /**
     * FUNGSI DELETE: Melepaskan klaim voucher dari user (user_id di-set NULL).
     */
    public function unclaimVoucher($voucher_id)
    {
        // Cari voucher berdasarkan ID-nya
        $voucher = DB::table('myads_voucher')->where('id', $voucher_id)->first();

        if (!$voucher) {
            return response()->json(['error' => 'Data voucher tidak ditemukan.'], 404);
        }

        // Set kolom user_id menjadi NULL
        DB::table('myads_voucher')->where('id', $voucher_id)->update([
            'user_id' => null,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['success' => 'Klaim voucher berhasil dilepaskan.']);
    }
    public function downloadVouchers()
    {
        // 1. Tentukan nama file
        $fileName = 'claimed_vouchers_' . Carbon::now()->format('Y-m-d') . '.csv';

        // 2. Tentukan header untuk file CSV
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // 3. Kolom yang akan ada di file CSV
        $columns = ['Tanggal Daftar', 'Nama', 'Usaha', 'Email', 'Nomor HP', 'Kode Voucher'];

        // 4. Buat callback untuk streaming data
        $callback = function () use ($columns) {
            // Buka output stream
            $file = fopen('php://output', 'w');

            // Tulis baris header ke file CSV dengan delimiter ~
            fputcsv($file, $columns, '~');

            // Ambil data dari database
            $data = DB::table('myads_voucher as mv')
                ->join('myads_user as mu', 'mv.user_id', '=', 'mu.id')
                ->whereNotNull('mv.user_id')
                ->select(
                    'mu.created_at',
                    'mu.nama',
                    'mu.usaha',
                    'mu.email',
                    'mu.nomor_hp',
                    'mv.voucher'
                )
                ->orderBy('mu.created_at', 'desc')
                ->get();

            // Tulis setiap baris data ke file CSV
            foreach ($data as $row) {
                $rowData = [
                    Carbon::parse($row->created_at)->format('Y-m-d H:i:s'),
                    $row->nama,
                    $row->usaha,
                    $row->email,
                    $row->nomor_hp,
                    $row->voucher,
                ];
                fputcsv($file, $rowData, '~');
            }

            // Tutup output stream
            fclose($file);
        };

        // 5. Kembalikan response sebagai file download
        return response()->stream($callback, 200, $headers);
    }

    public function getLogLogin(Request $request)
    {
        $query = DB::table('loglogin')
            ->select([
                'loglogin.id',
                'loglogin.user_id',
                'loglogin.tgl',
                'loglogin.nama',
                'loglogin.role',
                'loglogin.email',
                'loglogin.created_at',
                'loglogin.updated_at',
            ])
            ->orderBy('loglogin.tgl', 'desc')
            ->orderBy('loglogin.updated_at', 'desc');

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('loglogin.tgl', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('loglogin.tgl', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('loglogin.tgl', '<=', $request->end_date);
        }

        // Filter by role
        if ($request->filled('role') && $request->role != 'all') {
            $query->where('loglogin.role', $request->role);
        }

        return DataTables::of($query)
            ->editColumn('tgl', function ($row) {
                return \Carbon\Carbon::parse($row->tgl)->format('d-m-Y');
            })
            ->editColumn('updated_at', function ($row) {
                return \Carbon\Carbon::parse($row->updated_at)->format('d-m-Y H:i:s');
            })
            ->addColumn('action', function ($row) {
                return '<span class="badge badge-success">Logged</span>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**     * Export PowerHouse Voucher Report to Excel
     */
    public function exportPowerHouseVoucher(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->get('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        \Log::info($startDate);
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();
        
        // Voucher codes untuk PowerHouse
        $powerHouseCodes = ['SUPER1', 'SUPER2', 'SUPER3', 'SUPER4', 'SUPER5', 'SUPER6', 'SUPER7', 'SUPER8'];
        
        // Query dengan JOIN untuk mendapatkan data lengkap
        $query = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'rb.email_client',
                'rb.company_name',
                'dv.voucher_code',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as amount'),
                DB::raw('CAST(rb.discount_voucer AS DECIMAL(15,2)) as discount'),
                DB::raw('CAST(rb.total_settlement_klien AS DECIMAL(15,2)) as total'),
                'rb.payment_method_name',
                'rb.paid_date'
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $powerHouseCodes)
            ->whereBetween('rb.paid_date', [$startDate, $endDate])
            ->orderBy('dv.voucher_code');

        $data = $query->get();
        // Mapping untuk PowerHouse names
        $powerHouseMapping = [
            'SUPER1' => 'Angga Satria Gusti',
            'SUPER2' => 'Abdul Halim',
            'SUPER3' => 'Raden Agie S. Akbar',
            'SUPER4' => 'Sony Widjaya',
            'SUPER5' => 'Deni Setiawan',
            'SUPER6' => 'Muhammad Arief Syahbana',
            'SUPER7' => 'Naqsyabandi',
            'SUPER8' => 'Ikrar Dharmawan',
        ];
        
        // Transform data untuk Excel
        $exportData = $data->map(function ($item) use ($powerHouseMapping) {
            $voucherCode = strtoupper($item->voucher_code);
            return [
                'Email' => $item->email_client,
                'Perusahaan' => $item->company_name,
                'Voucher Code' => $voucherCode,
                'PowerHouse' => $powerHouseMapping[$voucherCode] ?? '-',
                'Amount' => $item->amount,
                'Discount' => $item->discount,
                'Total Settlement' => $item->total,
                'Payment Method' => $item->payment_method_name,
                'Tanggal Pembayaran' => Carbon::parse($item->paid_date)->format('d-m-Y H:i:s')
            ];
        });
        
        // Create Excel file
        $fileName = 'PowerHouse_Referral_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        
        return response()->streamDownload(function () use ($exportData) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set header
            $headers = ['Email', 'Perusahaan', 'Voucher Code', 'PowerHouse', 'Amount', 'Discount', 'Total Settlement', 'Payment Method', 'Tanggal Pembayaran'];
            $sheet->fromArray($headers, null, 'A1');
            
            // Style header
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '667EEA']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($exportData as $item) {
                $sheet->fromArray((array)$item, null, 'A' . $row);
                $row++;
            }
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(25);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(18);
            $sheet->getColumnDimension('H')->setWidth(20);
            $sheet->getColumnDimension('I')->setWidth(20);
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }
    
    /**
     * Export Canvasser Voucher Report to Excel
     */
    public function exportCanvasserVoucher()
    {
        $monthParam = request()->get('month', Carbon::now()->format('Y-m-d'));
        $monthStart = Carbon::parse($monthParam)->startOfMonth();
        $currentMonth = $monthStart->format('Y-m');
        
        // Voucher codes untuk Canvasser
        $canvasserCodes = ['EXTRA1', 'EXTRA2', 'EXTRA3', 'EXTRA4', 'EXTRA5', 'EXTRA6', 'EXTRA7', 'EXTRA8', 'EXTRA9', 'EXTRA10', 'EXTRA11', 'EXTRA12', 'EXTRA13', 'EXTRA14', 'EXTRA15'];
        
        // Query dengan JOIN (per akun, bukan aggregate)
        $data = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'rb.email_client',
                'rb.company_name',
                'dv.voucher_code',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as amount'),
                'rb.payment_method_name',
                'rb.paid_date'
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $canvasserCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$currentMonth])
            ->orderBy('dv.voucher_code')
            ->orderBy('rb.email_client')
            ->get();
        
        // Mapping untuk Canvasser names
        $canvasserMapping = $this->getCanvasserOwnerMapForMonth($monthStart);
        
        // Transform data untuk Excel (per akun dengan insentif per akun)
        $showInsentif = $this->isCanvasserInsentifVisible($monthStart);
        $exportData = $data->map(function ($item) use ($canvasserMapping, $showInsentif) {
            $totalAmount = (float)$item->amount;
            // Insentif per akun: jika total > 500K, dapat 100K
            $insentif = $totalAmount > 500000 ? 100000 : 0;
            $canvasserName = $canvasserMapping[$item->voucher_code] ?? '';
            
            $row = [
                'Email' => $item->email_client,
                'Perusahaan' => $item->company_name,
                'Voucher Code' => $item->voucher_code,
                'Canvasser' => $canvasserName !== '' ? $canvasserName : '-',
                'Total Top Up' => $totalAmount,
                'Payment Method' => $item->payment_method_name,
                'Tanggal Pembayaran' => Carbon::parse($item->paid_date)->format('d-m-Y H:i:s')
            ];

            if ($showInsentif) {
                $row['Insentif'] = $insentif;
            }

            return $row;
        });
        
        // Create Excel file
        $fileName = 'Canvasser_Referral_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        
        return response()->streamDownload(function () use ($exportData, $showInsentif) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set header
            $headers = ['Email', 'Perusahaan', 'Voucher Code', 'Canvasser', 'Total Top Up'];
            if ($showInsentif) {
                $headers[] = 'Insentif';
            }
            $headers[] = 'Payment Method';
            $headers[] = 'Tanggal Pembayaran';
            $sheet->fromArray($headers, null, 'A1');
            
            // Style header
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '667EEA']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $lastColumn = $showInsentif ? 'H' : 'G';
            $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($exportData as $item) {
                $sheet->fromArray((array)$item, null, 'A' . $row);
                $row++;
            }
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(18);
            if ($showInsentif) {
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(20);
            } else {
                $sheet->getColumnDimension('F')->setWidth(20);
                $sheet->getColumnDimension('G')->setWidth(20);
            }
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }

    /**
     * Get PowerHouse Referral Report
     * Data dari JOIN report_balance_top_up + data_voucher dengan sistem POIN
     * 1 Poin = 1 juta rupiah
     * Added: Jumlah Leads dan Jumlah Visit dari LeadsMaster dan Booking
     */
    public function getPowerHouseVoucher(Request $request)
    {
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();
        
        // Voucher codes untuk PowerHouse
        $powerHouseCodes = ['SUPER1', 'SUPER2', 'SUPER3', 'SUPER4', 'SUPER5', 'SUPER6', 'SUPER7', 'SUPER8'];
        
        // Mapping voucher code ke nama PowerHouse
        $powerHouseMapping = [
            'SUPER1' => 'Angga Satria Gusti',
            'SUPER2' => 'Abdul Halim',
            'SUPER3' => 'Raden Agie S. Akbar',
            'SUPER4' => 'Sony Widjaya',
            'SUPER5' => 'Deni Setiawan',
            'SUPER6' => 'Muhammad Arief Syahbana',
            'SUPER7' => 'Naqsyabandi',
            'SUPER8' => 'Ikrar Dharmawan',
        ];

        // Get users associated with PowerHouse teams
        $powerHouseUserMap = [
            'Angga Satria Gusti' => ['Angga Satria Gusti', 'Angga S. Gusti'],
            'Abdul Halim' => ['Abdul Halim'],
            'Raden Agie S. Akbar' => ['Raden Agie Satria Akbar', 'Raden Agie S. Akbar'],
            'Sony Widjaya' => ['Sony Widjaya'],
            'Deni Setiawan' => ['Deni Setiawan'],
            'Muhammad Arief Syahbana' => ['Muhammad Arief Syahbana', 'Muhammad Arief'],
            'Naqsyabandi' => ['Naqsyabandi'],
            'Ikrar Dharmawan' => ['Ikrar Dharmawan'],
        ];

        // Ambil data dari JOIN report_balance_top_up + data_voucher
        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                DB::raw('COUNT(*) as jumlah_akun'),
                DB::raw('SUM(CAST(rb.amount AS DECIMAL(15,2))) as total_topup'),
                DB::raw('MAX(rb.tgl_transaksi) as tgl_transaksi_terakhir')
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $powerHouseCodes)
            ->whereBetween('rb.paid_date', [$startDate, $endDate])
            ->groupBy('dv.voucher_code')
            ->get()
            ->keyBy('voucher_code');

        // Build result dari mapping
        $result = [];
        foreach ($powerHouseMapping as $voucherCode => $powerHouseName) {
            $voucherInfo = $voucherData->get($voucherCode);
            
            // Count leads for this PowerHouse team (from leads_master table)
            $userNames = $powerHouseUserMap[$powerHouseName] ?? [];
            
            // Get user IDs for this PowerHouse
            $userIds = DB::table('users')
                ->whereIn('name', $userNames)
                ->pluck('id')
                ->toArray();

            // Count new leads created by this user in the selected month
            $jumlahLeads = 0;
            if (!empty($userIds)) {
                $jumlahLeads = DB::table('leads_master')
                    ->whereIn('user_id', $userIds)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();
            }

            // Count visits from bookings for this user in the selected month
            $jumlahVisit = 0;
            if (!empty($userNames)) {
                $jumlahVisit = DB::table('bookings')
                    ->whereIn('nama', $userNames)
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->count();
            }
            
            if ($voucherInfo) {
                $totalTopup = (float)($voucherInfo->total_topup ?? 0);
                // Hitung POIN: 1 Poin = 1 juta rupiah
                $poin = floor($totalTopup / 1000000);
                $jumlahAkun = (int)($voucherInfo->jumlah_akun ?? 0);
                $percentageLeadToVisit = $jumlahVisit > 0 ? ($jumlahLeads / $jumlahVisit) * 100 : 0;
                $percentageNewAkunToLead = $jumlahLeads > 0 ? ($jumlahAkun / $jumlahLeads) * 100 : 0;

                $tglFormatted = $voucherInfo->tgl_transaksi_terakhir ? 
                    Carbon::parse($voucherInfo->tgl_transaksi_terakhir)->format('d M Y') : '-';
                    
                $result[] = [
                    'referral_code' => $voucherCode,
                    'team_powerhouse' => $powerHouseName,
                    'jumlah_akun' => $jumlahAkun,
                    'jumlah_leads' => $jumlahLeads,
                    'jumlah_visit' => $jumlahVisit,
                    'percentage_lead_to_visit' => $percentageLeadToVisit,
                    'percentage_new_akun_to_lead' => $percentageNewAkunToLead,
                    'total_topup' => $totalTopup,
                    'poin' => $poin,
                    'tgl_transaksi_terakhir' => $tglFormatted,
                ];
            } else {
                // Voucher tanpa data
                $result[] = [
                    'referral_code' => $voucherCode,
                    'team_powerhouse' => $powerHouseName,
                    'jumlah_akun' => 0,
                    'jumlah_leads' => $jumlahLeads,
                    'jumlah_visit' => $jumlahVisit,
                    'percentage_lead_to_visit' => $jumlahVisit > 0 ? ($jumlahLeads / $jumlahVisit) * 100 : 0,
                    'percentage_new_akun_to_lead' => 0,
                    'total_topup' => 0,
                    'poin' => 0,
                    'tgl_transaksi_terakhir' => '-',
                ];
            }
        }

        return DataTables::of($result)
            ->addIndexColumn()
            ->editColumn('percentage_lead_to_visit', function ($row) {
                return number_format($row['percentage_lead_to_visit'], 2, ',', '.') . '%';
            })
            ->editColumn('percentage_new_akun_to_lead', function ($row) {
                return number_format($row['percentage_new_akun_to_lead'], 2, ',', '.') . '%';
            })
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('poin', function ($row) {
                return (int)$row['poin'];
            })
            ->make(true);
    }

    public function getPowerHouseDealTopupMom(Request $request)
    {
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $targetMonth = $request->get('month')
            ? Carbon::parse($request->get('month'))->format('Y-m')
            : $startDate->format('Y-m');

        $result = $this->buildPowerHouseDealTopupMomResult(
            $startDate,
            $endDate,
            function ($phUsers) use ($targetMonth) {
                return DB::table('target_ph')
                    ->whereIn('user_id', $phUsers->pluck('id')->all())
                    ->where('bulan', $targetMonth)
                    ->select('user_id', 'target')
                    ->get()
                    ->keyBy('user_id');
            }
        );

        return DataTables::of($result)
            ->addIndexColumn()
            ->addColumn('acv', function ($row) {
                $target = (float) ($row['target'] ?? 0);
                $total = (float) ($row['total_topup'] ?? 0);
                $acv = $target > 0 ? ($total / $target) * 100 : 0;
                return number_format($acv, 2, ',', '.') . '%';
            })
            ->addColumn('gap_to_target', function ($row) {
                $target = (float) ($row['target'] ?? 0);
                $total = (float) ($row['total_topup'] ?? 0);
                return 'Rp ' . number_format($target - $total, 0, ',', '.');
            })
            ->editColumn('top_up_new_akun_rp', function ($row) {
                return number_format($row['top_up_new_akun_rp'], 0, ',', '.');
            })
            ->editColumn('top_up_existing_akun_rp', function ($row) {
                return number_format($row['top_up_existing_akun_rp'], 0, ',', '.');
            })
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('target', function ($row) {
                return 'Rp ' . number_format($row['target'], 0, ',', '.');
            })
            ->editColumn('mom_prev_partial', function ($row) {
                return number_format($row['mom_prev_partial'], 0, ',', '.');
            })
            ->editColumn('mom_current_partial', function ($row) {
                return number_format($row['mom_current_partial'], 0, ',', '.');
            })
            ->editColumn('mom_prev_remaining', function ($row) {
                return number_format($row['mom_prev_remaining'], 0, ',', '.');
            })
            ->editColumn('mom_gap', function ($row) {
                return number_format($row['mom_gap'], 0, ',', '.');
            })
            ->make(true);
    }

    public function getPowerHouseSemesterDealTopupMom(Request $request)
    {
        $semesterPeriod = $this->resolveSemesterPeriod($request->get('semester'));
        $year = $semesterPeriod['year'];
        $semester = $semesterPeriod['semester'];
        $startDate = $semesterPeriod['start_date'];
        $endDate = $semesterPeriod['end_date'];

        $result = $this->buildPowerHouseDealTopupMomResult(
            $startDate,
            $endDate,
            function ($phUsers) use ($year, $semester) {
                if (!Schema::hasTable('target_ph_semesters')) {
                    return collect();
                }

                return DB::table('target_ph_semesters')
                    ->where('year', $year)
                    ->where('semester', $semester)
                    ->whereIn('team_powerhouse', $phUsers->pluck('name')->all())
                    ->select('team_powerhouse', 'target')
                    ->get()
                    ->keyBy('team_powerhouse');
            },
            $endDate
        );

        return DataTables::of($result)
            ->addIndexColumn()
            ->addColumn('acv', function ($row) {
                $target = (float) ($row['target'] ?? 0);
                $total = (float) ($row['total_topup'] ?? 0);
                $acv = $target > 0 ? ($total / $target) * 100 : 0;
                return number_format($acv, 2, ',', '.') . '%';
            })
            ->addColumn('gap_to_target', function ($row) {
                $target = (float) ($row['target'] ?? 0);
                $total = (float) ($row['total_topup'] ?? 0);
                return 'Rp ' . number_format($target - $total, 0, ',', '.');
            })
            ->editColumn('top_up_new_akun_rp', function ($row) {
                return number_format($row['top_up_new_akun_rp'], 0, ',', '.');
            })
            ->editColumn('top_up_existing_akun_rp', function ($row) {
                return number_format($row['top_up_existing_akun_rp'], 0, ',', '.');
            })
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('target', function ($row) {
                return 'Rp ' . number_format($row['target'], 0, ',', '.');
            })
            ->editColumn('mom_prev_partial', function ($row) {
                return number_format($row['mom_prev_partial'], 0, ',', '.');
            })
            ->editColumn('mom_current_partial', function ($row) {
                return number_format($row['mom_current_partial'], 0, ',', '.');
            })
            ->editColumn('mom_prev_remaining', function ($row) {
                return number_format($row['mom_prev_remaining'], 0, ',', '.');
            })
            ->editColumn('mom_gap', function ($row) {
                return number_format($row['mom_gap'], 0, ',', '.');
            })
            ->make(true);
    }

    public function getPowerHouseSemesterTargets(Request $request)
    {
        $semesterPeriod = $this->resolveSemesterPeriod($request->get('semester'));
        $year = $semesterPeriod['year'];
        $semester = $semesterPeriod['semester'];
        $periodLabel = $semesterPeriod['start_date']->format('d M Y') . ' s/d ' . $semesterPeriod['end_date']->format('d M Y');
        $semesterLabel = 'Semester ' . $semester . ' ' . $year;

        if (!Schema::hasTable('target_ph_semesters')) {
            $rows = DB::table('users as u')
                ->where('u.role', 'PH')
                ->where('u.name', '!=', 'self service')
                ->orderBy('u.name')
                ->select('u.name as team_powerhouse')
                ->get()
                ->map(function ($row) use ($semesterLabel, $periodLabel) {
                    return [
                        'team_powerhouse' => $row->team_powerhouse,
                        'semester_label' => $semesterLabel,
                        'period_label' => $periodLabel,
                        'target' => 'Rp 0',
                    ];
                });

            return DataTables::of($rows)
                ->addIndexColumn()
                ->make(true);
        }

        $rows = DB::table('users as u')
            ->leftJoin('target_ph_semesters as tps', function ($join) use ($year, $semester) {
                $join->on('u.name', '=', 'tps.team_powerhouse')
                    ->where('tps.year', '=', $year)
                    ->where('tps.semester', '=', $semester);
            })
            ->where('u.role', 'PH')
            ->where('u.name', '!=', 'self service')
            ->orderBy('u.name')
            ->select(
                'u.name as team_powerhouse',
                DB::raw("'" . $semesterLabel . "' as semester_label"),
                DB::raw("'" . $periodLabel . "' as period_label"),
                DB::raw('COALESCE(tps.target, 0) as target')
            )
            ->get()
            ->map(function ($row) {
                return [
                    'team_powerhouse' => $row->team_powerhouse,
                    'semester_label' => $row->semester_label,
                    'period_label' => $row->period_label,
                    'target' => 'Rp ' . number_format((float) $row->target, 0, ',', '.'),
                ];
            });

        return DataTables::of($rows)
            ->addIndexColumn()
            ->make(true);
    }

    /**
     * Get Canvasser Voucher Report
     * Data dari JOIN report_balance_top_up + data_voucher
     */
    public function getCanvasserVoucher(Request $request)
    {
        // Get month from request (format Y-m-d) or use current month
        $monthParam = $request->get('month', Carbon::now()->format('Y-m-d'));
        $monthStart = Carbon::parse($monthParam)->startOfMonth();
        // Extract only Y-m from the date parameter
        $month = $monthStart->format('Y-m');

        // Voucher codes untuk Canvasser
        $canvasserCodes = ['EXTRA1', 'EXTRA2', 'EXTRA3', 'EXTRA4', 'EXTRA5', 'EXTRA6', 'EXTRA7', 'EXTRA8', 'EXTRA9', 'EXTRA10', 'EXTRA11', 'EXTRA12', 'EXTRA13', 'EXTRA14', 'EXTRA15'];
        
        // Mapping voucher code ke nama canvasser
        $canvasserMapping = $this->getCanvasserOwnerMapForMonth($monthStart);

        // Ambil data dari JOIN report_balance_top_up + data_voucher (PER AKUN, bukan di-aggregate)
        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'rb.email_client',
                'rb.company_name',
                'dv.voucher_code',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as total_topup'),
                'rb.tgl_transaksi',
                'rb.paid_date'
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $canvasserCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$month])
            ->orderBy('dv.voucher_code')
            ->orderBy('rb.email_client')
            ->get();
        // dd($voucherData);
        // Build result per akun (per email_client)
        $result = [];
        foreach ($voucherData as $data) {
            $totalTopup = (float)$data->total_topup;
            // Insentif per akun: jika total top-up >= 500K, dapat 100K
            $insentif = $totalTopup >= 500000 ? 100000 : 0;

            $tglFormatted = $data->paid_date ? 
                Carbon::parse($data->paid_date)->format('d M Y') : '-';
                
            $canvasserName = $canvasserMapping[$data->voucher_code] ?? '';
            $result[] = [
                'referral_code' => $data->voucher_code,
                'canvasser' => $canvasserName !== '' ? $canvasserName : '-',
                'email_client' => $data->email_client,
                'company_name' => $data->company_name,
                'total_topup' => $totalTopup,
                'insentif' => $insentif,
                'tgl_transaksi_terakhir' => $tglFormatted,
            ];
        }

        return DataTables::of($result)
            ->addIndexColumn()
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('insentif', function ($row) {
                return $row['insentif'] > 0 ? 'Rp ' . number_format($row['insentif'], 0, ',', '.') : '-';
            })
            ->make(true);
    }

    /**
     * Get Canvasser Voucher Summary
     * Summary per canvasser (grouped by voucher code)
     * Total client count, total topup, total insentif
     */
    public function getCanvasserVoucherSummary(Request $request)
    {
        // Get month from request (format Y-m-d) or use current month
        $monthParam = $request->get('month', Carbon::now()->format('Y-m-d'));
        $monthStart = Carbon::parse($monthParam)->startOfMonth();
        // Extract only Y-m from the date parameter
        $month = $monthStart->format('Y-m');
        
        // Voucher codes untuk Canvasser
        $canvasserCodes = ['EXTRA1', 'EXTRA2', 'EXTRA3', 'EXTRA4', 'EXTRA5', 'EXTRA6', 'EXTRA7', 'EXTRA8', 'EXTRA9', 'EXTRA10', 'EXTRA11', 'EXTRA12', 'EXTRA13', 'EXTRA14', 'EXTRA15'];
        
        // Mapping voucher code ke nama canvasser
        $canvasserMapping = $this->getCanvasserOwnerMapForMonth($monthStart);

        // Ambil data dari JOIN report_balance_top_up + data_voucher
        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'dv.voucher_code',
                'rb.email_client',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as total_topup'),
                'rb.paid_date'
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $canvasserCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$month])
            ->orderBy('dv.voucher_code')
            ->orderBy('rb.email_client')
            ->get();

        // Group by voucher code and calculate summary
        $summaryData = [];
        $grouped = $voucherData->groupBy('voucher_code');
        
        foreach ($canvasserCodes as $voucherCode) {

            $items = $grouped[$voucherCode] ?? collect();

            $totalTopup = 0;
            $totalInsentif = 0;
            $totalClient = 0;

            foreach ($items as $item) {
                $amount = (float) $item->total_topup;
                $totalTopup += $amount;
                $totalClient++;

                if ($amount >= 500000) {
                    $totalInsentif += 100000;
                }
            }

            $canvasserName = $canvasserMapping[$voucherCode] ?? '';
            $summaryData[] = [
                'referral_code' => $voucherCode,
                'canvasser' => $canvasserName !== '' ? $canvasserName : '-',
                'total_client' => $totalClient,
                'total_topup' => $totalTopup,
                'total_insentif' => $totalInsentif,
            ];
        }

        return DataTables::of($summaryData)
            ->addIndexColumn()
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('total_insentif', function ($row) {
                return 'Rp ' . number_format($row['total_insentif'], 0, ',', '.');
            })
            ->make(true);
    }

    /**
     * Export Canvasser Voucher Summary to Excel
     */
    public function exportCanvasserVoucherSummary()
    {
        $monthParam = request()->get('month', Carbon::now()->format('Y-m-d'));
        $currentMonthStart = Carbon::parse($monthParam)->startOfMonth();
        $currentMonth = $currentMonthStart->format('Y-m');
        
        // Voucher codes untuk Canvasser
        $canvasserCodes = ['EXTRA1', 'EXTRA2', 'EXTRA3', 'EXTRA4', 'EXTRA5', 'EXTRA6', 'EXTRA7', 'EXTRA8', 'EXTRA9', 'EXTRA10', 'EXTRA11', 'EXTRA12', 'EXTRA13', 'EXTRA14', 'EXTRA15'];
        
        // Mapping voucher code ke nama canvasser
        $canvasserMapping = $this->getCanvasserOwnerMapForMonth($currentMonthStart);

        // Ambil data dari JOIN report_balance_top_up + data_voucher
        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'dv.voucher_code',
                'rb.email_client',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as total_topup')
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $canvasserCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$currentMonth])
            ->orderBy('dv.voucher_code')
            ->orderBy('rb.email_client')
            ->get();

        // Group by voucher code and calculate summary
        $showInsentif = $this->isCanvasserInsentifVisible($currentMonthStart);
        $summaryData = [];
        $grouped = $voucherData->groupBy('voucher_code');
        
        foreach ($canvasserCodes as $voucherCode) {
            $items = $grouped[$voucherCode] ?? collect();
            $totalTopup = 0;
            $totalInsentif = 0;
            $totalClient = 0;
            
            foreach ($items as $item) {
                $amount = (float)$item->total_topup;
                $totalTopup += $amount;
                $totalClient++;
                
                // Insentif per akun: jika total top-up >= 500K, dapat 100K
                if ($amount >= 500000) {
                    $totalInsentif += 100000;
                }
            }
            
            $canvasserName = $canvasserMapping[$voucherCode] ?? '';
            $row = [
                'referral_code' => $voucherCode,
                'canvasser' => $canvasserName !== '' ? $canvasserName : '-',
                'total_client' => $totalClient,
                'total_topup' => $totalTopup,
            ];
            if ($showInsentif) {
                $row['total_insentif'] = $totalInsentif;
            }
            $summaryData[] = $row;
        }

        // Create Excel file
        $fileName = 'Canvasser_Summary_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        
        return response()->streamDownload(function () use ($summaryData, $showInsentif) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set header
            $headers = ['Referral Code', 'Nama Canvasser', 'Total Client', 'Total Top Up'];
            if ($showInsentif) {
                $headers[] = 'Total Insentif';
            }
            $sheet->fromArray($headers, null, 'A1');
            
            // Style header
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '36B9CC']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
            ];
            $lastColumn = $showInsentif ? 'E' : 'D';
            $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($summaryData as $item) {
                $sheet->setCellValue('A' . $row, $item['referral_code']);
                $sheet->setCellValue('B' . $row, $item['canvasser']);
                $sheet->setCellValue('C' . $row, $item['total_client']);
                $sheet->setCellValue('D' . $row, $item['total_topup']);
                if ($showInsentif) {
                    $sheet->setCellValue('E' . $row, $item['total_insentif'] ?? 0);
                }
                $row++;
            }
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(20);
            if ($showInsentif) {
                $sheet->getColumnDimension('E')->setWidth(20);
            }
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }

    /**
     * Export Daily TopUp Report to Excel
     */
    public function exportDailyTopup()
    {
        try {
            // Get current month from request or use current month
            $monthFilter = request()->get('month');
            $leadProgramController = new LeadProgramController();
            $data = $leadProgramController->getDailyTopupData($monthFilter);

            if (empty($data)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk diekspor');
            }

            // Prepare export data
            $exportData = [];
            foreach ($data as $row) {
                $exportData[] = [
                    'Tanggal' => $row['date'],
                    'Mitra SBP (Settlement)' => $row['mitra_sbp_settle'],
                    'Mitra SBP (User)' => $row['mitra_sbp_user'],
                    'Internal (Settlement)' => $row['internal_settle'],
                    'Internal (User)' => $row['internal_user'],
                    'Canvasser (Settlement)' => $row['canvasser_settle'],
                    'Canvasser (User)' => $row['canvasser_user'],
                    'Self Service (Settlement)' => $row['self_service_settle'],
                    'Self Service (User)' => $row['self_service_user'],
                    'Agency (Settlement)' => $row['agency_settle'],
                    'Agency (User)' => $row['agency_user'],
                    'Total (Settlement)' => $row['total'],
                    'Total (User)' => $row['total_user'],
                ];
            }

            // Create Excel file
            $fileName = 'Daily_TopUp_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

            return response()->streamDownload(function () use ($exportData) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // Set header
                $headers = ['Tanggal', 'Mitra SBP (Settlement)', 'Mitra SBP (User)', 'Internal (Settlement)', 'Internal (User)', 
                            'Canvasser (Settlement)', 'Canvasser (User)', 'Self Service (Settlement)', 'Self Service (User)', 
                            'Agency (Settlement)', 'Agency (User)', 'Total (Settlement)', 'Total (User)'];
                $sheet->fromArray($headers, null, 'A1');

                // Style header
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '667EEA']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ];
                $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

                // Add data
                $row = 2;
                foreach ($exportData as $item) {
                    $sheet->fromArray((array)$item, null, 'A' . $row);
                    $row++;
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(18);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(18);
                $sheet->getColumnDimension('K')->setWidth(15);
                $sheet->getColumnDimension('L')->setWidth(18);
                $sheet->getColumnDimension('M')->setWidth(15);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error("Error in exportDailyTopup: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }

    public function exportRegional()
    {
        try {
            $leadProgramController = new LeadProgramController();
            $data = $leadProgramController->getRegionalData(request());

            if (empty($data)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk diekspor');
            }

            // Create Excel file
            $fileName = 'Regional_Report_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

            return response()->streamDownload(function () use ($data) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // Set header
                $headers = [
                    'No', 'Canvaser Name', 'Leads', 'Existing Akun', 'New Akun', 
                    'Top Up Existing Akun Count', 'Top Up New Akun (Rp)', 'Top Up Existing Akun (Rp)', 
                    'Total Top Up (Rp)', 'Target (Rp)', 'Achievement (%)', 'Gap (Rp)', 
                    'Gap Daily (Rp)', 'MOM (1-Current Date Prev)', 'MOM (1-Current Date Current)',
                    'MOM (Remaining Prev)', 'MOM Gap (Rp)'
                ];
                $sheet->fromArray($headers, null, 'A1');

                // Style header
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F54D9']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ];
                $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);

                // Add data
                $row = 2;
                $no = 1;
                foreach ($data as $item) {
                    $sheet->setCellValue('A' . $row, $no);
                    $sheet->setCellValue('B' . $row, $item['canvaser_name']);
                    $sheet->setCellValue('C' . $row, $item['leads']);
                    $sheet->setCellValue('D' . $row, $item['existing_akun']);
                    $sheet->setCellValue('E' . $row, $item['new_akun']);
                    $sheet->setCellValue('F' . $row, $item['top_up_existing_akun_count']);
                    $sheet->setCellValue('G' . $row, $item['top_up_new_akun_rp']);
                    $sheet->setCellValue('H' . $row, $item['top_up_existing_akun_rp']);
                    $sheet->setCellValue('I' . $row, $item['total_top_up_rp']);
                    $sheet->setCellValue('J' . $row, $item['target']);
                    $sheet->setCellValue('K' . $row, $item['achievement_percent']);
                    $sheet->setCellValue('L' . $row, $item['gap']);
                    $sheet->setCellValue('M' . $row, $item['gap_daily']);
                    $sheet->setCellValue('N' . $row, $item['mom_prev_partial']);
                    $sheet->setCellValue('O' . $row, $item['mom_current_partial']);
                    $sheet->setCellValue('P' . $row, $item['mom_prev_remaining']);
                    $sheet->setCellValue('Q' . $row, $item['mom_gap']);
                    
                    // Add color for total rows
                    if ($item['is_total']) {
                        $styleTotal = [
                            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC107']],
                            'font' => ['bold' => true],
                        ];
                        $sheet->getStyle('A' . $row . ':Q' . $row)->applyFromArray($styleTotal);
                    }

                    $row++;
                    $no++;
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(18);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(18);
                $sheet->getColumnDimension('J')->setWidth(15);
                $sheet->getColumnDimension('K')->setWidth(15);
                $sheet->getColumnDimension('L')->setWidth(15);
                $sheet->getColumnDimension('M')->setWidth(15);
                $sheet->getColumnDimension('N')->setWidth(18);
                $sheet->getColumnDimension('O')->setWidth(18);
                $sheet->getColumnDimension('P')->setWidth(18);
                $sheet->getColumnDimension('Q')->setWidth(15);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error("Error in exportRegional: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }

    private function getMpccUsersWithVoucherCodes()
    {
        $voucherCodes = collect(range(1, 13))
            ->map(function ($index) {
                return 'HEBAT' . $index;
            })
            ->values();

        return DB::table('users')
            ->where('role', 'MPCC')
            ->select('id', 'name', 'area', 'branch')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function ($user, $index) use ($voucherCodes) {
                $user->voucher_code = $voucherCodes->get($index);
                return $user;
            })
            ->filter(function ($user) {
                return !empty($user->voucher_code);
            })
            ->values();
    }

    public function getMpccVoucherReport(Request $request)
    {
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $mpccUsers = $this->getMpccUsersWithVoucherCodes();
        if ($mpccUsers->isEmpty()) {
            return DataTables::of([])->addIndexColumn()->make(true);
        }

        $voucherCodes = $mpccUsers->pluck('voucher_code')->map(function ($code) {
            return strtoupper($code);
        })->values()->all();

        $userIds = $mpccUsers->pluck('id')->map(function ($id) {
            return (int) $id;
        })->values()->all();

        $startDateFormatted = $startDate->copy()->format('Y-m-d');
        $endDateFormatted = $endDate->copy()->format('Y-m-d');
        $transactionDateExpr = "DATE(COALESCE(rb.paid_date, rb.tgl_transaksi))";

        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                DB::raw('COUNT(DISTINCT LOWER(rb.email_client)) as jumlah_akun'),
                DB::raw('SUM(CAST(rb.amount AS DECIMAL(15,2))) as total_topup'),
                DB::raw('MAX(COALESCE(rb.paid_date, rb.tgl_transaksi)) as tgl_transaksi_terakhir')
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $voucherCodes)
            ->whereBetween(DB::raw($transactionDateExpr), [$startDateFormatted, $endDateFormatted])
            ->groupBy(DB::raw('UPPER(dv.voucher_code)'))
            ->get()
            ->keyBy('voucher_code');

        $leadAggByUser = DB::table('leads_master')
            ->whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as jumlah_leads'))
            ->get()
            ->keyBy('user_id');

        $result = [];
        foreach ($mpccUsers as $mpccUser) {
            $voucherCode = strtoupper($mpccUser->voucher_code);
            $voucherInfo = $voucherData->get($voucherCode);
            $jumlahLeads = (int) ($leadAggByUser[$mpccUser->id]->jumlah_leads ?? 0);
            $jumlahAkun = (int) ($voucherInfo->jumlah_akun ?? 0);
            $totalTopup = (float) ($voucherInfo->total_topup ?? 0);
            $poin = floor($totalTopup / 1000000);
            $percentageNewAkunToLead = $jumlahLeads > 0 ? ($jumlahAkun / $jumlahLeads) * 100 : 0;
            $tglFormatted = !empty($voucherInfo->tgl_transaksi_terakhir)
                ? Carbon::parse($voucherInfo->tgl_transaksi_terakhir)->format('d M Y')
                : '-';

            $result[] = [
                'referral_code' => $voucherCode,
                'team_powerhouse' => $mpccUser->name,
                'jumlah_akun' => $jumlahAkun,
                'jumlah_leads' => $jumlahLeads,
                'jumlah_visit' => 0,
                'percentage_lead_to_visit' => 0,
                'percentage_new_akun_to_lead' => $percentageNewAkunToLead,
                'total_topup' => $totalTopup,
                'poin' => $poin,
                'tgl_transaksi_terakhir' => $tglFormatted,
            ];
        }

        return DataTables::of($result)
            ->addIndexColumn()
            ->editColumn('percentage_lead_to_visit', function ($row) {
                return number_format($row['percentage_lead_to_visit'], 2, ',', '.') . '%';
            })
            ->editColumn('percentage_new_akun_to_lead', function ($row) {
                return number_format($row['percentage_new_akun_to_lead'], 2, ',', '.') . '%';
            })
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('poin', function ($row) {
                return (int) $row['poin'];
            })
            ->make(true);
    }

    public function getMpccDealTopupMom(Request $request)
    {
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $mpccUsers = $this->getMpccUsersWithVoucherCodes();
        if ($mpccUsers->isEmpty()) {
            return DataTables::of([])->addIndexColumn()->make(true);
        }

        $voucherCodes = $mpccUsers->pluck('voucher_code')->map(function ($code) {
            return strtoupper($code);
        })->values()->all();
        $userIds = $mpccUsers->pluck('id')->map(function ($id) {
            return (int) $id;
        })->values()->all();

        $startDateFormatted = $startDate->copy()->format('Y-m-d');
        $endDateFormatted = $endDate->copy()->format('Y-m-d');
        $transactionDateExpr = "DATE(COALESCE(rp.paid_date, rp.tgl_transaksi))";
        $targetMonth = (int) $startDate->copy()->month;
        $targetYear = (int) $startDate->copy()->year;

        $targetByUser = DB::table('mpcc_targets')
            ->whereIn('user_id', $userIds)
            ->where('year', $targetYear)
            ->where('month', $targetMonth)
            ->select('user_id', 'target_amount')
            ->get()
            ->keyBy('user_id');

        $topUpStatsByCode = DB::table('report_balance_top_up as rp')
            ->join('data_voucher as dv', 'rp.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                DB::raw('COUNT(rp.id) as top_up_count'),
                DB::raw('SUM(CAST(rp.amount AS DECIMAL(15,2))) as total_top_up_rp')
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $voucherCodes)
            ->whereBetween(DB::raw($transactionDateExpr), [$startDateFormatted, $endDateFormatted])
            ->groupBy(DB::raw('UPPER(dv.voucher_code)'))
            ->get()
            ->keyBy('voucher_code');

        $topUpNewAkunByCode = DB::table('data_registarsi_status_approveorreject as dt')
            ->join('report_balance_top_up as rp', function ($join) {
                $join->on(DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(rp.email_client)'))
                    ->whereRaw("DATE(COALESCE(rp.paid_date, rp.tgl_transaksi)) >= STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')");
            })
            ->join('data_voucher as dv', 'rp.no_invoice', '=', 'dv.id_transaksi')
            ->where('dt.status', 'APPROVE')
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $voucherCodes)
            ->whereBetween(DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')"), [$startDateFormatted, $endDateFormatted])
            ->whereBetween(DB::raw($transactionDateExpr), [$startDateFormatted, $endDateFormatted])
            ->groupBy(DB::raw('UPPER(dv.voucher_code)'))
            ->select(
                DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                DB::raw('COUNT(DISTINCT rp.id) as top_up_count'),
                DB::raw('SUM(CAST(rp.amount AS DECIMAL(15,2))) as top_up_new_akun_rp')
            )
            ->get()
            ->keyBy('voucher_code');

        $momReference = $endDate->copy()->endOfDay();
        $currentMonthStart = $momReference->copy()->startOfMonth()->format('Y-m-d');
        $currentMonthUntilRef = $momReference->copy()->format('Y-m-d');
        $prevMonthRef = $momReference->copy()->subMonthNoOverflow();
        $prevMonthStart = $prevMonthRef->copy()->startOfMonth()->format('Y-m-d');
        $prevMonthSameDay = $prevMonthRef->copy()->format('Y-m-d');
        $prevMonthEnd = $prevMonthRef->copy()->endOfMonth()->format('Y-m-d');
        $prevMonthRemainingStart = $prevMonthRef->copy()->addDay()->format('Y-m-d');

        $momByCode = DB::table('report_balance_top_up as rp')
            ->join('data_voucher as dv', 'rp.no_invoice', '=', 'dv.id_transaksi')
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $voucherCodes)
            ->groupBy(DB::raw('UPPER(dv.voucher_code)'))
            ->select(
                DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                DB::raw("SUM(CASE WHEN DATE(COALESCE(rp.paid_date, rp.tgl_transaksi)) BETWEEN '{$prevMonthStart}' AND '{$prevMonthSameDay}' THEN CAST(rp.amount AS DECIMAL(15,2)) ELSE 0 END) as mom_prev_partial"),
                DB::raw("SUM(CASE WHEN DATE(COALESCE(rp.paid_date, rp.tgl_transaksi)) BETWEEN '{$currentMonthStart}' AND '{$currentMonthUntilRef}' THEN CAST(rp.amount AS DECIMAL(15,2)) ELSE 0 END) as mom_current_partial"),
                DB::raw("SUM(CASE WHEN DATE(COALESCE(rp.paid_date, rp.tgl_transaksi)) BETWEEN '{$prevMonthRemainingStart}' AND '{$prevMonthEnd}' THEN CAST(rp.amount AS DECIMAL(15,2)) ELSE 0 END) as mom_prev_remaining")
            )
            ->get()
            ->keyBy('voucher_code');

        $result = [];
        foreach ($mpccUsers as $mpccUser) {
            $voucherCode = strtoupper($mpccUser->voucher_code);
            $topUpCount = (int) ($topUpStatsByCode[$voucherCode]->top_up_count ?? 0);
            $newAkunCount = (int) ($topUpNewAkunByCode[$voucherCode]->top_up_count ?? 0);
            $newAkunRp = (float) ($topUpNewAkunByCode[$voucherCode]->top_up_new_akun_rp ?? 0);
            $totalTopup = (float) ($topUpStatsByCode[$voucherCode]->total_top_up_rp ?? 0);
            $existingAkunCount = max($topUpCount - $newAkunCount, 0);
            $existingAkunRp = max($totalTopup - $newAkunRp, 0);
            $momPrevPartial = (float) ($momByCode[$voucherCode]->mom_prev_partial ?? 0);
            $momCurrentPartial = (float) ($momByCode[$voucherCode]->mom_current_partial ?? 0);
            $momPrevRemaining = (float) ($momByCode[$voucherCode]->mom_prev_remaining ?? 0);
            $momGap = $momCurrentPartial - $momPrevPartial;
            $target = (float) ($targetByUser[$mpccUser->id]->target_amount ?? 0);

            $result[] = [
                'team_powerhouse' => $mpccUser->name,
                'target' => $target,
                'deal_topup_new_akun' => $newAkunCount,
                'deal_topup_existing_akun' => $existingAkunCount,
                'top_up_new_akun_rp' => $newAkunRp,
                'top_up_existing_akun_rp' => $existingAkunRp,
                'total_topup' => $totalTopup,
                'mom_prev_partial' => $momPrevPartial,
                'mom_current_partial' => $momCurrentPartial,
                'mom_prev_remaining' => $momPrevRemaining,
                'mom_gap' => $momGap,
            ];
        }

        return DataTables::of($result)
            ->addIndexColumn()
            ->addColumn('acv', function ($row) {
                $target = (float) ($row['target'] ?? 0);
                $total = (float) ($row['total_topup'] ?? 0);
                $acv = $target > 0 ? ($total / $target) * 100 : 0;
                return number_format($acv, 2, ',', '.') . '%';
            })
            ->editColumn('top_up_new_akun_rp', function ($row) {
                return number_format($row['top_up_new_akun_rp'], 0, ',', '.');
            })
            ->editColumn('top_up_existing_akun_rp', function ($row) {
                return number_format($row['top_up_existing_akun_rp'], 0, ',', '.');
            })
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('target', function ($row) {
                return 'Rp ' . number_format($row['target'], 0, ',', '.');
            })
            ->editColumn('mom_prev_partial', function ($row) {
                return number_format($row['mom_prev_partial'], 0, ',', '.');
            })
            ->editColumn('mom_current_partial', function ($row) {
                return number_format($row['mom_current_partial'], 0, ',', '.');
            })
            ->editColumn('mom_prev_remaining', function ($row) {
                return number_format($row['mom_prev_remaining'], 0, ',', '.');
            })
            ->editColumn('mom_gap', function ($row) {
                return number_format($row['mom_gap'], 0, ',', '.');
            })
            ->make(true);
    }

    public function exportMpccVoucher(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->get('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $mpccUsers = $this->getMpccUsersWithVoucherCodes();
        $voucherCodes = $mpccUsers->pluck('voucher_code')->map(function ($code) {
            return strtoupper($code);
        })->values()->all();

        $userMapByCode = [];
        foreach ($mpccUsers as $mpccUser) {
            $userMapByCode[strtoupper($mpccUser->voucher_code)] = $mpccUser;
        }

        $data = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'rb.email_client',
                'rb.company_name',
                DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as amount'),
                DB::raw('CAST(rb.discount_voucer AS DECIMAL(15,2)) as discount'),
                DB::raw('CAST(rb.total_settlement_klien AS DECIMAL(15,2)) as total'),
                'rb.payment_method_name',
                'rb.paid_date'
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $voucherCodes)
            ->whereBetween(DB::raw("DATE(COALESCE(rb.paid_date, rb.tgl_transaksi))"), [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('dv.voucher_code')
            ->orderBy('rb.paid_date')
            ->get();

        $exportData = $data->map(function ($item) use ($userMapByCode) {
            $user = $userMapByCode[$item->voucher_code] ?? null;

            return [
                'Email' => $item->email_client,
                'Perusahaan' => $item->company_name,
                'Voucher Code' => $item->voucher_code,
                'MPCC' => $user->name ?? '-',
                'Area' => $user->area ?? '-',
                'Branch' => $user->branch ?? '-',
                'Amount' => $item->amount,
                'Discount' => $item->discount,
                'Total Settlement' => $item->total,
                'Payment Method' => $item->payment_method_name,
                'Tanggal Pembayaran' => $item->paid_date ? Carbon::parse($item->paid_date)->format('d-m-Y H:i:s') : '-',
            ];
        });

        $fileName = 'MPCC_Report_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($exportData) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = ['Email', 'Perusahaan', 'Voucher Code', 'MPCC', 'Area', 'Branch', 'Amount', 'Discount', 'Total Settlement', 'Payment Method', 'Tanggal Pembayaran'];
            $sheet->fromArray($headers, null, 'A1');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '667EEA']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

            $row = 2;
            foreach ($exportData as $item) {
                $sheet->fromArray((array) $item, null, 'A' . $row);
                $row++;
            }

            foreach (range('A', 'K') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }
    public function getMpccAreaBranchReport(Request $request)
    {
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->get('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $rows = $this->buildMpccAreaBranchRows($startDate, $endDate);

        return DataTables::of($rows)
            ->addIndexColumn()
            ->editColumn('target_revenue_cluster_billion', function ($row) {
                return number_format($row['target_revenue_cluster_billion'], 2, ',', '.');
            })
            ->editColumn('target_revenue_branch_billion', function ($row) {
                return number_format($row['target_revenue_branch_billion'], 2, ',', '.');
            })
            ->editColumn('achievement', function ($row) {
                return number_format($row['achievement'], 2, ',', '.') . '%';
            })
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->make(true);
    }

    public function exportMpccAreaBranchReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->get('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfDay();

        $rows = collect($this->buildMpccAreaBranchRows($startDate, $endDate));
        $fileName = 'MPCC_Area_Branch_Report_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($rows) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = [
                'Area',
                'Branch',
                'Cluster',
                'Jumlah MPCC',
                'Target Revenue Cluster (B)',
                'Target Revenue Branch (B)',
                'Target Visit',
                'Target Leads',
                'Target Registrasi',
                'Actual Visit',
                'Jumlah Leads',
                'Jumlah Akun',
                'Total Top Up',
                'Achievement (%)',
                'Tgl Transaksi Terakhir'
            ];
            $sheet->fromArray($headers, null, 'A1');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '667EEA']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

            $rowNumber = 2;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    $row['area'],
                    $row['branch'],
                    $row['cluster'],
                    $row['jumlah_mpcc'],
                    $row['target_revenue_cluster_billion'],
                    $row['target_revenue_branch_billion'],
                    $row['target_visit'],
                    $row['target_leads'],
                    $row['target_registrasi'],
                    $row['actual_visit'],
                    $row['jumlah_leads'],
                    $row['jumlah_akun'],
                    $row['total_topup'],
                    number_format($row['achievement'], 2, ',', '.') . '%',
                    $row['tgl_transaksi_terakhir'],
                ], null, 'A' . $rowNumber);
                $rowNumber++;
            }

            foreach (range('A', 'O') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }

    public function showMpccPilotCityReport(Request $request)
    {
        $availableYears = DB::table('mpcc_branch_targets')
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->merge(
                DB::table('mpcc_targets')
                    ->select('year')
                    ->distinct()
                    ->pluck('year')
            )
            ->push(Carbon::now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $availableMonths = $availableYears
            ->flatMap(function ($year) {
                return collect(range(1, 12))->map(function ($month) use ($year) {
                    $value = sprintf('%04d-%02d', $year, $month);

                    return [
                        'value' => $value,
                        'label' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
                    ];
                })->reverse()->values();
            })
            ->values();

        $defaultMonth = Carbon::now()->format('Y-m');
        if (!$availableMonths->contains(fn($month) => $month['value'] === $defaultMonth)) {
            $defaultMonth = $availableMonths->first()['value'] ?? $defaultMonth;
        }
        $selectedMonth = $request->get('month', $defaultMonth);

        if (!preg_match('/^\d{4}-\d{2}$/', (string) $selectedMonth)) {
            $selectedMonth = $defaultMonth;
        }

        $monthDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $report = $this->buildMpccPilotCityReport($monthDate);

        return view('admin.mpcc_pilot_city_report', [
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'reportConfig' => $report['config'],
            'reportRows' => $report['rows'],
            'missingDataNotes' => $report['missing_data_notes'],
            'sourceNotes' => $report['source_notes'],
            'periodLabel' => $monthDate->translatedFormat('F Y'),
        ]);
    }

    private function buildMpccAreaBranchRows(Carbon $startDate, Carbon $endDate): array
    {
        $mpccUsers = $this->getMpccUsersWithVoucherCodes();
        if ($mpccUsers->isEmpty()) {
            return [];
        }

        $voucherCodes = $mpccUsers->pluck('voucher_code')->map(function ($code) {
            return strtoupper($code);
        })->values()->all();

        $userIds = $mpccUsers->pluck('id')->map(function ($id) {
            return (int) $id;
        })->values()->all();

        $targetMonth = (int) $startDate->copy()->month;
        $targetYear = (int) $startDate->copy()->year;
        $startDateFormatted = $startDate->copy()->format('Y-m-d');
        $endDateFormatted = $endDate->copy()->format('Y-m-d');
        $transactionDateExpr = "DATE(COALESCE(rb.paid_date, rb.tgl_transaksi))";

        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                DB::raw('COUNT(DISTINCT LOWER(rb.email_client)) as jumlah_akun'),
                DB::raw('SUM(CAST(rb.amount AS DECIMAL(15,2))) as total_topup'),
                DB::raw('MAX(COALESCE(rb.paid_date, rb.tgl_transaksi)) as tgl_transaksi_terakhir')
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $voucherCodes)
            ->whereBetween(DB::raw($transactionDateExpr), [$startDateFormatted, $endDateFormatted])
            ->groupBy(DB::raw('UPPER(dv.voucher_code)'))
            ->get()
            ->keyBy('voucher_code');

        $leadAggByUser = DB::table('leads_master')
            ->whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as jumlah_leads'))
            ->get()
            ->keyBy('user_id');

        $visitAggByName = DB::table('bookings')
            ->whereIn('nama', $mpccUsers->pluck('name')->values()->all())
            ->whereBetween('tanggal', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('nama')
            ->select('nama', DB::raw('COUNT(*) as jumlah_visit'))
            ->get()
            ->keyBy('nama');

        $targetRows = DB::table('mpcc_branch_targets')
            ->where('year', $targetYear)
            ->where('month', $targetMonth)
            ->get()
            ->mapWithKeys(function ($row) {
                return [$this->normalizeMpccBranchKey($row->branch) => $row];
            });

        $branchClusterCounts = $mpccUsers
            ->map(function ($user) {
                $rawBranch = trim((string) ($user->branch ?: '-'));

                return [
                    'branch' => $this->resolveMpccBranchName($rawBranch),
                    'cluster' => $this->resolveMpccClusterName($rawBranch),
                ];
            })
            ->groupBy('cluster')
            ->map(function ($items) {
                return max(1, $items->unique('branch')->count());
            });

        $clusterTargetTotals = $targetRows
            ->flatMap(function ($row) {
                $branch = trim((string) ($row->branch ?: '-'));
                $clusters = $this->resolveMpccTargetClusters($branch);
                $clusterCount = max(1, count($clusters));

                return collect($clusters)->map(function ($cluster) use ($row, $clusterCount) {
                    return [
                        'cluster' => $cluster,
                        'target_revenue_cluster_billion' => (float) ($row->target_revenue_cluster_billion ?? 0) / $clusterCount,
                        'target_revenue_branch_billion' => (float) ($row->target_revenue_branch_billion ?? 0) / $clusterCount,
                        'target_visit' => (int) round(((int) ($row->target_visit ?? 0)) / $clusterCount),
                        'target_leads' => (int) round(((int) ($row->target_leads ?? 0)) / $clusterCount),
                        'target_registrasi' => (int) round(((int) ($row->target_registrasi ?? 0)) / $clusterCount),
                        'target_topup' => (int) round(((int) ($row->target_topup ?? 0)) / $clusterCount),
                    ];
                });
            })
            ->groupBy('cluster')
            ->map(function ($items) {
                return [
                    'target_revenue_cluster_billion' => (float) $items->sum('target_revenue_cluster_billion'),
                    'target_revenue_branch_billion' => (float) $items->sum('target_revenue_branch_billion'),
                    'target_visit' => (int) $items->sum('target_visit'),
                    'target_leads' => (int) $items->sum('target_leads'),
                    'target_registrasi' => (int) $items->sum('target_registrasi'),
                    'target_topup' => (int) $items->sum('target_topup'),
                ];
            });
        $grouped = [];
        $jakartaArea = trim((string) ($mpccUsers->first(function ($user) {
            return str_contains($this->normalizeMpccBranchKey($user->branch ?? null), 'jakarta');
        })->area ?? 'Area 2'));

        foreach (['Cluster Jakarta Barat', 'Cluster Jakarta Utara', 'Cluster Jakarta Pusat Selatan', 'Cluster Jakarta Timur'] as $cluster) {
            $branchName = in_array($cluster, ['Cluster Jakarta Barat', 'Cluster Jakarta Utara'], true)
                ? 'Jakarta Northern'
                : 'Jakarta Southern';
            $groupKey = $jakartaArea . '||' . $cluster . '||' . $branchName;
            $clusterTarget = $clusterTargetTotals->get($cluster, [
                'target_revenue_cluster_billion' => 0,
                'target_revenue_branch_billion' => 0,
                'target_visit' => 0,
                'target_leads' => 0,
                'target_registrasi' => 0,
                'target_topup' => 0,
            ]);
            $clusterBranchCount = (int) ($branchClusterCounts->get($cluster) ?? 1);

            $grouped[$groupKey] = [
                'area' => $jakartaArea,
                'cluster' => $cluster,
                'jumlah_mpcc' => 0,
                'branch' => $branchName,
                'target_revenue_cluster_billion' => (float) ($clusterTarget['target_revenue_cluster_billion'] ?? 0),
                'target_revenue_branch_billion' => (float) (($clusterTarget['target_revenue_branch_billion'] ?? 0) / $clusterBranchCount),
                'target_visit' => (int) round(($clusterTarget['target_visit'] ?? 0) / $clusterBranchCount),
                'target_leads' => (int) round(($clusterTarget['target_leads'] ?? 0) / $clusterBranchCount),
                'target_registrasi' => (int) round(($clusterTarget['target_registrasi'] ?? 0) / $clusterBranchCount),
                'target_topup' => (int) round(($clusterTarget['target_topup'] ?? 0) / $clusterBranchCount),
                'actual_visit' => 0,
                'jumlah_leads' => 0,
                'jumlah_akun' => 0,
                'achievement' => 0,
                'total_topup' => 0,
                'tgl_transaksi_terakhir' => '-',
                '_last_transaction_at' => null,
            ];
        }

        foreach ($mpccUsers as $mpccUser) {
            $voucherCode = strtoupper($mpccUser->voucher_code);
            $voucherInfo = $voucherData->get($voucherCode);
            $area = trim((string) ($mpccUser->area ?: '-'));
            $rawBranch = trim((string) ($mpccUser->branch ?: '-'));
            $branch = $this->resolveMpccBranchName($rawBranch);
            $cluster = $this->resolveMpccClusterName($rawBranch);
            $groupKey = $area . '||' . $cluster . '||' . $branch;
            $clusterTarget = $clusterTargetTotals->get($cluster, [
                'target_revenue_cluster_billion' => 0,
                'target_revenue_branch_billion' => 0,
                'target_visit' => 0,
                'target_leads' => 0,
                'target_registrasi' => 0,
                'target_topup' => 0,
            ]);
            $clusterBranchCount = (int) ($branchClusterCounts->get($cluster) ?? 1);

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'area' => $area,
                    'cluster' => $cluster,
                    'branch' => $branch,
                    'jumlah_mpcc' => 0,
                    'target_revenue_cluster_billion' => (float) ($clusterTarget['target_revenue_cluster_billion'] ?? 0),
                    'target_revenue_branch_billion' => (float) (($clusterTarget['target_revenue_branch_billion'] ?? 0) / $clusterBranchCount),
                    'target_visit' => (int) round(($clusterTarget['target_visit'] ?? 0) / $clusterBranchCount),
                    'target_leads' => (int) round(($clusterTarget['target_leads'] ?? 0) / $clusterBranchCount),
                    'target_registrasi' => (int) round(($clusterTarget['target_registrasi'] ?? 0) / $clusterBranchCount),
                    'target_topup' => (int) round(($clusterTarget['target_topup'] ?? 0) / $clusterBranchCount),
                    'actual_visit' => 0,
                    'jumlah_leads' => 0,
                    'jumlah_akun' => 0,
                    'achievement' => 0,
                    'total_topup' => 0,
                    'tgl_transaksi_terakhir' => '-',
                    '_last_transaction_at' => null,
                ];
            }

            $jumlahLeads = (int) ($leadAggByUser[$mpccUser->id]->jumlah_leads ?? 0);
            $jumlahVisit = (int) ($visitAggByName[$mpccUser->name]->jumlah_visit ?? 0);
            $jumlahAkun = (int) ($voucherInfo->jumlah_akun ?? 0);
            $totalTopup = (float) ($voucherInfo->total_topup ?? 0);
            $lastTransaction = !empty($voucherInfo->tgl_transaksi_terakhir)
                ? Carbon::parse($voucherInfo->tgl_transaksi_terakhir)
                : null;

            $grouped[$groupKey]['jumlah_mpcc']++;
            $grouped[$groupKey]['actual_visit'] += $jumlahVisit;
            $grouped[$groupKey]['jumlah_leads'] += $jumlahLeads;
            $grouped[$groupKey]['jumlah_akun'] += $jumlahAkun;
            $grouped[$groupKey]['total_topup'] += $totalTopup;

            if ($lastTransaction && (
                is_null($grouped[$groupKey]['_last_transaction_at']) ||
                $lastTransaction->gt($grouped[$groupKey]['_last_transaction_at'])
            )) {
                $grouped[$groupKey]['_last_transaction_at'] = $lastTransaction;
                $grouped[$groupKey]['tgl_transaksi_terakhir'] = $lastTransaction->format('d M Y');
            }
        }

        return collect($grouped)
            ->map(function ($row) {
                $targetRevenueBranchRp = (float) $row['target_revenue_branch_billion'] * 1000000000;
                $row['achievement'] = $targetRevenueBranchRp > 0
                    ? ((float) $row['total_topup'] / $targetRevenueBranchRp) * 100
                    : 0;
                return $row;
            })
            ->sortBy(['area', 'cluster', 'branch'])
            ->values()
            ->all();
    }

    private function buildMpccPilotCityReport(Carbon $monthDate): array
    {
        $config = $this->getMpccPilotCityConfig();
        $cityKeys = collect($config)
            ->flatMap(function ($group) {
                return collect($group['cities'])->pluck('key');
            })
            ->values()
            ->all();

        $stats = [];
        foreach ($cityKeys as $cityKey) {
            $stats[$cityKey] = [
                'mpcc' => 0,
                'revenue_commitment' => 0,
                'visits' => 0,
                'leads' => 0,
                'customers' => 0,
                'total_topup' => 0,
            ];
        }

        $startDate = $monthDate->copy()->startOfMonth();
        $endDate = $monthDate->copy()->endOfMonth();
        $targetMonth = (int) $monthDate->month;
        $targetYear = (int) $monthDate->year;

        $mpccUsers = $this->getMpccUsersWithVoucherCodes();
        $voucherCodes = $mpccUsers->pluck('voucher_code')->map(function ($code) {
            return strtoupper($code);
        })->values()->all();
        $userIds = $mpccUsers->pluck('id')->map(function ($id) {
            return (int) $id;
        })->values()->all();

        $voucherData = collect();
        $leadAggByUser = collect();
        $visitAggByName = collect();

        if (!empty($voucherCodes)) {
            $transactionDateExpr = "DATE(COALESCE(rb.paid_date, rb.tgl_transaksi))";

            $voucherData = DB::table('report_balance_top_up as rb')
                ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
                ->select(
                    DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                    DB::raw('COUNT(DISTINCT LOWER(rb.email_client)) as jumlah_akun'),
                    DB::raw('SUM(CAST(rb.amount AS DECIMAL(15,2))) as total_topup')
                )
                ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $voucherCodes)
                ->whereBetween(DB::raw($transactionDateExpr), [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy(DB::raw('UPPER(dv.voucher_code)'))
                ->get()
                ->keyBy('voucher_code');
        }

        if (!empty($userIds)) {
            $leadAggByUser = DB::table('leads_master')
                ->whereIn('user_id', $userIds)
                ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                ->groupBy('user_id')
                ->select('user_id', DB::raw('COUNT(*) as jumlah_leads'))
                ->get()
                ->keyBy('user_id');

            $visitAggByName = DB::table('bookings')
                ->whereIn('nama', $mpccUsers->pluck('name')->values()->all())
                ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->groupBy('nama')
                ->select('nama', DB::raw('COUNT(*) as jumlah_visit'))
                ->get()
                ->keyBy('nama');
        }

        $targetRows = DB::table('mpcc_branch_targets')
            ->where('year', $targetYear)
            ->where('month', $targetMonth)
            ->select('branch', 'target_revenue_branch_billion')
            ->get();

        foreach ($targetRows as $targetRow) {
            $cityKey = $this->resolveMpccPilotCityKey($targetRow->branch);
            if (!$cityKey || !isset($stats[$cityKey])) {
                continue;
            }

            $branchTargetRevenue = (float) ($targetRow->target_revenue_branch_billion ?? 0);
            $stats[$cityKey]['revenue_commitment'] += $branchTargetRevenue;
        }

        foreach ($mpccUsers as $mpccUser) {
            $cityKey = $this->resolveMpccPilotCityKey($mpccUser->branch);
            if (!$cityKey || !isset($stats[$cityKey])) {
                continue;
            }

            $voucherCode = strtoupper($mpccUser->voucher_code);
            $voucherInfo = $voucherData->get($voucherCode);

            $stats[$cityKey]['mpcc']++;
            $stats[$cityKey]['visits'] += (int) ($visitAggByName[$mpccUser->name]->jumlah_visit ?? 0);
            $stats[$cityKey]['leads'] += (int) ($leadAggByUser[$mpccUser->id]->jumlah_leads ?? 0);
            $stats[$cityKey]['customers'] += (int) ($voucherInfo->jumlah_akun ?? 0);
            $stats[$cityKey]['total_topup'] += (float) ($voucherInfo->total_topup ?? 0);
        }

        $rows = [
            [
                'section' => 'basic',
                'label' => 'MPCC',
                'values' => $this->formatMpccPilotCityMetricRow($cityKeys, $stats, 'mpcc'),
            ],
            [
                'section' => 'basic',
                'label' => 'Local SBP',
                'values' => $this->buildUnavailableMetricRow($cityKeys),
            ],
            [
                'section' => 'basic',
                'label' => 'Number of resellers onboarded',
                'values' => $this->buildUnavailableMetricRow($cityKeys),
            ],
            [
                'section' => 'commitment',
                'label' => 'Revenue commitment',
                'values' => $this->formatMpccPilotCityMetricRow($cityKeys, $stats, 'revenue_commitment'),
            ],
            [
                'section' => 'performance',
                'label' => '# of visits',
                'values' => $this->formatMpccPilotCityMetricRow($cityKeys, $stats, 'visits'),
            ],
            [
                'section' => 'performance',
                'label' => '# of leads',
                'values' => $this->formatMpccPilotCityMetricRow($cityKeys, $stats, 'leads'),
            ],
            [
                'section' => 'performance',
                'label' => '# of customers',
                'values' => $this->formatMpccPilotCityMetricRow($cityKeys, $stats, 'customers'),
            ],
            [
                'section' => 'performance',
                'label' => 'Total top-up',
                'values' => $this->formatMpccPilotCityMetricRow($cityKeys, $stats, 'total_topup'),
            ],
        ];

        return [
            'config' => $config,
            'rows' => $rows,
            'missing_data_notes' => [
                'Mapping formal `Area VP` dan `pilot city` belum ditemukan di tabel database, jadi saat ini dipasang manual mengikuti contoh yang Anda kirim.',
                'Baris `Local SBP` belum bisa diisi akurat per pilot city karena tabel `mitra_sbp` hanya punya `area` dan `regional`, belum ada pemetaan langsung ke branch/kota pilot.',
                'Baris `Number of resellers onboarded` belum ada definisi dan sumber tabel/kolom yang tegas. Saya belum menemukan field `reseller` atau status `onboarded` yang bisa dihitung langsung.',
            ],
            'source_notes' => [
                'MPCC diambil dari `users.role = MPCC` dan dikelompokkan berdasarkan branch/kota pilot.',
                'Revenue commitment hanya diambil dari `mpcc_branch_targets.target_revenue_branch_billion` pada bulan yang dipilih dan ditampilkan dengan nilai aslinya.',
                'Visits diambil dari `bookings`, leads dari `leads_master`, customers dari transaksi voucher unik di `report_balance_top_up + data_voucher`, dan total top-up dari penjumlahan `report_balance_top_up.amount`.',
            ],
        ];
    }

    private function getMpccPilotCityConfig(): array
    {
        return [
            [
                'vp' => 'P. Saki Hamsat Bramono',
                'area' => '1',
                'cities' => [
                    ['key' => 'palembang', 'label' => 'Palembang'],
                    ['key' => 'medan', 'label' => 'Medan'],
                    ['key' => 'pekanbaru', 'label' => 'Pekanbaru'],
                ],
            ],
            [
                'vp' => 'B. Tuty R. Afriza',
                'area' => '2',
                'cities' => [
                    ['key' => 'bandung', 'label' => 'Bandung'],
                    ['key' => 'jakarta', 'label' => 'Jakarta'],
                ],
            ],
            [
                'vp' => 'P. Suryo Hadiyanto',
                'area' => '3',
                'cities' => [
                    ['key' => 'semarang', 'label' => 'Semarang'],
                    ['key' => 'denpasar', 'label' => 'Denpasar'],
                    ['key' => 'surabaya', 'label' => 'Surabaya'],
                ],
            ],
            [
                'vp' => 'P. Murhalis',
                'area' => '4',
                'cities' => [
                    ['key' => 'samarinda', 'label' => 'Samarinda'],
                    ['key' => 'makassar', 'label' => 'Makassar'],
                ],
            ],
        ];
    }

    private function resolveMpccPilotCityKey(?string $branch): ?string
    {
        $normalized = $this->normalizeMpccBranchKey($branch);

        $cityMap = [
            'palembang' => 'palembang',
            'medan' => 'medan',
            'pekanbaru' => 'pekanbaru',
            'bandung' => 'bandung',
            'northern jakarta' => 'jakarta',
            'southern jakarta' => 'jakarta',
            'jakarta barat' => 'jakarta',
            'jakarta utara' => 'jakarta',
            'jakarta pusat' => 'jakarta',
            'jakarta selatan' => 'jakarta',
            'jakarta timur' => 'jakarta',
            'semarang' => 'semarang',
            'denpasar' => 'denpasar',
            'surabaya' => 'surabaya',
            'samarinda' => 'samarinda',
            'makassar' => 'makassar',
        ];

        foreach ($cityMap as $keyword => $cityKey) {
            if ($normalized !== '' && str_contains($normalized, $keyword)) {
                return $cityKey;
            }
        }

        return null;
    }

    private function formatMpccPilotCityMetricRow(array $cityKeys, array $stats, string $metric): array
    {
        $formatted = [];

        foreach ($cityKeys as $cityKey) {
            $value = $stats[$cityKey][$metric] ?? 0;

            switch ($metric) {
                case 'revenue_commitment':
                    $formatted[] = 'Rp ' . number_format((float) $value, 0, ',', '.');
                    break;
                case 'total_topup':
                    $formatted[] = 'Rp ' . number_format((float) $value, 0, ',', '.');
                    break;
                default:
                    $formatted[] = number_format((int) $value, 0, ',', '.');
                    break;
            }
        }

        return $formatted;
    }

    private function buildUnavailableMetricRow(array $cityKeys): array
    {
        return array_fill(0, count($cityKeys), '-');
    }

    private function resolveMpccClusterName(?string $branch): string
    {
        $normalized = $this->normalizeMpccBranchKey($branch);

        $clusterMap = [
            'kota palembang' => 'Cluster Kota Palembang',
            'palembang' => 'Cluster Kota Palembang',
            'kota pekanbaru' => 'Cluster Kota Pekanbaru',
            'pekanbaru' => 'Cluster Kota Pekanbaru',
            'kota medan' => 'Cluster Kota Medan',
            'medan' => 'Cluster Kota Medan',
            'kota makassar' => 'Cluster Kota Makassar',
            'makassar' => 'Cluster Kota Makassar',
            'kota samarinda' => 'Cluster Kota Samarinda',
            'samarinda' => 'Cluster Kota Samarinda',
            'jakarta barat' => 'Cluster Jakarta Barat',
            'western jakarta' => 'Cluster Jakarta Barat',
            'jakarta utara' => 'Cluster Jakarta Utara',
            'northern jakarta' => 'Cluster Jakarta Utara',
            'jakarta pusat' => 'Cluster Jakarta Pusat Selatan',
            'jakarta selatan' => 'Cluster Jakarta Pusat Selatan',
            'southern jakarta' => 'Cluster Jakarta Pusat Selatan',
            'jakarta timur' => 'Cluster Jakarta Timur',
            'eastern jakarta' => 'Cluster Jakarta Timur',
            'kota bandung' => 'Cluster Kota Bandung',
            'bandung' => 'Cluster Kota Bandung',
            'denpasar' => 'Cluster Bali Tengah',
            'bali tengah' => 'Cluster Bali Tengah',
            'surabaya' => 'Cluster Surabaya',
            'semarang' => 'Cluster Semarang',
        ];

        foreach ($clusterMap as $keyword => $clusterName) {
            if ($normalized !== '' && str_contains($normalized, $keyword)) {
                return $clusterName;
            }
        }

        return 'Cluster Lainnya';
    }

    private function resolveMpccTargetClusters(?string $branch): array
    {
        $normalized = $this->normalizeMpccBranchKey($branch);

        if ($normalized !== '' && str_contains($normalized, 'jakarta')) {
            return ['Cluster Jakarta Barat', 'Cluster Jakarta Utara', 'Cluster Jakarta Pusat Selatan', 'Cluster Jakarta Timur'];
        }
        return [$this->resolveMpccClusterName($branch)];
    }

    private function resolveMpccBranchName(?string $branch): string
    {
        $normalized = $this->normalizeMpccBranchKey($branch);

        if ($normalized === 'northern jakarta') {
            return 'Jakarta Northern';
        }

        if ($normalized === 'southern jakarta') {
            return 'Jakarta Southern';
        }

        return trim((string) ($branch ?: '-'));
    }
    private function normalizeMpccBranchKey(?string $branch): string
    {
        return strtolower(trim((string) $branch));
    }
    private function getCanvasserOwnerMapForMonth(Carbon $monthStart): array
    {
        $defaultMapping = [
            'EXTRA1' => 'Amanah',
            'EXTRA2' => 'Indah',
            'EXTRA3' => 'Maria',
            'EXTRA4' => 'Meisya',
            'EXTRA5' => 'Hardi',
            'EXTRA6' => 'Bustomi',
            'EXTRA7' => 'Intan',
            'EXTRA8' => 'Hika Rochmah',
            'EXTRA9' => 'Akbar Zikron',
            'EXTRA10' => 'Riva',
            'EXTRA11' => 'Fanni',
            'EXTRA12' => 'Maiph',
            'EXTRA13' => 'Nyayu Z. Septianita',
            'EXTRA14' => 'Afan',
            'EXTRA15' => 'Herman',
        ];

        $historyCodes = DB::table('voucher_owner_history')
            ->whereIn('voucher_code', array_keys($defaultMapping))
            ->distinct()
            ->pluck('voucher_code')
            ->toArray();

        $baseMapping = $defaultMapping;
        foreach ($historyCodes as $code) {
            $baseMapping[$code] = '';
        }

        $monthDate = $monthStart->toDateString();
        $historyRows = DB::table('voucher_owner_history')
            ->whereIn('voucher_code', array_keys($defaultMapping))
            ->whereDate('effective_from', '<=', $monthDate)
            ->where(function ($query) use ($monthDate) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $monthDate);
            })
            ->select('voucher_code', 'owner_name')
            ->get();

        $override = [];
        foreach ($historyRows as $row) {
            $override[$row->voucher_code] = $row->owner_name;
        }

        return array_merge($baseMapping, $override);
    }

    private function isCanvasserInsentifVisible(Carbon $monthStart): bool
    {
        $monthKey = $monthStart->format('Y-m');
        return in_array($monthKey, ['2026-01', '2026-02'], true);
    }
}












