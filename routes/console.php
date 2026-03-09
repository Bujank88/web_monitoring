<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\GetDataController;
use App\Http\Controllers\PanenPoinController;
use App\Http\Controllers\AmLevelUpController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\LogbookDailyController;
use App\Http\Controllers\LeadsMasterController;
use App\Models\Presensi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// // ===== Schedule: Retry send notifikasi presensi yang gagal setiap menit =====
Schedule::call(function () {
    \Log::info('=== RETRY SEND PRESENSI NOTIFICATIONS STARTED ===');
    
    $failedPresensi = Presensi::query()
        ->where(function ($query) {
            $query->where('is_sent_clock_in', false)->whereNotNull('jam_datang')
                  ->orWhere('is_sent_clock_out', false)->whereNotNull('jam_pulang')
                  ->orWhere('is_sent_izin', false)->where('status_datang', '!=', 'Hadir');
        })
        ->limit(50)
        ->get();

    \Log::info("Total failed presensi found: " . count($failedPresensi));

    $success = 0;
    $failed = 0;

    foreach ($failedPresensi as $presensi) {
        try {
            $waBot = env('WA_BOT_URL');
            $botPhone = env('WA_BOT_PHONE');

            if (!$waBot || !$botPhone) continue;

            $phone = preg_replace('/^0/', '62', $botPhone);
            if (!str_starts_with($phone, '62')) {
                $phone = '62' . $phone;
            }

            $pending = $presensi->getPendingNotifications();

            foreach ($pending as $action) {
                $postData = [
                    'phone' => $phone,
                    'nama_cvsr' => $presensi->user->name,
                    'action' => $action,
                ];

                if ($action === 'clockIn') {
                    $postData['tanggal'] = Carbon::parse($presensi->tanggal)->locale('id')->translatedFormat('d F Y');
                    $postData['jam'] = is_object($presensi->jam_datang) 
                        ? $presensi->jam_datang->format('H:i')
                        : $presensi->jam_datang;
                    $postData['status'] = $presensi->status_datang;
                    $postData['latitude'] = $presensi->latitude_datang;
                    $postData['longitude'] = $presensi->longitude_datang;
                    
                    if ($presensi->user->role === 'cvsr') {
                        $assignedLocation = \App\Models\LocationPresensiCvsr::where('user_id', $presensi->user->id)->first();
                        if ($assignedLocation) {
                            $distance = \App\Models\LocationPresensiCvsr::calculateDistance(
                                $presensi->latitude_datang, $presensi->longitude_datang,
                                $assignedLocation->latitude, $assignedLocation->longitude
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
                        : $presensi->jam_pulang;
                    $postData['status'] = $presensi->status_pulang;
                    $postData['latitude'] = $presensi->latitude_pulang;
                    $postData['longitude'] = $presensi->longitude_pulang;
                    
                    if ($presensi->user->role === 'cvsr') {
                        $assignedLocation = \App\Models\LocationPresensiCvsr::where('user_id', $presensi->user->id)->first();
                        if ($assignedLocation) {
                            $distance = \App\Models\LocationPresensiCvsr::calculateDistance(
                                $presensi->latitude_pulang, $presensi->longitude_pulang,
                                $assignedLocation->latitude, $assignedLocation->longitude
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

                $response = Http::timeout(60)->post($waBot . '/api/send-wa-presensi', $postData);

                if ($response->successful()) {
                    // Update status jadi true
                    if ($action === 'clockIn') {
                        $presensi->update(['is_sent_clock_in' => true]);
                    } elseif ($action === 'clockOut') {
                        $presensi->update(['is_sent_clock_out' => true]);
                    } elseif ($action === 'izin') {
                        $presensi->update(['is_sent_izin' => true]);
                    }
                    $success++;
                } else {
                    $failed++;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Retry send presensi notification error: ' . $e->getMessage());
            $failed++;
        }
    }

    \Log::info("Retry Send Presensi - Success: {$success}, Failed: {$failed}");
})->everyMinute()->name('retrySendPresensiNotifications');

Schedule::call(function () {
    app(PanenPoinController::class)->refreshSummaryPanenPoin();
})->everyFiveMinutes()->name('refreshSummaryPanenPoin');

Schedule::call(function () {
    app(AmLevelUpController::class)->refreshSummaryamlevelup();
})->everyTwoMinutes()->name('refreshSummaryamlevelup');

Schedule::call(function () {
    app(LogbookController::class)->refreshLogbookStatus();
})->everyTwoMinutes()->name('refreshLogbookStatus');

Schedule::call(function () {
    app(LogbookDailyController::class)->refreshLogbookDaily();
})->everyTwoMinutes()->name('refreshLogbookDaily');

// Schedule::call(function () {
//     app(LeadsMasterController::class)->syncLeadsWithRegistration();
// })->everyTenMinutes()->name('syncLeadsWithRegistration');

Schedule::call(function () {
    app(LeadsMasterController::class)->syncLeadsFromTopUp();
})->everyTenMinutes()->name('syncLeadsFromTopUp');

Schedule::call(function () {
    app(LeadsMasterController::class)->syncLeadsWithRegional();
})->everyTenMinutes()->name('syncLeadsWithRegional');

Schedule::call(function () {
    app(LeadsMasterController::class)->refreshDetailLeadsSummary();
})->everyFiveMinutes()->name('refreshDetailLeadsSummary');

// Refresh Regional Canvasser Summary setiap 5 menit
Schedule::command('summary:refresh-regional-canvasser')->everyFiveMinutes()->name('refreshRegionalCanvasserSummary');

// // ===== Schedule: Retry send notifikasi logbook yang gagal setiap menit =====
Schedule::call(function () {
    \Log::info('=== RETRY SEND LOGBOOK NOTIFICATIONS STARTED ===');
    
    $failedLogbook = DB::table('logbook_daily')
        ->join('leads_master', 'leads_master.id', '=', 'logbook_daily.leads_master_id')
        ->join('users', 'users.id', '=', 'leads_master.user_id')
        ->select('logbook_daily.id')
        ->where(function ($query) {
            $query->where('logbook_daily.is_sent_logbook', false)
                  ->whereNotNull('logbook_daily.realisasi_photo');
        })
        ->limit(50)
        ->get();

    \Log::info("Total failed logbook found: " . count($failedLogbook));

    $success = 0;
    $failed = 0;

    foreach ($failedLogbook as $logbookDaily) {
        try {
            $result = app(LogbookDailyController::class)->sendLogbookNotification($logbookDaily->id);
            if ($result) {
                $success++;
            } else {
                $failed++;
            }
        } catch (\Exception $e) {
            \Log::error('Retry send logbook notification error: ' . $e->getMessage());
            $failed++;
        }
    }

    \Log::info("Retry Send Logbook - Success: {$success}, Failed: {$failed}");
})->everyMinute()->name('retrySendLogbookNotifications');