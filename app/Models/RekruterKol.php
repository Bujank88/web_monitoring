<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekruterKol extends Model
{
     public $timestamps = false;
    protected $table = 'rekruter_kol';

    protected $fillable = [
        'nama',
        'email',
        'no_hp',
        'referral_code',
        'created_at',
        'updated_at'
    ];
}
