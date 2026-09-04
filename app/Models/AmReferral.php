<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AmReferral extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public function scopeActiveAm($query)
    {
        return $query->whereRaw('UPPER(role) = ?', ['AM'])
            ->whereIn(DB::raw('UPPER(referral_code)'), ['AM1', 'AM2', 'AM3', 'AM4', 'AM5', 'AM6', 'AM7', 'AM8'])
            ->where('status', 'Aktif');
    }
}
