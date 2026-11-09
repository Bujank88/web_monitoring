<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSponsorhip extends Model
{
    public $timestamps = false;
    protected $table = 'event_sponsorship';

    protected $fillable = [
        'area',
        'regional',
        'nama_event',
        'lokasi_event',
        'tanggal_event',
        'pic_event',
        'telp_pic_event',
        'pic_tsel',
        'telp_pic_tsel',
        'upload_proposal',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'tanggal_event' => 'date',
    ];
}
