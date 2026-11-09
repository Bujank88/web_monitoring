<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    protected $table = 'revenue';
    protected $primaryKey = 'id';
    public $timestamps = true; // karena ada created_at & updated_at

    protected $fillable = [
        'tanggal',
        'id_transaksi',
        'id_klien',
        'nama_klien',
        'id_merchant',
        'ext_id_partner',
        'saldo_utama',
        'saldo_bonus',
        'diskon',
        'total',
        'metode_pembayaran',
        'jenis_kartu',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'saldo_utama' => 'integer',
        'saldo_bonus' => 'integer',
        'diskon' => 'integer',
        'total' => 'integer',
    ];
}
