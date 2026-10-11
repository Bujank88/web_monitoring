<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadSpectrum extends Model
{
    public const HEADERS = [
        'provider' => 'Provider',
        'last_name' => 'Last Name',
        'company_account' => 'Company / Account',
        'mobile_phone' => 'Mobile Phone',
        'email' => 'email',
        'company_size' => 'Company Size',
        'product' => 'product',
        'lead_source' => 'Lead Source',
        'create_date' => 'Create Date',
        'share_date' => 'Share Date',
        'description' => 'Description',
        'pillar' => 'Pillar',
        'industry_sector' => 'Sektor Industri Company',
        'fu' => 'FU',
        'response' => 'Respon',
    ];

    protected $table = 'leads_spectrum';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['create_date' => 'date', 'share_date' => 'date'];
    }
}
