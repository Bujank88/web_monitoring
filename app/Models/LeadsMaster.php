<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LeadsSource;
use App\Models\User;
use App\Models\Sector;
use App\Events\LeadsMasterUpdated;

class LeadsMaster extends Model
{
    // Nama tabel
    protected $table = 'leads_master';

    // Kolom yang bisa diisi mass-assignment
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
    ];
    
    // Events untuk trigger summary update
    protected $dispatchesEvents = [
        'updated' => LeadsMasterUpdated::class,
        'created' => LeadsMasterUpdated::class,
    ];
    
    // RELATION TO USER
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // RELATION TO LEAD SOURCE
    public function source()
    {
        return $this->belongsTo(LeadsSource::class, 'source_id');
    }

    // RELATION TO SECTOR
    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }
}
