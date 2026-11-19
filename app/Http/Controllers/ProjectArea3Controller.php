<?php

namespace App\Http\Controllers;

use App\Models\ProjectArea3Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectDs;

class ProjectArea3Controller extends Controller
{
    public function ds()
    {
        return $this->tampil('ds', 'Project DS');
    }

    public function canvasser()
    {
        return $this->tampil('canvasser', 'Canvasser Project');
    }

private function tampil(string $tipe, string $judul)
{
    return view('project-area3.index', compact('tipe', 'judul'));
}
private function anggota(string $tipe)
{
    if ($tipe === 'ds') {
        return ProjectDs::where('status_akun', 'Aktif')
            ->orderBy('city')->orderBy('nama')->get()
            ->map(fn ($d) => (object) [
                'nama'          => $d->nama,
                'lokasi_kerja'  => $d->city,
                'referral_code' => $d->kode_referal,
                'email'         => $d->email,
                'user_id'       => $d->user_id,
            ]);
    }

    return ProjectArea3Member::where('tipe', $tipe)
        ->orderBy('lokasi_kerja')->orderBy('nama')->get()
        ->map(fn ($m) => (object) [
            'nama'          => $m->nama,
            'lokasi_kerja'  => $m->lokasi_kerja,
            'referral_code' => $m->referral_code,
            'email'         => null,
            'user_id'       => null,
        ]);
}

private function petaUser($members): array
{
    $codes = $members->pluck('referral_code')->filter()
        ->map(fn ($c) => strtoupper(trim($c)))->unique()->values()->all();
    $emails = $members->pluck('email')->filter()
        ->map(fn ($e) => strtolower(trim($e)))->unique()->values()->all();

    $byCode = empty($codes) ? collect() : DB::table('users')
        ->whereIn(DB::raw('UPPER(referral_code)'), $codes)
        ->select('id', DB::raw('UPPER(referral_code) as k'))
        ->get()->pluck('id', 'k');

    $byEmail = empty($emails) ? collect() : DB::table('users')
        ->whereIn(DB::raw('LOWER(email)'), $emails)
        ->select('id', DB::raw('LOWER(email) as k'))
        ->get()->pluck('id', 'k');

    $direct  = $members->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->all();
    $userIds = $byCode->values()->merge($byEmail->values())->merge($direct)
        ->map(fn ($id) => (int) $id)->unique()->values()->all();

    return [$codes, $byCode, $byEmail, $userIds];
}

private function userIdAnggota($m, $byCode, $byEmail)
{
    if (! empty($m->user_id)) {
    return (int) $m->user_id;
}
    $email = strtolower(trim((string) ($m->email ?? '')));
    $code  = strtoupper(trim((string) ($m->referral_code ?? '')));

    return ($email !== '' ? $byEmail->get($email) : null)
        ?? ($code !== '' ? $byCode->get($code) : null);
}


public function data(Request $request, string $tipe)
{
    return datatables()->of($this->laporan($request, $tipe))
        ->addIndexColumn()
        ->editColumn('percentage_new_akun_to_lead', fn ($r) => number_format($r['percentage_new_akun_to_lead'], 2, ',', '.') . '%')
        ->editColumn('total_topup', fn ($r) => 'Rp ' . number_format($r['total_topup'], 0, ',', '.'))
        ->make(true);
}

public function export(Request $request, string $tipe)
{
    $rows = $this->laporan($request, $tipe);
    $filename = 'report-' . $tipe . '-area3-' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($rows) {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['No', 'Area', 'Cluster', 'Referral Code', 'Nama', 'Jumlah Leads',
            'Jumlah Akun', 'New Akun to Leads (%)', 'Top Up', 'Tgl Transaksi Terakhir']);
        foreach ($rows as $i => $r) {
            fputcsv($out, [$i + 1, $r['area'], $r['branch'], $r['referral_code'], $r['nama'],
                $r['jumlah_leads'], $r['jumlah_akun'],
                number_format($r['percentage_new_akun_to_lead'], 2, ',', '.') . '%',
                $r['total_topup'], $r['tgl_transaksi_terakhir']]);
        }
        fclose($out);
    }, $filename, ['Content-Type' => 'text/csv']);
}

