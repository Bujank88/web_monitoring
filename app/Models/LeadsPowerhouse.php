<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LeadsSource;
use App\Models\Sector;
use App\Models\User;

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
        'solusi',
        'reg_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function source()
    {
        return $this->belongsTo(LeadsSource::class, 'source_id');
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }
}
