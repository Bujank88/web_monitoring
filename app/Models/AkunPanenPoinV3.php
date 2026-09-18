<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AkunPanenPoinV3 extends Model
{
    use HasFactory;

    protected $table = 'akun_panen_poin_v3';

    protected $fillable = [
        'uuid',
        'user_id',
        'nama_akun',
        'email_client',
        'password',
        'source',
    ];

    protected $hidden = [
        'password',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
