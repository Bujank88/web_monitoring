<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BAmPointSummary extends Model
{
    protected $table = 'b2b_am_point_summaries';

    protected $fillable = [
        'user_id',
        'period_month',
        'client_count',
        'total_topup',
        'carry_in_amount',
        'total_amount_for_point',
        'point_decimal',
        'point_rounded',
        'campaign_point',
        'total_redeem_point',
        'carry_out_amount',
        'carry_out_decimal',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'total_topup' => 'float',
            'carry_in_amount' => 'float',
            'total_amount_for_point' => 'float',
            'point_decimal' => 'float',
            'point_rounded' => 'integer',
            'campaign_point' => 'integer',
            'total_redeem_point' => 'integer',
            'carry_out_amount' => 'float',
            'carry_out_decimal' => 'float',
            'client_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
