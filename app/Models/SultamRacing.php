<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SultamRacing extends Model
{
    public $timestamps = false;
    protected $table = 'sultam_racing';

    protected $fillable = [
        'jenis_akun',
        'nama_akun',
        'area',
        'email',
        'nama_am',
        'created_at',
        'updated_at'
    ];
}