private function laporan(Request $request, string $tipe): array
{
    $ref   = Carbon::yesterday();
    $start = $request->get('start_date')
        ? Carbon::parse($request->start_date)->startOfDay()
        : $ref->copy()->startOfMonth()->startOfDay();
    $end = $request->get('end_date')
        ? Carbon::parse($request->end_date)->endOfDay()
        : $ref->copy()->endOfDay();

    $members = $this->anggota($tipe);
    [$codes, $userIdByCode, $userIdByEmail, $userIds] = $this->petaUser($members);

    $voucherData = collect();
    if (! empty($codes)) {
        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                DB::raw('COUNT(DISTINCT LOWER(rb.email_client)) as jumlah_akun'),
                DB::raw('SUM(CAST(rb.amount AS DECIMAL(15,2))) as total_topup'),
                DB::raw('MAX(COALESCE(rb.paid_date, rb.tgl_transaksi)) as tgl_transaksi_terakhir')
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $codes)
            ->where('rb.payment_method_name', '!=', 'Voucher Bonus')
            ->whereBetween(DB::raw('DATE(COALESCE(rb.paid_date, rb.tgl_transaksi))'),
                [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->groupBy(DB::raw('UPPER(dv.voucher_code)'))
            ->get()->keyBy('voucher_code');
    }

    $leadAgg = collect();
    if (! empty($userIds)) {
        $leadAgg = DB::table('leads_master')
            ->whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as jumlah_leads'))
            ->get()->keyBy('user_id');
    }

    $result = [];
    foreach ($members as $m) {
        $code   = strtoupper(trim((string) $m->referral_code));
        $info   = $code !== '' ? $voucherData->get($code) : null;
        $userId = $this->userIdAnggota($m, $userIdByCode, $userIdByEmail);

        $leads = (int) ($userId ? ($leadAgg[$userId]->jumlah_leads ?? 0) : 0);
        $akun  = (int) ($info->jumlah_akun ?? 0);

        $result[] = [
            'area'          => 'Area 3',
            'branch'        => $m->lokasi_kerja ?: '-',
            'referral_code' => $code !== '' ? $code : '-',
            'nama'          => $m->nama,
            'jumlah_leads'  => $leads,
            'jumlah_akun'   => $akun,
            'percentage_new_akun_to_lead' => $leads > 0 ? ($akun / $leads) * 100 : 0,
            'total_topup'   => (float) ($info->total_topup ?? 0),
            'tgl_transaksi_terakhir' => ! empty($info->tgl_transaksi_terakhir)
                ? Carbon::parse($info->tgl_transaksi_terakhir)->format('d M Y') : '-',
        ];
    }

    return $result;
}

// ===== Report Territory (Summary per Territory) =====

public function territory()
{
    return view('project-area3.territory');
}

public function territoryData(Request $request)
{
    return datatables()->of($this->territorySummary($request))
        ->addIndexColumn()
        ->addColumn('acv', function ($row) {
            $acv = $row['target'] > 0 ? ($row['total_topup'] / $row['target']) * 100 : 0;
            return number_format($acv, 2, ',', '.') . '%';
        })
        ->editColumn('target', fn ($r) => 'Rp ' . number_format($r['target'], 0, ',', '.'))
        ->editColumn('top_up_new_akun_rp', fn ($r) => number_format($r['top_up_new_akun_rp'], 0, ',', '.'))
        ->editColumn('top_up_existing_akun_rp', fn ($r) => number_format($r['top_up_existing_akun_rp'], 0, ',', '.'))
        ->editColumn('total_topup', fn ($r) => 'Rp ' . number_format($r['total_topup'], 0, ',', '.'))
        ->make(true);
}

public function territoryExport(Request $request)
{
    $rows = $this->territorySummary($request);
    $filename = 'summary-territory-area3-' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($rows) {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['No', 'Territory', 'Target (Rp)', 'Jumlah Leads', 'Jumlah Visit',
            'New Akun', 'Eksisting Akun', 'Total Akun', 'New Akun (Rp)',
            'Eksisting Akun (Rp)', 'Total (Rp)', 'ACV (%)']);
        foreach ($rows as $i => $r) {
            $acv = $r['target'] > 0 ? ($r['total_topup'] / $r['target']) * 100 : 0;
            fputcsv($out, [$i + 1, $r['territory'], $r['target'], $r['jumlah_leads'], $r['jumlah_visit'],
                $r['deal_topup_new_akun'], $r['deal_topup_existing_akun'], $r['deal_topup_total_akun'],
                $r['top_up_new_akun_rp'], $r['top_up_existing_akun_rp'], $r['total_topup'],
                number_format($acv, 2, ',', '.') . '%']);
        }
        fclose($out);
    }, $filename, ['Content-Type' => 'text/csv']);
}

