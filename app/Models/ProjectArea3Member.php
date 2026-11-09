<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectArea3Member extends Model
{
    protected $table = 'project_area3_members';
    protected $fillable = ['tipe', 'nama', 'lokasi_kerja'];
}