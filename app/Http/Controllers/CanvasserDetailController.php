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
        ]);
        $user = DB::table('users')->where('id', $data['user_id'])->where('role', 'cvsr')
            ->select('id', 'name', 'email', 'regional')->first();
        abort_if(!$user, 404, 'User canvasser tidak ditemukan.');

        return [$user, Carbon::createFromFormat('Y-m-d', $data['month'].'-01'), $data['month']];
    }

    public function page(Request $request)
    {
        [$user, $period, $month] = $this->context($request);
        return view('report.topup-canvasser-detail', [
            'canvasser' => $user, 'month' => $month,
            'periodLabel' => $period->translatedFormat('F Y'),
        ]);
    }

    public function overview(Request $request)
    {
        [$user, , $month] = $this->context($request);
        $rows = collect(app(LeadProgramController::class)->getRegionalData(
            Request::create('/', 'GET', ['month' => $month])
        ));
        $row = $rows->first(fn ($item) => empty($item['is_total'])
            && ($item['canvaser_name'] ?? null) === $user->name);
        abort_if(!$row, 404, 'Data report canvasser tidak ditemukan.');
        $num = fn ($value) => is_int($value) || is_float($value)
            ? (float) $value
            : (float) str_replace(',', '.', str_replace('.', '', (string) $value));
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
        [$user, $period] = $this->context($request);
        $end = $period->isSameMonth(Carbon::today()) ? Carbon::today() : $period->copy()->endOfMonth();
        $currentStart = $period->copy()->startOfMonth();
        $previousStart = $period->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $period->copy()->subMonthNoOverflow()
            ->day(min($end->day, $period->copy()->subMonthNoOverflow()->daysInMonth));
        $totals = DB::table('report_balance_top_up as rp')->join('leads_master as lm', 'rp.email_client', '=', 'lm.email')
            ->where('lm.user_id', $user->id)->where('rp.payment_method_name', '!=', 'Voucher Bonus')
            ->selectRaw(
                'SUM(CASE WHEN rp.tgl_transaksi BETWEEN ? AND ? THEN CAST(rp.total_settlement_klien AS DECIMAL(15,2)) ELSE 0 END) previous_total,
                 SUM(CASE WHEN rp.tgl_transaksi BETWEEN ? AND ? THEN CAST(rp.total_settlement_klien AS DECIMAL(15,2)) ELSE 0 END) current_total',
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
        [$user,$period]=$this->context($request);
        $totals=DB::table('report_balance_top_up as rp')->join('leads_master as lm','rp.email_client','=','lm.email')
            ->where('lm.user_id',$user->id)->whereYear('rp.tgl_transaksi',$period->year)
            ->where('rp.payment_method_name','!=','Voucher Bonus')->groupByRaw('MONTH(rp.tgl_transaksi)')
            ->selectRaw('MONTH(rp.tgl_transaksi) month_number, SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))) total')
            ->pluck('total','month_number');
        $today=Carbon::today();
        $last=$period->year===$today->year?$today->month:($period->year<$today->year?12:0);
        return response()->json(['year'=>$period->year,'rows'=>collect($last?range(1,$last):[])->map(fn($m)=>[
            'label'=>Carbon::create($period->year,$m,1)->translatedFormat('M'),'total'=>(float)($totals[$m]??0)
        ])->values()]);
    }

    public function transactions(Request $request)
    {
        [$user,$period]=$this->context($request);
        $leads=DB::table('leads_master')->where('user_id',$user->id)->whereNotNull('email')
            ->groupByRaw('LOWER(TRIM(email))')->selectRaw('LOWER(TRIM(email)) normalized_email');
        $approval=DB::table('data_registarsi_status_approveorreject')->where('status','APPROVE')->whereNotNull('email')
            ->groupByRaw('LOWER(TRIM(email))')->selectRaw("LOWER(TRIM(email)) normalized_email, MIN(STR_TO_DATE(tanggal_approval_aktivasi,'%Y-%m-%d')) approval_date");
        $start=$period->copy()->startOfMonth(); $end=$period->copy()->endOfMonth();
        $rows=DB::table('report_balance_top_up as rp')
            ->joinSub($leads,'lm',fn($j)=>$j->on(DB::raw('LOWER(TRIM(rp.email_client))'),'=','lm.normalized_email'))
            ->leftJoinSub($approval,'dt',fn($j)=>$j->on(DB::raw('LOWER(TRIM(rp.email_client))'),'=','dt.normalized_email'))
            ->whereBetween('rp.tgl_transaksi',[$start->copy()->startOfDay(),$end->copy()->endOfDay()])
            ->where('rp.payment_method_name','!=','Voucher Bonus')
            ->select('rp.tgl_transaksi','rp.no_invoice','rp.company_name','rp.total_settlement_klien','rp.payment_method_name','rp.payment_history_status','rp.voucher_code',
                DB::raw("CONCAT(LEFT(TRIM(rp.email_client),5),REPEAT('*',GREATEST(CHAR_LENGTH(TRIM(rp.email_client))-5,0))) masked_email"),
                DB::raw("CASE WHEN dt.approval_date BETWEEN '{$start->format('Y-m-d')}' AND '{$end->format('Y-m-d')}' AND DATE(rp.tgl_transaksi)>=dt.approval_date THEN 'New Account' ELSE 'Existing Account' END account_status"))
            ->orderByDesc('rp.tgl_transaksi')->get();
        return response()->json(['data'=>$rows]);
    }
}
