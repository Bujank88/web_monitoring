<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\LeadsMaster;

class AkunAmLevelUp extends Model
{
    use HasFactory;

    protected $table = 'akun_am_level_up';

    protected $fillable = [
        'uuid',
        'user_id',
        'leads_master_id',
        'nama_akun',
        'email_client',
        'password',
        'source',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Auto-generate UUID saat create
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leadsMaster()
    {
        return $this->belongsTo(LeadsMaster::class, 'leads_master_id');
    }
}

