<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPanenPoin extends Model
{
    protected $table = 'user_panen_poin';
    
    protected $fillable = [
        'user_id',
        'nama_pelanggan',
        'akun_myads_pelanggan',
        'nomor_hp_pelanggan'
    ];
    
    // Relasi ke User (canvasser yang input)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
