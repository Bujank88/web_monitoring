<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RefreshRegionalCanvasserSummary extends Command
{
    protected $signature = 'summary:refresh-regional-canvasser {month?}';
    protected $description = 'Refresh regional canvasser summary table for specified month or current month';

    public function handle()
    {
        $monthInput = $this->argument('month');
        $monthDate = $monthInput 
            ? Carbon::createFromFormat('Y-m', $monthInput)
            : Carbon::now();
        
        $this->info("Refreshing regional canvasser summary for: " . $monthDate->format('Y-m'));
        
        try {
            $this->refreshSummary($monthDate);
            $this->info("✓ Summary refreshed successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
            Log::error("Error refreshing regional canvasser summary: " . $e->getMessage());
            return 1;
        }
    }
    
    public function refreshSummary($monthDate)
    {
        // Get active canvassers
        $canvasers = DB::table('users')
            ->where('role', 'cvsr')
            ->where('name', '!=', 'self service')
            ->select('id', 'name')
            ->get();
            
        if ($canvasers->isEmpty()) {
            $this->warn("No canvassers found.");
            return;
        }
        
        $canvaserIds = $canvasers->pluck('id')->all();
        $logbookPeriod = $monthDate->copy();
        $logbookMonth = (int) $logbookPeriod->month;
        $logbookYear = (int) $logbookPeriod->year;
        
        $today = $monthDate->copy()->endOfMonth();
        $todayDate = $today->format('Y-m-d');
        $startOfMonth = $monthDate->copy()->startOfMonth()->format('Y-m-d');
        $endOfMonth = $monthDate->copy()->endOfMonth();
        $endOfMonthFormatted = $endOfMonth->format('Y-m-d');
        $currentMonth = $monthDate->format('Y-m');
        
        // Calculate remaining working days
        $holidays = [
            '2026-01-01', '2026-02-17', '2026-03-22', '2026-03-23', '2026-03-24',
            '2026-04-10', '2026-05-01', '2026-05-02', '2026-05-21', '2026-05-30',
            '2026-06-01', '2026-06-20', '2026-08-17', '2026-08-29', '2026-12-25',
        ];
        
        $remainingWorkingDays = 0;
        $currentDate = ($today->month == Carbon::today()->month && $today->year == Carbon::today()->year)
            ? Carbon::today()
            : $today->copy();
            
        while ($currentDate->lte($endOfMonth)) {
            if ($currentDate->isWeekday() && !in_array($currentDate->format('Y-m-d'), $holidays)) {
                $remainingWorkingDays++;
            }
            $currentDate->addDay();
        }
        
        $this->info("Processing {$canvasers->count()} canvassers...");
        $progressBar = $this->output->createProgressBar($canvasers->count());
        
        // Get all data in batch queries
        $regionalMap = DB::table('leads_master as lm')
            ->leftJoin('regional_tracers as rt', 'lm.email', '=', 'rt.pic_email')
            ->whereIn('lm.user_id', $canvaserIds)
            ->whereNotNull('rt.regional')
            ->groupBy('lm.user_id')
            ->select('lm.user_id', DB::raw('MIN(rt.regional) as regional'))
            ->pluck('regional', 'lm.user_id');
            
        $leadStats = DB::table('logbook as lb')
            ->join('leads_master as lm', 'lb.leads_master_id', '=', 'lm.id')
            ->whereIn('lm.user_id', $canvaserIds)
            ->where('lb.bulan', $logbookMonth)
            ->where('lb.tahun', $logbookYear)
            ->groupBy('lm.user_id')
            ->select(
                'lm.user_id',
                DB::raw("COUNT(DISTINCT CASE WHEN lm.data_type = 'leads' THEN lb.leads_master_id END) as leads"),
                DB::raw("COUNT(DISTINCT CASE WHEN lm.data_type = 'Eksisting Akun' THEN lb.leads_master_id END) as existing_akun")
            )
            ->get()
            ->keyBy('user_id');
            
        $newAkunStats = DB::table('data_registarsi_status_approveorreject as dt')
            ->join('leads_master as lm', 'dt.email', '=', 'lm.email')
            ->whereIn('lm.user_id', $canvaserIds)
            ->whereBetween(
                DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')"),
                [$startOfMonth, $endOfMonthFormatted]
            )
            ->groupBy('lm.user_id')
            ->select('lm.user_id', DB::raw('COUNT(DISTINCT dt.email) as new_akun'))
            ->get()
            ->keyBy('user_id');
            
        $topUpStatsByUser = DB::table('report_balance_top_up as rp')
            ->join('leads_master as lm', DB::raw('LOWER(rp.email_client)'), '=', DB::raw('LOWER(lm.email)'))
            ->whereIn('lm.user_id', $canvaserIds)
            ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
            ->groupBy('lm.user_id')
            ->select(
                'lm.user_id',
                DB::raw("COUNT(rp.id) as top_up_count"),
                DB::raw("SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))) as total_top_up_rp")
            )
            ->get()
            ->keyBy('user_id');
            
        $topUpNewAkunByUser = DB::table('data_registarsi_status_approveorreject as dt')
            ->join('report_balance_top_up as rp', function ($join) {
                $join->on(DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(rp.email_client)'))
                    ->whereRaw("DATE(rp.tgl_transaksi) >= STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')");
            })
            ->join('leads_master as lm', DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(lm.email)'))
            ->whereIn('lm.user_id', $canvaserIds)
            ->where('dt.status', 'APPROVE')
            ->whereBetween(
                DB::raw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')"),
                [$startOfMonth, $endOfMonthFormatted]
            )
            ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
            ->groupBy('lm.user_id')
            ->select(
                'lm.user_id',
                DB::raw("COUNT(DISTINCT rp.id) as top_up_count"),
                DB::raw("SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))) as top_up_new_akun_rp")
            )
            ->get()
            ->keyBy('user_id');
            
        $topUpExistingAkunByUser = DB::table('data_registarsi_status_approveorreject as dt')
            ->join('leads_master as lm', DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(lm.email)'))
            ->join('report_balance_top_up as rp', function ($join) {
                $join->on(DB::raw('LOWER(dt.email)'), '=', DB::raw('LOWER(rp.email_client)'))
                    ->whereRaw("DATE(rp.tgl_transaksi) >= STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d')");
            })
            ->whereIn('lm.user_id', $canvaserIds)
            ->where('dt.status', 'APPROVE')
            ->whereRaw("STR_TO_DATE(dt.tanggal_approval_aktivasi, '%Y-%m-%d') < ?", [$startOfMonth])
            ->whereBetween(DB::raw("DATE(rp.tgl_transaksi)"), [$startOfMonth, $todayDate])
            ->groupBy('lm.user_id')
            ->select(
                'lm.user_id',
                DB::raw("COUNT(rp.id) as top_up_existing_akun_count"),
                DB::raw("SUM(CAST(rp.total_settlement_klien AS DECIMAL(15,2))) as top_up_existing_akun_rp")
            )
            ->get()
            ->keyBy('user_id');
            
        $targetByUser = DB::table('target_canvaser')
            ->whereIn('user_id', $canvaserIds)
            ->where('bulan', $currentMonth)
            ->select('user_id', 'target')
            ->get()
            ->keyBy('user_id');
            
        // MoM calculation
        $momToday = $monthDate;
        $currentMonthStart = $momToday->copy()->startOfMonth()->format('Y-m-d');
        $currentMonthUntilToday = $momToday->copy()->format('Y-m-d');
        $prevMonthStart = $momToday->copy()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
        $prevMonthSameDay = $momToday->copy()->subMonthNoOverflow()->format('Y-m-d');
        $prevMonthEnd = $momToday->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d');
        $prevMonthRemainingStart = $momToday->copy()->subMonthNoOverflow()->addDay()->format('Y-m-d');
        
        $momByUser = DB::table('report_balance_top_up as rp')
            ->join('leads_master as lm', 'rp.email_client', '=', 'lm.email')
            ->whereIn('lm.user_id', $canvaserIds)
            ->groupBy('lm.user_id')
            ->select(
                'lm.user_id',
                DB::raw("SUM(CASE WHEN DATE(rp.tgl_transaksi) BETWEEN '{$prevMonthStart}' AND '{$prevMonthSameDay}' THEN CAST(rp.total_settlement_klien AS DECIMAL(15,2)) ELSE 0 END) as mom_prev_partial"),
                DB::raw("SUM(CASE WHEN DATE(rp.tgl_transaksi) BETWEEN '{$currentMonthStart}' AND '{$currentMonthUntilToday}' THEN CAST(rp.total_settlement_klien AS DECIMAL(15,2)) ELSE 0 END) as mom_current_partial"),
                DB::raw("SUM(CASE WHEN DATE(rp.tgl_transaksi) BETWEEN '{$prevMonthRemainingStart}' AND '{$prevMonthEnd}' THEN CAST(rp.total_settlement_klien AS DECIMAL(15,2)) ELSE 0 END) as mom_prev_remaining")
            )
            ->get()
            ->keyBy('user_id');
        
        // Process each canvasser and insert/update summary
        foreach ($canvasers as $canvaser) {
            $canvaserId = $canvaser->id;
            $regional = $regionalMap[$canvaserId] ?? '-';
            $totalLeads = (int) ($leadStats[$canvaserId]->leads ?? 0);
            $existingAkun = (int) ($leadStats[$canvaserId]->existing_akun ?? 0);
            $newAkun = (int) ($newAkunStats[$canvaserId]->new_akun ?? 0);
            
            $topUpNewAkunStats = $topUpNewAkunByUser[$canvaserId] ?? null;
            $topUpExistingAkunStats = $topUpExistingAkunByUser[$canvaserId] ?? null;
            $target = (float) ($targetByUser[$canvaserId]->target ?? 0);
            
            $topUpNewAkunRp = $topUpNewAkunStats->top_up_new_akun_rp ?? 0;
            $topUpExistingAkunRp = $topUpExistingAkunStats->top_up_existing_akun_rp ?? 0;
            $topUpStats = $topUpStatsByUser[$canvaserId] ?? null;
            $totalTopUpFromStats = $topUpStats->total_top_up_rp ?? 0;
            
            $splitTotal = $topUpNewAkunRp + $topUpExistingAkunRp;
            $totalTopUp = $splitTotal > 0 ? $splitTotal : $totalTopUpFromStats;
            
            if ($totalTopUpFromStats > 0 && $splitTotal < $totalTopUpFromStats) {
                $difference = $totalTopUpFromStats - $splitTotal;
                $topUpExistingAkunRp += $difference;
                $totalTopUp = $totalTopUpFromStats;
            }
            
            $achievementPercent = $target > 0 ? ($totalTopUp / $target) * 100 : 0;
            $gap = $totalTopUp - $target;
            $gapDaily = $remainingWorkingDays > 0 ? ($gap / $remainingWorkingDays) * -1 : 0;
            
            $topUpPrevMonthPartial = (float) ($momByUser[$canvaserId]->mom_prev_partial ?? 0);
            $topUpCurrentMonthPartial = (float) ($momByUser[$canvaserId]->mom_current_partial ?? 0);
            $topUpPrevMonthRemaining = (float) ($momByUser[$canvaserId]->mom_prev_remaining ?? 0);
            $momGap = $topUpCurrentMonthPartial - $topUpPrevMonthPartial;
            
            // Upsert to summary table
            DB::table('regional_canvasser_summary')->updateOrInsert(
                [
                    'user_id' => $canvaserId,
                    'bulan' => $currentMonth
                ],
                [
                    'canvasser_name' => $canvaser->name,
                    'regional' => $regional,
                    'leads' => $totalLeads,
                    'existing_akun' => $existingAkun,
                    'new_akun' => $newAkun,
                    'top_up_new_akun_count' => $topUpNewAkunStats->top_up_count ?? 0,
                    'top_up_existing_akun_count' => $topUpExistingAkunStats->top_up_existing_akun_count ?? 0,
                    'top_up_new_akun_rp' => $topUpNewAkunRp,
                    'top_up_existing_akun_rp' => $topUpExistingAkunRp,
                    'total_top_up_rp' => $totalTopUp,
                    'target' => $target,
                    'achievement_percent' => $achievementPercent,
                    'gap' => $gap,
                    'gap_daily' => $gapDaily,
                    'remaining_days' => $remainingWorkingDays,
                    'mom_prev_partial' => $topUpPrevMonthPartial,
                    'mom_current_partial' => $topUpCurrentMonthPartial,
                    'mom_prev_remaining' => $topUpPrevMonthRemaining,
                    'mom_gap' => $momGap,
                    'updated_at' => now()
                ]
            );
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }
}
