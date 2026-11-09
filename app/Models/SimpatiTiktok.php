<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimpatiTiktok extends Model
{
    public $timestamps = false;
    protected $table = 'simpati_tiktok';

    protected $fillable = [
        'email',
        'no_hp',
        'nama_lengkap',
        'created_at',
        'updated_at'
    ];
}
