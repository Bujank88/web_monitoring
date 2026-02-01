<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\LocationPresensiCvsr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
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
        
        // Ambil lokasi yang ditugaskan untuk CVSR
        $assignedLocation = null;
        if ($user->role === 'cvsr') {
            $assignedLocation = LocationPresensiCvsr::where('user_id', $user->id)->first();
        }
        
        return view('absensi.presensi', compact('presensiHariIni', 'assignedLocation'));
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

        // Validasi jarak untuk CVSR (hanya untuk clock in, bukan clock out)
        if ($user->role === 'cvsr') {
            $assignedLocation = LocationPresensiCvsr::where('user_id', $user->id)->first();
            
            if ($assignedLocation) {
                $distance = LocationPresensiCvsr::calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $assignedLocation->latitude,
                    $assignedLocation->longitude
                );

                // Maksimal jarak 150 meter
                if ($distance > 150) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda berada di luar area presensi yang ditentukan. Jarak: ' . round($distance) . ' meter. Maksimal jarak: 150 meter.',
                        'distance' => round($distance),
                        'max_distance' => 150,
                        'assigned_location' => $assignedLocation
                    ], 422);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin belum menentukan lokasi presensi untuk Anda'
                ], 422);
            }
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

        // Langsung send WA notif, jika gagal set false
        $this->sendPresensiWANotif($presensi, 'clockIn');

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

        // Langsung send WA notif, jika gagal set false
        $this->sendPresensiWANotif($presensi, 'clockOut');

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

        // Langsung send WA notif, jika gagal set false
        $this->sendPresensiWANotif($presensi, 'izin');

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

    /**
     * Send WA Notif untuk Presensi - langsung send & update status
     * Jika berhasil → set is_sent_* = true
     * Jika gagal → set is_sent_* = false
     */
    private function sendPresensiWANotif($presensi, $action = 'clockIn')
    {
        try {
            $waBot = env('WA_BOT_URL');
            $botPhone = env('WA_BOT_PHONE');

            if (!$waBot || !$botPhone) {
                \Log::warning('WA Bot configuration missing');
                $this->markSendFailed($presensi, $action);
                return;
            }

            $phone = preg_replace('/^0/', '62', $botPhone);
            if (!str_starts_with($phone, '62')) {
                $phone = '62' . $phone;
            }

            $user = $presensi->user;
            $postData = [
                'phone' => $phone,
                'nama_cvsr' => $user->name,
                'action' => $action,
            ];

            if ($action === 'clockIn') {
                $postData['tanggal'] = Carbon::parse($presensi->tanggal)->locale('id')->translatedFormat('d F Y');
                $postData['jam'] = is_object($presensi->jam_datang)
                    ? $presensi->jam_datang->format('H:i')
                    : substr($presensi->jam_datang, 0, 5);
                $postData['status'] = $presensi->status_datang;
                $postData['latitude'] = $presensi->latitude_datang;
                $postData['longitude'] = $presensi->longitude_datang;
                
                if ($user->role === 'cvsr') {
                    $assignedLocation = LocationPresensiCvsr::where('user_id', $user->id)->first();
                    if ($assignedLocation) {
                        $distance = LocationPresensiCvsr::calculateDistance(
                            $presensi->latitude_datang,
                            $presensi->longitude_datang,
                            $assignedLocation->latitude,
                            $assignedLocation->longitude
                        );
                        $postData['lokasi_penugasan_lat'] = $assignedLocation->latitude;
                        $postData['lokasi_penugasan_lng'] = $assignedLocation->longitude;
                        $postData['lokasi_penugasan_nama'] = $assignedLocation->keterangan;
                        $postData['distance'] = round($distance);
                    }
                }
                
                if ($presensi->foto_datang && Storage::disk('public')->exists($presensi->foto_datang)) {
                    $postData['foto_base64'] = base64_encode(Storage::disk('public')->get($presensi->foto_datang));
                    $postData['foto_mime'] = Storage::disk('public')->mimeType($presensi->foto_datang);
                }
            } elseif ($action === 'clockOut') {
                $postData['tanggal'] = Carbon::parse($presensi->tanggal)->locale('id')->translatedFormat('d F Y');
                $postData['jam'] = is_object($presensi->jam_pulang)
                    ? $presensi->jam_pulang->format('H:i')
                    : substr($presensi->jam_pulang, 0, 5);
                $postData['status'] = $presensi->status_pulang;
                $postData['latitude'] = $presensi->latitude_pulang;
                $postData['longitude'] = $presensi->longitude_pulang;
                
                if ($user->role === 'cvsr') {
                    $assignedLocation = LocationPresensiCvsr::where('user_id', $user->id)->first();
                    if ($assignedLocation) {
                        $distance = LocationPresensiCvsr::calculateDistance(
                            $presensi->latitude_pulang,
                            $presensi->longitude_pulang,
                            $assignedLocation->latitude,
                            $assignedLocation->longitude
                        );
                        $postData['lokasi_penugasan_lat'] = $assignedLocation->latitude;
                        $postData['lokasi_penugasan_lng'] = $assignedLocation->longitude;
                        $postData['lokasi_penugasan_nama'] = $assignedLocation->keterangan;
                        $postData['distance'] = round($distance);
                    }
                }
                
                if ($presensi->foto_pulang && Storage::disk('public')->exists($presensi->foto_pulang)) {
                    $postData['foto_base64'] = base64_encode(Storage::disk('public')->get($presensi->foto_pulang));
                    $postData['foto_mime'] = Storage::disk('public')->mimeType($presensi->foto_pulang);
                }
            } elseif ($action === 'izin') {
                $postData['tanggal'] = Carbon::parse($presensi->tanggal)->locale('id')->translatedFormat('d F Y');
                $postData['tipe_izin'] = $presensi->status_datang;
                $postData['keterangan'] = $presensi->keterangan;
            }

            $response = Http::timeout(30)->post($waBot . '/api/send-wa-presensi', $postData);

            if ($response->successful()) {
                $this->markSendSuccess($presensi, $action);
                \Log::info('WA notif sent successfully', ['action' => $action, 'presensi_id' => $presensi->id]);
            } else {
                $this->markSendFailed($presensi, $action);
                \Log::warning('WA notif failed', ['action' => $action, 'status' => $response->status()]);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending WA notif: ' . $e->getMessage());
            $this->markSendFailed($presensi, $action);
        }
    }

    /**
     * Mark send sebagai berhasil (set true)
     */
    private function markSendSuccess($presensi, $action)
    {
        $updateData = [];
        if ($action === 'clockIn') {
            $updateData['is_sent_clock_in'] = true;
        } elseif ($action === 'clockOut') {
            $updateData['is_sent_clock_out'] = true;
        } elseif ($action === 'izin') {
            $updateData['is_sent_izin'] = true;
        }
        $presensi->update($updateData);
    }

    /**
     * Mark send sebagai gagal (set false)
     */
    private function markSendFailed($presensi, $action)
    {
        $updateData = [];
        if ($action === 'clockIn') {
            $updateData['is_sent_clock_in'] = false;
        } elseif ($action === 'clockOut') {
            $updateData['is_sent_clock_out'] = false;
        } elseif ($action === 'izin') {
            $updateData['is_sent_izin'] = false;
        }
        $presensi->update($updateData);
    }

    /**
     * Get Foto - Serve foto dari storage dengan authentication
     */
    public function getFoto($filePath)
    {
        $user = Auth::user();
        
        // Validasi path - hanya allow format user_id/tanggal/foto_*.png
        if (!preg_match('/^\d+\/\d{4}-\d{2}-\d{2}\/foto_presensi_(masuk|keluar)\.png$/', $filePath)) {
            abort(403, 'Invalid file path');
        }

        // Untuk CVSR, hanya bisa akses foto mereka sendiri
        if ($user->role === 'cvsr') {
            $userId = explode('/', $filePath)[0];
            if ($userId != $user->id) {
                abort(403, 'Unauthorized');
            }
        }

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Foto tidak ditemukan');
        }

        return Storage::disk('public')->response($filePath);
    }
}
