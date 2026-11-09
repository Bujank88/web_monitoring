<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BClient extends Model
{
    protected $table = 'b2b_clients';

    protected $fillable = [
        'user_id',
        'company_name',
        'customer_phone',
        'customer_email',
        'customer_name',
        'sector',
        'myads_account',
        'remarks',
        // Legacy columns (for backward compatibility on existing DB schema)
        'client_name',
        'client_email',
        'client_phone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
