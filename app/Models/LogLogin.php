<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogLogin extends Model
{
    protected $table = 'loglogin';

    protected $fillable = [
        'user_id',
        'tgl',
        'nama',
        'role',
        'email',
    ];

    protected $casts = [
        'tgl' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
