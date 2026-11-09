<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LeadsMaster;

class LeadsSource extends Model
{
    // use HasFactory;

    protected $table = 'leads_source';

    protected $fillable = ['name'];

    public function leads()
    {
        return $this->hasMany(LeadsMaster::class, 'source_id');
    }
}
