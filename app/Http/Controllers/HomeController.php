<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CreatorPartner;
use App\Models\EventSponsorhip;
use App\Models\ReferralChampionAm;
use App\Models\SultamRacing;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        logUserLogin();
        
       $months = [];

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->format('Y-m-01'); // bulan sekarang, tanggal 01

        for ($i = 1; $i <= 12; ++$i) {
            $date = Carbon::create($currentYear, $i, 1);
            $months[] = [
                'value' => $date->format('Y-m-d'), // e.g., 2025-05-01
                'label' => $date->translatedFormat('F Y'), // e.g., Mei 2025
                'selected' => $date->format('Y-m-d') === $currentMonth,
            ];
        }
        
        return view('admin.home', compact('months'));
    }
    
    private function getPadiUmkmSummary()
    {
        try {
            $totalForm = DB::table('summary_padi_umkm')->count();
            $totalTopup = DB::table('summary_padi_umkm')->sum('total_topup');
            $jumlahTopup = DB::table('summary_padi_umkm')->sum('jumlah_topup');
            
            return [
                'total_form' => $totalForm,
                'total_topup' => $totalTopup,
                'jumlah_topup' => $jumlahTopup
            ];
        } catch (\Exception $e) {
            return ['total_form' => 0, 'total_topup' => 0, 'jumlah_topup' => 0];
        }
    }
    
    private function getEventSponsorshipSummary()
    {
        try {
            $total = EventSponsorhip::count();
            $thisMonth = EventSponsorhip::whereMonth('created_at', Carbon::now()->month)
                                      ->whereYear('created_at', Carbon::now()->year)
                                      ->count();
            
            return [
                'total' => $total,
                'this_month' => $thisMonth
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'this_month' => 0];
        }
    }
    
    private function getCreatorPartnerSummary()
    {
        try {
            $total = CreatorPartner::count();
            $buzzer = CreatorPartner::where('jenis_kol', 'KOL as a Buzzer')->count();
            $influencer = CreatorPartner::where('jenis_kol', 'KOL as an Influencer')->count();
            
            return [
                'total' => $total,
                'buzzer' => $buzzer,
                'influencer' => $influencer
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'buzzer' => 0, 'influencer' => 0];
        }
    }
    
    private function getRekruterKolSummary()
    {
        try {
            $total = DB::table('rekruter_kol')->count();
            
            // Buzzer referral codes
            $buzzerCodes = DB::table('creator_partner')
                            ->where('jenis_kol', 'KOL as a Buzzer')
                            ->pluck('referral_code');
            
            $totalBuzzer = DB::table('rekruter_kol')
                            ->whereIn('referral_code', $buzzerCodes)
                            ->count();
            
            // Influencer referral codes
            $influencerCodes = DB::table('creator_partner')
                               ->where('jenis_kol', 'KOL as an Influencer')
                               ->pluck('referral_code');
            
            $totalInfluencer = DB::table('rekruter_kol')
                              ->whereIn('referral_code', $influencerCodes)
                              ->count();
            
            return [
                'total' => $total,
                'buzzer' => $totalBuzzer,
                'influencer' => $totalInfluencer
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'buzzer' => 0, 'influencer' => 0];
        }
    }
    
    private function getAreaMarcomSummary()
    {
        try {
            $totalAreas = DB::table('creator_partner')
                           ->distinct('area')
                           ->count();
            
            $totalKolPerArea = DB::table('creator_partner')
                               ->select('area', DB::raw('COUNT(DISTINCT id) as total_kol'))
                               ->groupBy('area')
                               ->get()
                               ->avg('total_kol');
            
            return [
                'total_areas' => $totalAreas,
                'avg_kol_per_area' => round($totalKolPerArea, 0)
            ];
        } catch (\Exception $e) {
            return ['total_areas' => 0, 'avg_kol_per_area' => 0];
        }
    }
    
    private function getSimpatiTiktokSummary()
    {
        try {
            $totalForm = DB::table('summary_simpati_tiktok')->count();
            $totalTopup = DB::table('summary_simpati_tiktok')->sum('total_topup');
            $jumlahTopup = DB::table('summary_simpati_tiktok')->sum('jumlah_topup');
            
            return [
                'total_form' => $totalForm,
                'total_topup' => $totalTopup,
                'jumlah_topup' => $jumlahTopup
            ];
        } catch (\Exception $e) {
            return ['total_form' => 0, 'total_topup' => 0, 'jumlah_topup' => 0];
        }
    }
    
    private function getReferralChampionSummary()
    {
        try {
            $total = ReferralChampionAm::count();
            $thisMonth = ReferralChampionAm::whereMonth('created_at', Carbon::now()->month)
                                          ->whereYear('created_at', Carbon::now()->year)
                                          ->count();
            
            return [
                'total' => $total,
                'this_month' => $thisMonth
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'this_month' => 0];
        }
    }
    
    private function getSultamRacingSummary()
    {
        try {
            $total = SultamRacing::count();
            $thisMonth = SultamRacing::whereMonth('created_at', Carbon::now()->month)
                                    ->whereYear('created_at', Carbon::now()->year)
                                    ->count();
            
            return [
                'total' => $total,
                'this_month' => $thisMonth
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'this_month' => 0];
        }
    }
    
    private function getVoucherSummary()
    {
        try {
            $totalVoucher = DB::table('myads_voucher')->count();
            $totalClaimed = DB::table('myads_voucher')->whereNotNull('user_id')->count();
            $totalNotClaimed = $totalVoucher - $totalClaimed;
            
            return [
                'total_voucher' => $totalVoucher,
                'total_claimed' => $totalClaimed,
                'total_not_claimed' => $totalNotClaimed
            ];
        } catch (\Exception $e) {
            return ['total_voucher' => 0, 'total_claimed' => 0, 'total_not_claimed' => 0];
        }
    }
    
    private function getTregRaceSummary()
    {
        try {
            $totalTreg = DB::table('treg')->count();
            
            // Menggunakan inner join untuk menghitung akuisisi dan revenue 
            // hanya untuk TREG yang memiliki voucher_code yang sama di voucher_treg
            $akuisisiData = DB::table('treg as t')
                ->join('voucher_treg as vt', 't.voucher_code', '=', 'vt.voucher_code')
                ->where('vt.status', 'PAID')
                ->select(
                    DB::raw('COUNT(vt.id) as total_akuisisi'),
                    DB::raw('COALESCE(SUM(vt.total_amount), 0) as total_revenue')
                )
                ->first();
            
            return [
                'total_treg' => $totalTreg,
                'total_akuisisi' => $akuisisiData->total_akuisisi ?? 0,
                'total_revenue' => $akuisisiData->total_revenue ?? 0
            ];
        } catch (\Exception $e) {
            return ['total_treg' => 0, 'total_akuisisi' => 0, 'total_revenue' => 0];
        }
    }
    
    private function getUserSummary()
    {
        try {
            $totalUsers = DB::table('users')->count();
            $totalAdmin = DB::table('users')->where('role', 'Admin')->count();
            $totalTsel = DB::table('users')->where('role', 'Tsel')->count();
            $totalTreg = DB::table('users')->where('role', 'Treg')->count();
            $activeUsers = DB::table('users')->where('status', 'Aktif')->count();
            
            return [
                'total_users' => $totalUsers,
                'total_admin' => $totalAdmin,
                'total_tsel' => $totalTsel,
                'total_treg' => $totalTreg,
                'active_users' => $activeUsers
            ];
        } catch (\Exception $e) {
            return ['total_users' => 0, 'total_admin' => 0, 'total_tsel' => 0, 'total_treg' => 0, 'active_users' => 0];
        }
    }
}