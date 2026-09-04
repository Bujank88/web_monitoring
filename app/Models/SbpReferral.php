<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SbpReferral extends Model
{
    protected $table = 'sbp_referrals';

    protected $fillable = [
        'user_id',
        'name',
        'user_email',
        'referral_code',
        'status',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
