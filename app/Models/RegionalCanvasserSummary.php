<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionalCanvasserSummary extends Model
{
    protected $table = 'regional_canvasser_summary';
    
    protected $fillable = [
        'user_id',
        'canvasser_name',
        'regional',
        'bulan',
        'leads',
        'existing_akun',
        'new_akun',
        'top_up_new_akun_count',
        'top_up_existing_akun_count',
        'top_up_new_akun_rp',
        'top_up_existing_akun_rp',
        'total_top_up_rp',
        'target',
        'achievement_percent',
        'gap',
        'gap_daily',
        'remaining_days',
        'mom_prev_partial',
        'mom_current_partial',
        'mom_prev_remaining',
        'mom_gap',
    ];
    
    protected $casts = [
        'top_up_new_akun_rp' => 'decimal:2',
        'top_up_existing_akun_rp' => 'decimal:2',
        'total_top_up_rp' => 'decimal:2',
        'target' => 'decimal:2',
        'achievement_percent' => 'decimal:4',
        'gap' => 'decimal:2',
        'gap_daily' => 'decimal:2',
        'mom_prev_partial' => 'decimal:2',
        'mom_current_partial' => 'decimal:2',
        'mom_prev_remaining' => 'decimal:2',
        'mom_gap' => 'decimal:2',
    ];
    
    /**
     * Get data for current month
     */
    public static function getCurrentMonth()
    {
        return self::where('bulan', now()->format('Y-m'))
            ->orderBy('achievement_percent', 'desc')
            ->get();
    }
    
    /**
     * Get data for specific month
     */
    public static function getByMonth($month)
    {
        return self::where('bulan', $month)
            ->orderBy('achievement_percent', 'desc')
            ->get();
    }
}
