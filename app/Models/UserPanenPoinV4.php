<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPanenPoinV4 extends Model
{
    protected $table = 'user_panen_poin_v4';

    protected $fillable = [
        'user_id',
        'nama_pelanggan',
        'akun_myads_pelanggan',
        'nomor_hp_pelanggan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
