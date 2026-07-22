<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsCanvasserSbp extends Model
{
    protected $table = 'leads_canvasser_sbp';

    protected $fillable = [
        'referral_id',
        'referral_code',
        'referral_name',
        'company_name',
        'email_myads',
        'mobile_phone',
        'created_by',
    ];
}
