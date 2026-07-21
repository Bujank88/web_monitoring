<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsPowerhouse extends Model
{
    protected $table = 'leads_powerhouse';

    protected $fillable = [
        'user_id',
        'source_id',
        'sector_id',
        'regional',
        'kode_voucher',
        'company_name',
        'mobile_phone',
        'email',
        'status',
        'nama',
        'address',
        'myads_account',
        'data_type',
        'komitmen',
        'plan_min_topup',
        'remarks',
        'flag_event',
        'usecase',
        'reg_id',
    ];
}
