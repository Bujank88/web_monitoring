<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralChampionAm extends Model
{
    public $timestamps = false;
    protected $table = 'referral_champion_am';

    protected $fillable = [
        'nama_tele_am',
        'no_hp',
        'email',
        'username_company_myads',
        'username',
        'created_at',
        'updated_at'
    ];
}
