<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RahasiaBisnis extends Model
{
    protected $table = 'rahasia_bisnis_myads';

    protected $fillable = [
        'nama',
        'email',
        'nomor_hp',
        'jenis_usaha',
        'alamat_usaha',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
    ];

}