private function territorySummary(Request $request): array
{
    $ref   = Carbon::yesterday();
    $start = $request->get('start_date')
        ? Carbon::parse($request->start_date)->startOfDay()
        : $ref->copy()->startOfMonth()->startOfDay();
    $end = $request->get('end_date')
        ? Carbon::parse($request->end_date)->endOfDay()
        : $ref->copy()->endOfDay();

    $grouped = [];
    foreach ($this->performanceRows($start, $end) as $row) {
        $t = $row['territory'];
        if (! isset($grouped[$t])) {
            $grouped[$t] = [
                'territory' => $t, 'target' => 0, 'jumlah_leads' => 0, 'jumlah_visit' => 0,
                'deal_topup_new_akun' => 0, 'deal_topup_existing_akun' => 0, 'deal_topup_total_akun' => 0,
                'top_up_new_akun_rp' => 0, 'top_up_existing_akun_rp' => 0, 'total_topup' => 0,
            ];
        }
        foreach (['target', 'jumlah_leads', 'jumlah_visit', 'deal_topup_new_akun',
                  'deal_topup_existing_akun', 'deal_topup_total_akun',
                  'top_up_new_akun_rp', 'top_up_existing_akun_rp', 'total_topup'] as $k) {
            $grouped[$t][$k] += $row[$k];
        }
    }

    $result = array_values($grouped);
    usort($result, fn ($a, $b) => strcasecmp($a['territory'], $b['territory']));

    return $result;
}

