<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GotoReport extends Model
{
    protected $table = 'goto_reports';

    protected $fillable = [
        'id_iklan',
        'tgl_tayang',
        'judul_pesan_iklan',
        'operator_seluler',
        'kategori_iklan',
        'tipe_kanal',
        'detil_status',
        'sukses',
        'gagal',
        'refunded',
        'read',
        'click',
        'total_harga',
        'source_file_name',
        'upload_batch',
    ];
}
