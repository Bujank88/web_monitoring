<?php

namespace App\Models\OneSynergy;

use Illuminate\Database\Eloquent\Model;

class CampaignReport extends Model
{
    protected $table = 'one_synergy_reports';

    protected $fillable = [
        'id_iklan', 'tgl_tayang', 'judul_pesan_iklan', 'operator_seluler',
        'kategori_iklan', 'tipe_kanal', 'detil_status', 'sukses', 'gagal',
        'refunded', 'read', 'click', 'total_harga', 'source_file_name', 'upload_batch',
    ];

    protected $casts = [
        'tgl_tayang' => 'date',
        'sukses' => 'integer',
        'gagal' => 'integer',
        'refunded' => 'integer',
        'read' => 'integer',
        'click' => 'integer',
        'total_harga' => 'integer',
    ];
}
