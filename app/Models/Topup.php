<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use app\Models\LeadsMaster;

class Topup extends Model
{
    protected $connection = 'pgsql'; // jika beda DB
    protected $table = 'em_myads_topup';

    protected $dates = ['tgl_transaksi'];

    public function masterCvs()
    {
        return $this->belongsTo(LeadsMaster::class, 'email_client', 'email');
    }
}
