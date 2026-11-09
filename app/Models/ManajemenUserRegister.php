<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManajemenUserRegister extends Model
{
    protected $table = 'manajemen_user_register';
    protected $primaryKey = 'id';
    public $timestamps = true; // karena ada created_at & updated_at

    protected $fillable = [
        'reg_id',
        'email',
        'instansi',
        'grup',
        'business_type',
        'business_category',
        'activation_status',
        'created_by',
        'updated_by',
        'mobile_phone',
        'ext_partner_id',
        'approval_status',
        'status_top_up',
        'created_date',
        'updated_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];
}
