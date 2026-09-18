<?php

namespace App\Models\OneSynergy;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $table = 'one_synergy_referrals';

    protected $fillable = ['name', 'referral_code', 'status', 'created_by'];
}
