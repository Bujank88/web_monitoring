<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDs extends Model
{
    protected $table = 'project_ds';
    protected $fillable = ['user_id', 'kode_ds', 'nama', 'city', 'no_hp', 'email', 'status_akun', 'kode_referal', 'no_rek'];
}