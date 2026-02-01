<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Presensi extends Model
{
    use HasFactory;

    protected $table = 'presensi';
    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_datang',
        'jam_pulang',
        'status_datang',
        'status_pulang',
        'latitude_datang',
        'longitude_datang',
        'latitude_pulang',
        'longitude_pulang',
        'foto_datang',
        'foto_pulang',
        'keterangan',
        'jam_kerja_awal',
        'jam_kerja_akhir',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jam_datang' => 'datetime:H:i:s',
        'jam_pulang' => 'datetime:H:i:s',
        'jam_kerja_awal' => 'datetime:H:i:s',
        'jam_kerja_akhir' => 'datetime:H:i:s',
    ];

    /**
     * Relationship: Presensi milik User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Ambil presensi hari ini
     */
    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal', Carbon::today());
    }

    /**
     * Scope: Ambil presensi per user hari ini
     */
    public function scopeForUserToday($query, $userId)
    {
        return $query->where('user_id', $userId)->whereDate('tanggal', Carbon::today());
    }

    /**
     * Scope: Ambil presensi per bulan
     */
    public function scopePerBulan($query, $bulan, $tahun)
    {
        return $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
    }

    /**
     * Check apakah user sudah absen datang hari ini
     */
    public static function sudahAbsenDatang($userId)
    {
        return self::where('user_id', $userId)
            ->whereDate('tanggal', Carbon::today())
            ->whereNotNull('jam_datang')
            ->exists();
    }

    /**
     * Check apakah user sudah absen pulang hari ini
     */
    public static function sudahAbsenPulang($userId)
    {
        return self::where('user_id', $userId)
            ->whereDate('tanggal', Carbon::today())
            ->whereNotNull('jam_pulang')
            ->exists();
    }

    /**
     * Hitung apakah terlambat berdasarkan jam kerja awal
     */
    public function isTerlambat()
    {
        if (!$this->jam_datang) {
            return false;
        }

        $jamDatang = Carbon::createFromFormat('H:i:s', $this->jam_datang->format('H:i:s'));
        $jamKerjaAwal = Carbon::createFromFormat('H:i:s', $this->jam_kerja_awal->format('H:i:s'));

        return $jamDatang->greaterThan($jamKerjaAwal);
    }

    /**
     * Hitung durasi kerja (jam pulang - jam datang)
     */
    public function getDurasiKerja()
    {
        if (!$this->jam_datang || !$this->jam_pulang) {
            return null;
        }

        $datang = Carbon::createFromFormat('H:i:s', $this->jam_datang->format('H:i:s'));
        $pulang = Carbon::createFromFormat('H:i:s', $this->jam_pulang->format('H:i:s'));

        return $datang->diffInMinutes($pulang);
    }

    /**
     * Format durasi kerja ke jam:menit
     */
    public function formatDurasiKerja()
    {
        $durasi = $this->getDurasiKerja();
        if (!$durasi) {
            return '-';
        }

        $jam = intdiv($durasi, 60);
        $menit = $durasi % 60;

        return "{$jam}h {$menit}m";
    }
}