private function performanceRows(Carbon $startDate, Carbon $endDate): array
{
    $members = $this->anggota('ds')->concat($this->anggota('canvasser'))
    ->sortBy(fn ($m) => $m->lokasi_kerja . '|' . $m->nama)->values();
    if ($members->isEmpty()) {
        return [];
    }

    [$codes, $userIdByCode, $userIdByEmail, $userIds] = $this->petaUser($members);

    $startDay = $startDate->format('Y-m-d');
    $endDay   = $endDate->format('Y-m-d');
    $startDt  = $startDate->copy()->startOfDay()->format('Y-m-d H:i:s');
    $endDt    = $endDate->copy()->endOfDay()->format('Y-m-d H:i:s');
    $trxDate  = 'DATE(COALESCE(rp.paid_date, rp.tgl_transaksi))';

    $targetByUser = collect();
    $leadAgg      = collect();
    $topUpStats   = collect();
    $newStats     = [];

    if (! empty($userIds)) {
        $targetByUser = DB::table('mpcc_targets')
            ->whereIn('user_id', $userIds)
            ->where('year', (int) $startDate->year)
            ->where('month', (int) $startDate->month)
            ->select('user_id', 'target_amount')->get()->keyBy('user_id');

        $leadAgg = DB::table('leads_master')
            ->whereIn('user_id', $userIds)
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('user_id')
            ->select('user_id', DB::raw('COUNT(*) as jumlah_leads'))
            ->get()->keyBy('user_id');

        $topUpStats = DB::table('report_balance_top_up as rp')
            ->join('leads_master as lm', DB::raw('LOWER(rp.email_client)'), '=', DB::raw('LOWER(lm.email)'))
            ->select('lm.user_id',
                DB::raw('COUNT(rp.id) as top_up_count'),
                DB::raw('SUM(CAST(rp.amount AS DECIMAL(15,2))) as total_top_up_rp'))
            ->whereIn('lm.user_id', $userIds)
            ->where('rp.payment_method_name', '!=', 'Voucher Bonus')
            ->where(function ($q) use ($startDt, $endDt) {
                $q->whereBetween('rp.paid_date', [$startDt, $endDt])
                  ->orWhere(function ($f) use ($startDt, $endDt) {
                      $f->whereNull('rp.paid_date')->whereBetween('rp.tgl_transaksi', [$startDt, $endDt]);
                  });
            })
            ->whereBetween(DB::raw($trxDate), [$startDay, $endDay])
            ->groupBy('lm.user_id')
            ->get()->keyBy('user_id');

        $leadOwnersByEmail = DB::table('leads_master')
            ->whereIn('user_id', $userIds)->whereNotNull('email')
            ->select('user_id', 'email')->get()
            ->groupBy(fn ($row) => strtolower((string) $row->email));

        $approvalsByEmail = DB::table('data_registarsi_status_approveorreject as dt')
            ->where('dt.status', 'APPROVE')
            ->whereIn(DB::raw('LOWER(dt.email)'), $leadOwnersByEmail->keys()->all())
            ->whereBetween(DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')"), [$startDay, $endDay])
            ->select('dt.email', DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d') as approval_date"))
            ->get()->groupBy(fn ($row) => strtolower((string) $row->email));

        $approvalsByEmail->keys()->chunk(500)->each(function ($emailChunk) use (
            $startDt, $endDt, $startDay, $endDay, $trxDate, $approvalsByEmail, $leadOwnersByEmail, &$newStats
        ) {
            $transactions = DB::table('report_balance_top_up as rp')
                ->whereIn(DB::raw('LOWER(rp.email_client)'), $emailChunk->all())
                ->where('rp.payment_method_name', '!=', 'Voucher Bonus')
                ->where(function ($q) use ($startDt, $endDt) {
                    $q->whereBetween('rp.paid_date', [$startDt, $endDt])
                      ->orWhere(function ($f) use ($startDt, $endDt) {
                          $f->whereNull('rp.paid_date')->whereBetween('rp.tgl_transaksi', [$startDt, $endDt]);
                      });
                })
                ->whereBetween(DB::raw($trxDate), [$startDay, $endDay])
                ->select('rp.id', 'rp.email_client', 'rp.amount', 'rp.paid_date', 'rp.tgl_transaksi')
                ->get();

            foreach ($transactions as $trx) {
                $emailKey = strtolower((string) $trx->email_client);
                $trxDay = Carbon::parse($trx->paid_date ?: $trx->tgl_transaksi)->format('Y-m-d');

                foreach ($approvalsByEmail->get($emailKey, collect()) as $approval) {
                    if ($trxDay < $approval->approval_date) {
                        continue;
                    }
                    foreach ($leadOwnersByEmail->get($emailKey, collect()) as $owner) {
                        $uid = (int) $owner->user_id;
                        $newStats[$uid] = $newStats[$uid] ?? ['ids' => [], 'amount' => 0];
                        $newStats[$uid]['ids'][(string) $trx->id] = true;
                        $newStats[$uid]['amount'] += (float) $trx->amount;
                    }
                }
            }
        });
    }

    $visitAgg = DB::table('bookings')
        ->whereIn('nama', $members->pluck('nama')->all())
        ->whereBetween('tanggal', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
        ->groupBy('nama')
        ->select('nama', DB::raw('COUNT(*) as jumlah_visit'))
        ->get()->keyBy('nama');

    $result = [];
    foreach ($members as $m) {
        $uid = $this->userIdAnggota($m, $userIdByCode, $userIdByEmail);

        $topUpCount   = (int) ($uid ? ($topUpStats[$uid]->top_up_count ?? 0) : 0);
        $totalTopup   = (float) ($uid ? ($topUpStats[$uid]->total_top_up_rp ?? 0) : 0);
        $newAkunCount = (int) ($uid && isset($newStats[$uid]) ? count($newStats[$uid]['ids']) : 0);
        $newAkunRp    = (float) ($uid && isset($newStats[$uid]) ? $newStats[$uid]['amount'] : 0);

        $result[] = [
            'territory'                => $m->lokasi_kerja ?: '-',
            'target'                   => (float) ($uid ? ($targetByUser[$uid]->target_amount ?? 0) : 0),
            'jumlah_leads'             => (int) ($uid ? ($leadAgg[$uid]->jumlah_leads ?? 0) : 0),
            'jumlah_visit'             => (int) ($visitAgg[$m->nama]->jumlah_visit ?? 0),
            'deal_topup_new_akun'      => $newAkunCount,
            'deal_topup_existing_akun' => max($topUpCount - $newAkunCount, 0),
            'deal_topup_total_akun'    => $topUpCount,
            'top_up_new_akun_rp'       => $newAkunRp,
            'top_up_existing_akun_rp'  => max($totalTopup - $newAkunRp, 0),
            'total_topup'              => $totalTopup,
        ];
    }

    return $result;
}
}