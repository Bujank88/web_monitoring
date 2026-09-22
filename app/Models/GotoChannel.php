<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GotoChannel extends Model
{
    protected $table = 'goto';

    protected $fillable = [
        'reg_id',
        'email_myads',
        'email_parent',
        'remark',
    ];
}
