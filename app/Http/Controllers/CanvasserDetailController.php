<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CanvasserDetailController extends Controller
{
    private function context(Request $request): array
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'month' => ['required', 'date_format:Y-m'],
            'source' => ['nullable', 'in:canvasser,mpcc'],
        ]);
        $source = $data['source'] ?? 'canvasser';
        $expectedRole = $source === 'mpcc' ? 'MPCC' : 'cvsr';
        $user = DB::table('users')->where('id', $data['user_id'])->where('role', $expectedRole)
            ->select('id', 'name', 'email', 'regional')->first();
        abort_if(!$user, 404, 'User canvasser tidak ditemukan.');

        return [$user, Carbon::createFromFormat('Y-m-d', $data['month'].'-01'), $data['month'], $source];
    }

    public function page(Request $request)
    {
        [$user, $period, $month, $source] = $this->context($request);
        return view('report.topup-canvasser-detail', [
            'canvasser' => $user, 'month' => $month,
            'periodLabel' => $period->translatedFormat('F Y'), 'source' => $source,
            'canDownloadTopLeads' => $this->canDownloadTopLeads($request, $user->id),
        ]);
    }

    public function overview(Request $request)
    {
        [$user, $period, $month, $source] = $this->context($request);
        $num = static function ($value): float {
            if (is_int($value) || is_float($value)) return (float) $value;
            $clean = preg_replace('/[^\d,.\-]/', '', (string) $value);
            return (float) str_replace(',', '.', str_replace('.', '', $clean));
        };

        if ($source === 'mpcc') {
            $response = app(BackController::class)->getMpccDealTopupMom(
                Request::create('/', 'GET', [
                    'start_date' => $period->copy()->startOfMonth()->format('Y-m-d'),
                    'end_date' => $period->copy()->endOfMonth()->format('Y-m-d'),
                ])
            )->getData(true);
            $row = collect($response['data'] ?? [])->first(
                fn ($item) => (int) ($item['user_id'] ?? 0) === (int) $user->id
            );
            abort_if(!$row, 404, 'Data report MPCC tidak ditemukan.');
            $total = $num($row['total_topup'] ?? 0);
            $target = $num($row['target'] ?? 0);
            return response()->json([
                'prospect' => [(int)($row['jumlah_leads'] ?? 0), 0],
                'deal' => [(int)($row['deal_topup_new_akun'] ?? 0), (int)($row['deal_topup_existing_akun'] ?? 0)],
                'revenue' => [$num($row['top_up_new_akun_rp'] ?? 0), $num($row['top_up_existing_akun_rp'] ?? 0)],
                'target' => [
                    'target'=>$target,'total'=>$total,'achieved'=>min($total,$target),
                    'remaining'=>max($target-$total,0),'achievement'=>$target>0?($total/$target)*100:0,
                    'over_target'=>max($total-$target,0),
                ],
            ]);
        }

        $rows = collect(app(LeadProgramController::class)->getRegionalData(
            Request::create('/', 'GET', ['month' => $month])
        ));
        $row = $rows->first(fn ($item) => empty($item['is_total'])
            && ($item['canvaser_name'] ?? null) === $user->name);
        abort_if(!$row, 404, 'Data report canvasser tidak ditemukan.');
        $total = $num($row['total_top_up_rp'] ?? 0);
        $target = $num($row['target'] ?? 0);

        return response()->json([
            'prospect' => [(int)($row['leads'] ?? 0), (int)($row['existing_akun'] ?? 0)],
            'deal' => [(int)($row['top_up_new_akun_count'] ?? 0), (int)($row['top_up_existing_akun_count'] ?? 0)],
            'revenue' => [$num($row['top_up_new_akun_rp'] ?? 0), $num($row['top_up_existing_akun_rp'] ?? 0)],
            'target' => [
                'target' => $target, 'total' => $total,
                'achieved' => min($total, $target), 'remaining' => max($target-$total, 0),
                'achievement' => $target > 0 ? ($total/$target)*100 : 0,
                'over_target' => max($total-$target, 0),
            ],
        ]);
    }

    public function mom(Request $request)
    {
        [$user, $period, , $source] = $this->context($request);
        $dateExpr = $source === 'mpcc' ? 'COALESCE(rp.paid_date, rp.tgl_transaksi)' : 'rp.tgl_transaksi';
        $amountExpr = $source === 'mpcc' ? 'rp.amount' : 'rp.total_settlement_klien';
        $end = $period->isSameMonth(Carbon::today()) ? Carbon::today() : $period->copy()->endOfMonth();
        $currentStart = $period->copy()->startOfMonth();
        $previousStart = $period->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $period->copy()->subMonthNoOverflow()
            ->day(min($end->day, $period->copy()->subMonthNoOverflow()->daysInMonth));
        $totals = DB::table('report_balance_top_up as rp')->join('leads_master as lm', 'rp.email_client', '=', 'lm.email')
            ->where('lm.user_id', $user->id)->where('rp.payment_method_name', '!=', 'Voucher Bonus')
            ->selectRaw(
                "SUM(CASE WHEN {$dateExpr} BETWEEN ? AND ? THEN CAST({$amountExpr} AS DECIMAL(15,2)) ELSE 0 END) previous_total,
                 SUM(CASE WHEN {$dateExpr} BETWEEN ? AND ? THEN CAST({$amountExpr} AS DECIMAL(15,2)) ELSE 0 END) current_total",
                [$previousStart->copy()->startOfDay(),$previousEnd->copy()->endOfDay(),
                 $currentStart->copy()->startOfDay(),$end->copy()->endOfDay()]
            )->first();
        $prev=(float)($totals->previous_total??0); $current=(float)($totals->current_total??0);
        return response()->json([
            'labels'=>[
                $previousStart->translatedFormat('d M').' - '.$previousEnd->translatedFormat('d M Y'),
                $currentStart->translatedFormat('d M').' - '.$end->translatedFormat('d M Y'),
                'Sisa untuk menyamai '.$previousEnd->translatedFormat('d M Y'),
            ],
            'values'=>[$prev,$current,max($prev-$current,0)],
        ]);
    }

    public function trend(Request $request)
    {
        [$user,$period,,$source]=$this->context($request);
        $dateExpr=$source==='mpcc'?'COALESCE(rp.paid_date, rp.tgl_transaksi)':'rp.tgl_transaksi';
        $amountExpr=$source==='mpcc'?'rp.amount':'rp.total_settlement_klien';
        $trendRows=DB::table('report_balance_top_up as rp')->join('leads_master as lm','rp.email_client','=','lm.email')
            ->where('lm.user_id',$user->id)->whereYear(DB::raw($dateExpr),$period->year)
            ->where('rp.payment_method_name','!=','Voucher Bonus')->groupByRaw("MONTH({$dateExpr})")
            ->selectRaw("
                MONTH({$dateExpr}) month_number,
                SUM(CAST({$amountExpr} AS DECIMAL(15,2))) total,
                COUNT(DISTINCT LOWER(TRIM(rp.email_client))) user_count
            ")
            ->get()
            ->keyBy('month_number');
        $today=Carbon::today();
        $last=$period->year===$today->year?$today->month:($period->year<$today->year?12:0);
        return response()->json(['year'=>$period->year,'rows'=>collect($last?range(1,$last):[])->map(fn($m)=>[
            'label'=>Carbon::create($period->year,$m,1)->translatedFormat('M'),
            'total'=>(float)($trendRows[$m]->total??0),
            'user_count'=>(int)($trendRows[$m]->user_count??0),
        ])->values()]);
    }

    public function topLeads(Request $request)
    {
        [$user, $period, , $source] = $this->context($request);

        return response()->json($this->topLeadsData($user->id, $period, $source));
    }

    private function canDownloadTopLeads(Request $request, int $userId): bool
    {
        return $request->user()->role === 'Admin'
            || (string) $request->user()->getAuthIdentifier() === (string) $userId;
    }

    public function downloadTopLeads(Request $request)
    {
        [$user, $period, $month, $source] = $this->context($request);
        abort_unless($this->canDownloadTopLeads($request, $user->id), 403,
            'CSV hanya dapat diunduh oleh admin atau pemilik akun.');

        $report = $this->topLeadsData($user->id, $period, $source, includeEmail: true);

        return response()->streamDownload(function () use ($report) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Peringkat', 'Pelanggan', 'Email',
                'Top Up '.$report['current_label'], 'Top Up '.$report['previous_label'], 'Selisih', 'MoM (%)']);
            foreach ($report['data'] as $row) {
                // Prevent spreadsheet formulas in customer-controlled text cells.
                $text = static fn ($value) => preg_match('/^[\s]*[=+@-]|^[\t\r\n]/u', $value) ? "'".$value : $value;
                fputcsv($output, [$row['rank'], $text($row['company_name']), $text($row['email']),
                    $row['current_total'], $row['previous_total'], $row['difference'],
                    $row['mom_percent'] === null ? 'Baru' : $row['mom_percent']]);
            }
            fclose($output);
        }, "top-10-leads-{$user->id}-{$month}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function topLeadsData(int $userId, Carbon $period, string $source, bool $includeEmail = false): array
    {
        $dateExpr = $source === 'mpcc' ? 'COALESCE(rp.paid_date, rp.tgl_transaksi)' : 'rp.tgl_transaksi';
        $amountExpr = $source === 'mpcc' ? 'rp.amount' : 'rp.total_settlement_klien';
        $currentStart = $period->copy()->startOfMonth();
        $previousStart = $currentStart->copy()->subMonthNoOverflow();
        $isCurrentMonth = $period->isSameMonth(Carbon::today());
        $currentEnd = $isCurrentMonth ? Carbon::today()->endOfDay() : $period->copy()->endOfMonth();
        $previousEnd = $isCurrentMonth
            ? $previousStart->copy()->day(min($currentEnd->day, $previousStart->daysInMonth))->endOfDay()
            : $previousStart->copy()->endOfMonth();

        // One account per normalized email, so duplicate leads never multiply top ups.
        $leads = DB::table('leads_master')->where('user_id', $userId)
            ->whereNotNull('email')->whereRaw("TRIM(email) <> ''")
            ->groupByRaw('LOWER(TRIM(email))')
            ->selectRaw('LOWER(TRIM(email)) normalized_email, MAX(company_name) company_name');
        $totals = DB::table('report_balance_top_up as rp')
            ->joinSub($leads, 'lm', fn ($join) => $join->on(DB::raw('LOWER(TRIM(rp.email_client))'), '=', 'lm.normalized_email'))
            ->where('rp.payment_method_name', '!=', 'Voucher Bonus')
            ->where(function ($query) use ($dateExpr, $currentStart, $currentEnd, $previousStart, $previousEnd) {
                $query->whereBetween(DB::raw($dateExpr), [$currentStart, $currentEnd])
                    ->orWhereBetween(DB::raw($dateExpr), [$previousStart, $previousEnd]);
            })
            ->groupBy('lm.normalized_email', 'lm.company_name')
            ->select('lm.normalized_email', 'lm.company_name')
            ->selectRaw("SUM(CASE WHEN {$dateExpr} BETWEEN ? AND ? THEN CAST({$amountExpr} AS DECIMAL(15,2)) ELSE 0 END) current_total,
                SUM(CASE WHEN {$dateExpr} BETWEEN ? AND ? THEN CAST({$amountExpr} AS DECIMAL(15,2)) ELSE 0 END) previous_total",
                [$currentStart, $currentEnd, $previousStart, $previousEnd]);
        $rows = DB::query()->fromSub($totals, 'totals')->where('current_total', '>', 0)
            ->orderByDesc('current_total')->orderBy('normalized_email')->limit(10)->get();

        return [
            'current_label' => $currentStart->translatedFormat('d M').' - '.$currentEnd->translatedFormat('d M Y'),
            'previous_label' => $previousStart->translatedFormat('d M').' - '.$previousEnd->translatedFormat('d M Y'),
            'data' => $rows->map(function ($row, $index) use ($includeEmail) {
                $current = (float) $row->current_total;
                $previous = (float) $row->previous_total;
                $email = $row->normalized_email;
                $visible = min(5, max(1, strpos($email, '@') ?: 1));

                return [
                    'rank' => $index + 1,
                    'company_name' => $row->company_name ?: '-',
                    'masked_email' => substr($email, 0, $visible).str_repeat('*', max(3, strlen($email) - $visible)),
                    ...($includeEmail ? ['email' => $email] : []),
                    'current_total' => $current,
                    'previous_total' => $previous,
                    'difference' => $current - $previous,
                    'mom_percent' => $previous > 0 ? round(($current - $previous) / $previous * 100, 2) : null,
                ];
            })->all(),
        ];
    }

    public function transactions(Request $request)
    {
        [$user,$period,,$source]=$this->context($request);
        $dateExpr=$source==='mpcc'?'COALESCE(rp.paid_date, rp.tgl_transaksi)':'rp.tgl_transaksi';
        $amountColumn=$source==='mpcc'?'rp.amount':'rp.total_settlement_klien';
        $leads=DB::table('leads_master')->where('user_id',$user->id)->whereNotNull('email')
            ->groupByRaw('LOWER(TRIM(email))')->selectRaw('LOWER(TRIM(email)) normalized_email');
        $approval=DB::table('data_registarsi_status_approveorreject')->where('status','APPROVE')->whereNotNull('email')
            ->groupByRaw('LOWER(TRIM(email))')->selectRaw("LOWER(TRIM(email)) normalized_email, MIN(STR_TO_DATE(tanggal_approval_aktivasi,'%Y-%m-%d')) approval_date");
        $start=$period->copy()->startOfMonth(); $end=$period->copy()->endOfMonth();
        $rows=DB::table('report_balance_top_up as rp')
            ->joinSub($leads,'lm',fn($j)=>$j->on(DB::raw('LOWER(TRIM(rp.email_client))'),'=','lm.normalized_email'))
            ->leftJoinSub($approval,'dt',fn($j)=>$j->on(DB::raw('LOWER(TRIM(rp.email_client))'),'=','dt.normalized_email'))
            ->whereBetween(DB::raw($dateExpr),[$start->copy()->startOfDay(),$end->copy()->endOfDay()])
            ->where('rp.payment_method_name','!=','Voucher Bonus')
            ->selectRaw("{$dateExpr} as tgl_transaksi")
            ->addSelect(['rp.no_invoice','rp.company_name','rp.payment_method_name','rp.voucher_code'])
            ->selectRaw("CAST({$amountColumn} AS DECIMAL(15,2)) as total_settlement_klien")
            ->addSelect([
                DB::raw("CONCAT(LEFT(TRIM(rp.email_client),5),REPEAT('*',GREATEST(CHAR_LENGTH(TRIM(rp.email_client))-5,0))) masked_email"),
                DB::raw("CASE WHEN dt.approval_date BETWEEN '{$start->format('Y-m-d')}' AND '{$end->format('Y-m-d')}' AND DATE({$dateExpr})>=dt.approval_date THEN 'New Account' ELSE 'Existing Account' END account_status")
            ])
            ->orderByDesc('rp.tgl_transaksi')->get();
        return response()->json(['data'=>$rows]);
    }
}
