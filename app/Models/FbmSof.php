<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FbmSof extends Model
{
    protected $table = 'fbm_sof';

    protected $fillable = [
        'sender_name',
        'nomor_wa',
        'waba_id',
        'pic',
        'verif_bisnis',
        'credit_line',
        'sof_file',
        'sof_uploaded_at',
    ];

    protected $casts = [
        'sof_uploaded_at' => 'datetime',
    ];
}
