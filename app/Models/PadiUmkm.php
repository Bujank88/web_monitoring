<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PadiUmkm extends Model
{
    public $timestamps = false;
    protected $table = 'padi_umkm';

    protected $fillable = [
        'nama',
        'nama_usaha',
        'email',
        'no_hp',
        'created_at',
        'updated_at'
    ];
}
