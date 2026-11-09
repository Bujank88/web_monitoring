<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogbookModel extends Model
{
    protected $table = 'logbook'; // pastikan sesuai nama tabel di DB

    protected $fillable = [
        'leads_master_id',
        'komitmen',
        'plan_min_topup',
        'status',
        'bulan',
        'tahun',
    ];

    protected $casts = [
        'komitmen' => 'decimal:2',
        'plan_min_topup' => 'integer',
        'bulan' => 'integer',
        'tahun' => 'integer',
    ];

    /**
     * Relasi ke master leads
     */
    public function leadMaster()
    {
        return $this->belongsTo(LeadsMaster::class, 'leads_master_id');
    }

}
