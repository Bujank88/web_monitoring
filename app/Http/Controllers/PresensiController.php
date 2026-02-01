<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PresensiController extends Controller
{
    /**
     * Halaman presensi utama - 1 page untuk clock in/out dan izin
     */
    public function index()
    {
        logUserLogin();
        $user = Auth::user();
        
        // Ambil presensi hari ini
        $presensiHariIni = Presensi::forUserToday($user->id)->first();
        
        return view('absensi.presensi', compact('presensiHariIni'));
    }

    /**
     * Clock In (Absen Datang)
     */
    public function clockIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ], [
            'latitude.required' => 'Lokasi harus dideteksi',
            'longitude.required' => 'Lokasi harus dideteksi',
            'foto.required' => 'Foto harus diambil',
            'foto.image' => 'File harus berupa gambar',
            'foto.max' => 'Ukuran foto maksimal 5MB',
        ]);

        $user = Auth::user();
        $now = Carbon::now();

        // Cek apakah sudah ada clock in hari ini
        $presensi = Presensi::forUserToday($user->id)->first();

        if ($presensi && $presensi->jam_datang) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah clock in hari ini'
            ]);
        }

        if (!$presensi) {
            $presensi = new Presensi();
            $presensi->user_id = $user->id;
            $presensi->tanggal = $now->toDateString();
        }

        // Upload foto dengan struktur: user_id/tanggal/foto_presensi_masuk.png
        $folderPath = $user->id . '/' . $now->toDateString();
        $fotoPath = $request->file('foto')->storeAs($folderPath, 'foto_presensi_masuk.png', 'public');

        // Simpan clock in
        $presensi->jam_datang = $now->toTimeString('minute');
        $presensi->latitude_datang = $request->latitude;
        $presensi->longitude_datang = $request->longitude;
        $presensi->foto_datang = $fotoPath;

        // Deteksi keterlambatan
        $jamKerjaAwal = Carbon::createFromFormat('H:i', '08:00');
        if ($now->greaterThan($jamKerjaAwal)) {
            $presensi->status_datang = 'Terlambat';
        } else {
            $presensi->status_datang = 'Hadir';
        }

        $presensi->save();

        return response()->json([
            'success' => true,
            'message' => 'Anda berhasil melakukan presensi. Status ' . $presensi->status_datang,
            'status' => $presensi->status_datang,
        ]);
    }

    /**
     * Clock Out (Absen Pulang)
     */
    public function clockOut(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ], [
            'latitude.required' => 'Lokasi harus dideteksi',
            'longitude.required' => 'Lokasi harus dideteksi',
            'foto.required' => 'Foto harus diambil',
            'foto.image' => 'File harus berupa gambar',
            'foto.max' => 'Ukuran foto maksimal 5MB',
        ]);

        $user = Auth::user();
        $now = Carbon::now();

        $presensi = Presensi::forUserToday($user->id)->first();

        if (!$presensi || !$presensi->jam_datang) {
            return response()->json([
                'success' => false,
                'message' => 'Anda harus clock in terlebih dahulu'
            ]);
        }

        if ($presensi->jam_pulang) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah clock out hari ini'
            ]);
        }

        // Upload foto dengan struktur: user_id/tanggal/foto_presensi_keluar.png
        $folderPath = $user->id . '/' . $now->toDateString();
        $fotoPath = $request->file('foto')->storeAs($folderPath, 'foto_presensi_keluar.png', 'public');

        // Simpan clock out
        $presensi->jam_pulang = $now->toTimeString('minute');
        $presensi->latitude_pulang = $request->latitude;
        $presensi->longitude_pulang = $request->longitude;
        $presensi->foto_pulang = $fotoPath;

        // Deteksi status pulang
        $jamKerjaAkhir = Carbon::createFromFormat('H:i', '17:00');
        if ($now->lessThan($jamKerjaAkhir)) {
            $presensi->status_pulang = 'Pulang Awal';
        } else {
            $presensi->status_pulang = 'Tepat Waktu';
        }

        $presensi->save();

        return response()->json([
            'success' => true,
            'message' => 'Anda berhasil melakukan presensi. Status Tepat Waktu',
        ]);
    }

    /**
     * Izin (Cuti/Sakit)
     */
    public function izin(Request $request)
    {
        $request->validate([
            'tipe_izin' => 'required|in:Izin,Sakit',
            'keterangan' => 'nullable|string|max:500',
        ], [
            'tipe_izin.required' => 'Tipe izin harus dipilih',
        ]);

        $user = Auth::user();
        $now = Carbon::now();

        // Cek apakah sudah ada presensi hari ini
        $presensi = Presensi::forUserToday($user->id)->first();

        if ($presensi && $presensi->jam_datang) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah clock in, tidak bisa input izin'
            ]);
        }

        // Cek apakah sudah ada izin/sakit hari ini
        if ($presensi && in_array($presensi->status_datang, ['Izin', 'Sakit'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah input izin/sakit hari ini, tidak bisa input izin lagi'
            ]);
        }

        if (!$presensi) {
            $presensi = new Presensi();
            $presensi->user_id = $user->id;
            $presensi->tanggal = $now->toDateString();
        }

        $presensi->status_datang = $request->tipe_izin;
        $presensi->keterangan = $request->keterangan;
        $presensi->save();

        return response()->json([
            'success' => true,
            'message' => 'Izin ' . $request->tipe_izin . ' berhasil disimpan',
        ]);
    }

    /**
     * Riwayat Presensi (Summary)
     * CVSR hanya bisa lihat presensi diri sendiri
     * Admin bisa lihat semua presensi
     */
    public function riwayat(Request $request)
    {
        logUserLogin();
        $user = Auth::user();
        
        // Default date range: 1 bulan ke belakang sampai hari ini
        $dateFrom = $request->input('dateFrom', now()->subMonth()->toDateString());
        $dateTo = $request->input('dateTo', now()->toDateString());
        
        $query = Presensi::query();
        
        // CVSR hanya bisa lihat presensi diri sendiri
        if ($user->role === 'cvsr') {
            $query->where('user_id', $user->id);
        }
        // Admin bisa lihat semua
        
        $presensi = $query
            ->whereBetween('tanggal', [$dateFrom, $dateTo])
            ->with('user')
            ->orderBy('tanggal', 'desc')
            ->paginate(20);
        
        return view('absensi.summary_presensi', compact('presensi', 'dateFrom', 'dateTo'));
    }
}
