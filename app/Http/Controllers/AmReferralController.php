<?php

namespace App\Http\Controllers;

use App\Models\AmReferral;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AmReferralController extends Controller
{
    public function index()
    {
        logUserLogin();

        $months = collect(range(1, 12))->map(fn ($month) => [
            'value' => Carbon::create(now()->year, $month, 1)->format('Y-m-d'),
            'label' => Carbon::create(now()->year, $month, 1)->translatedFormat('F Y'),
            'selected' => $month === now()->month,
        ])->all();

        return view('admin.am_referral', compact('months'));
    }

    public function data(Request $request)
    {
        return datatables()->of(collect($this->reportRows($request)))->addIndexColumn()->make(true);
    }

    public function dealData(Request $request)
    {
        [$startDate, $endDate] = $this->period($request);
        $previousStart = $startDate->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $endDate->copy()->subMonthNoOverflow()->endOfDay();

        $rows = $this->amUsers()->map(function ($user) use ($startDate, $endDate, $previousStart, $previousEnd) {
            $leads = DB::table('leads_master')->where('user_id', $user->id)->get(['email', 'created_at']);
            $emails = $leads->pluck('email')->filter()->map(fn ($email) => strtolower(trim($email)))->unique()->values()->all();
            $newEmails = $leads->filter(fn ($lead) => Carbon::parse($lead->created_at)->between($startDate, $endDate))->pluck('email')->filter()->map(fn ($email) => strtolower(trim($email)))->unique()->values()->all();
            $existingEmails = array_values(array_diff($emails, $newEmails));

            $sum = fn (array $emailList, Carbon $from, Carbon $until) => empty($emailList) ? 0 : (float) DB::table('report_balance_top_up')
                ->whereIn(DB::raw('LOWER(TRIM(email_client))'), $emailList)->where('payment_method_name', '!=', 'Voucher Bonus')
                ->whereBetween('tgl_transaksi', [$from, $until])->sum('amount');
            $count = fn (array $emailList) => empty($emailList) ? 0 : DB::table('report_balance_top_up')
                ->whereIn(DB::raw('LOWER(TRIM(email_client))'), $emailList)->where('payment_method_name', '!=', 'Voucher Bonus')
                ->whereBetween('tgl_transaksi', [$startDate, $endDate])->distinct()->count('email_client');

            $newTopup = $sum($newEmails, $startDate, $endDate);
            $existingTopup = $sum($existingEmails, $startDate, $endDate);
            $transfer = empty($emails) ? 0 : (float) DB::table('saldo_transfer')->whereIn(DB::raw('LOWER(TRIM(email_client))'), $emails)->whereBetween('tgl_transaksi', [$startDate, $endDate])->sum('amount');
            $total = $newTopup + $existingTopup + $transfer;
            $previous = $sum($emails, $previousStart, $previousEnd);
            $target = 0;

            return [
                'team_am' => $user->name, 'target' => 'Rp ' . number_format($target, 0, ',', '.'),
                'deal_topup_new_akun' => $count($newEmails), 'deal_topup_existing_akun' => $count($existingEmails),
                'top_up_new_akun_rp' => number_format($newTopup, 0, ',', '.'), 'top_up_existing_akun_rp' => number_format($existingTopup, 0, ',', '.'),
                'total_transfer_saldo_rp' => number_format($transfer, 0, ',', '.'), 'total_topup' => 'Rp ' . number_format($total, 0, ',', '.'),
                'acv' => '0,00%', 'mom_prev_partial' => number_format($previous, 0, ',', '.'),
                'mom_current_partial' => number_format($total, 0, ',', '.'), 'mom_prev_remaining' => '0',
                'mom_gap' => number_format($total - $previous, 0, ',', '.'),
            ];
        });

        return datatables()->of($rows)->addIndexColumn()->make(true);
    }

    public function saldoData(Request $request)
    {
        [$startDate, $endDate] = $this->period($request);
        $userIds = $this->amUsers()->pluck('id')->all();
        $rows = DB::table('saldo_transfer as st')->join('leads_master as lm', DB::raw('LOWER(TRIM(st.email_client))'), '=', DB::raw('LOWER(TRIM(lm.email))'))
            ->join('users as u', 'u.id', '=', 'lm.user_id')->whereIn('u.id', $userIds)->whereBetween('st.tgl_transaksi', [$startDate, $endDate])
            ->select('u.name as team_am', 'lm.company_name', 'st.email_client', 'st.parent_email', 'st.amount', 'st.tgl_transaksi')->orderByDesc('st.tgl_transaksi')->get()
            ->map(fn ($row) => ['team_am' => $row->team_am, 'company_name' => $row->company_name, 'email_client' => $row->email_client,
                'parent_email' => $row->parent_email, 'amount' => 'Rp ' . number_format((float) $row->amount, 0, ',', '.'),
                'tgl_transaksi' => Carbon::parse($row->tgl_transaksi)->format('d M Y H:i')]);

        return datatables()->of($rows)->addIndexColumn()->make(true);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->reportRows($request);
        $filename = 'am_referral_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, ['Kode Referral', 'Nama AM', 'Visit', 'Leads', 'Akun', 'Lead ke Visit', 'Akun ke Lead', 'Total Topup', 'Poin', 'Transaksi Terakhir']);
            foreach ($rows as $row) {
                fputcsv($output, array_values($row));
            }
            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function reportRows(Request $request): array
    {
        [$startDate, $endDate] = $this->period($request);
        $users = $this->amUsers();

        return $users->map(function ($user) use ($startDate, $endDate) {
            $code = strtoupper(trim((string) $user->referral_code));
            $voucher = null;
            if ($code !== '') {
                $voucher = DB::table('report_balance_top_up as rb')
                    ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
                    ->whereRaw('UPPER(dv.voucher_code) = ?', [$code])
                    ->where('rb.payment_method_name', '!=', 'Voucher Bonus')
                    ->whereBetween('rb.paid_date', [$startDate, $endDate])
                    ->selectRaw('COUNT(DISTINCT LOWER(rb.email_client)) as jumlah_akun')
                    ->selectRaw('COALESCE(SUM(CAST(rb.amount AS DECIMAL(18,2))), 0) as total_topup')
                    ->selectRaw('MAX(rb.tgl_transaksi) as transaksi_terakhir')
                    ->first();
            }

            $leads = DB::table('leads_master')->where('user_id', $user->id)->whereBetween('created_at', [$startDate, $endDate])->count();
            $visits = DB::table('bookings')->where('nama', $user->name)->whereBetween('tanggal', [$startDate, $endDate])->count();
            $accounts = (int) ($voucher->jumlah_akun ?? 0);
            $topup = (float) ($voucher->total_topup ?? 0);

            return [
                'referral_code' => $code ?: '-',
                'team_am' => $user->name,
                'jumlah_visit' => $visits,
                'jumlah_leads' => $leads,
                'jumlah_akun' => $accounts,
                'percentage_lead_to_visit' => number_format($visits > 0 ? ($leads / $visits) * 100 : 0, 2, ',', '.') . '%',
                'percentage_new_akun_to_lead' => number_format($leads > 0 ? ($accounts / $leads) * 100 : 0, 2, ',', '.') . '%',
                'total_topup' => 'Rp ' . number_format($topup, 0, ',', '.'),
                'poin' => (int) floor($topup / 1000000),
                'tgl_transaksi_terakhir' => $voucher?->transaksi_terakhir ? Carbon::parse($voucher->transaksi_terakhir)->format('d M Y') : '-',
            ];
        })->all();
    }

    private function period(Request $request): array
    {
        $request->validate(['start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date']]);
        return [$request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth(),
            $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfDay()];
    }

    private function amUsers()
    {
        return AmReferral::query()->activeAm()->when(Auth::user()->role !== 'Admin', fn ($query) => $query->whereKey(Auth::id()))
            ->orderBy('name')->get(['id', 'name', 'referral_code']);
    }
}
