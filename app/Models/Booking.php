<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'nama',
        'lokasi',
        'keterangan',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'warna',
    ];
}
