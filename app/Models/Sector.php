<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LeadsMaster;
class Sector extends Model
{
    // use HasFactory;

    protected $table = 'sectors';

    protected $fillable = ['name'];

    public function leads()
    {
        return $this->hasMany(LeadsMaster::class, 'sector_id');
    }
}
