<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\RahasiaBisnis;
use App\Models\PadiUmkm;
use App\Models\CreatorPartner;
use App\Models\SimpatiTiktok;
use App\Models\ReferralChampionAm;
use App\Models\SultamRacing;
use App\Models\EventSponsorhip;
use Illuminate\Support\Str;
use App\Models\RekruterKol;


class GetDataController extends Controller
{
    private function normalizePhone($phone)
    {
        if (!$phone) return null;

        // buang semua karakter non-digit
        $phone = preg_replace('/\D/', '', $phone);

        // kalau sudah awalan 62, biarkan
        if (str_starts_with($phone, '62')) {
            return $phone;
        }

        // kalau awalan 0 → ganti 0 jadi 62
        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        // kalau tidak ada awalan 0 atau 62 (misal: 822xxxx)
        return '62' . $phone;
    }
    public function getDataRahasiaBisnis()
    {
        $url = "https://docs.google.com/spreadsheets/d/189GWwmBkByfgraTxSDjKbcmH9zjE8a83aIKtCYnpyq8/export?format=csv&gid=1498573724";

        try {
            $csv = @array_map('str_getcsv', file($url));
        } catch (\Exception $e) {
            Log::channel('rahasiaBisnis')->error("Gagal mengambil file CSV: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal ambil CSV'], 500);
        }

        if (empty($csv)) {
            Log::channel('rahasiaBisnis')->warning("CSV kosong dari URL: $url");
            return response()->json(['status' => 'error', 'message' => 'CSV kosong'], 400);
        }

        $header = array_map(fn($h) => Str::slug($h, '_'), $csv[0]);
        unset($csv[0]);

        $inserted = 0;

        foreach ($csv as $row) {
            if (count($row) < count($header)) continue;

            $rowAssoc = array_combine($header, $row);

            // ambil timestamp
            if (!empty($rowAssoc['timestamp'])) {
                try {
                    $created_at = Carbon::createFromFormat('d/m/Y H:i:s', $rowAssoc['timestamp'])
                        ->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $created_at = now();
                    Log::channel('rahasiaBisnis')->warning("Gagal parsing timestamp: {$rowAssoc['timestamp']}");
                }
            } else {
                $created_at = now();
            }

            // cek hanya di kolom kabupaten yang valid
            $kabupaten_kota = null;
            foreach ($rowAssoc as $colName => $value) {
                $colNameLower = strtolower($colName);
                if (Str::startsWith($colNameLower, 'kabupaten') || Str::startsWith($colNameLower, 'kabupaten_kota')) {
                    if (!empty($value)) {
                        $kabupaten_kota = $value;
                        break;
                    }
                }
            }

            RahasiaBisnis::create([
                'nama'          => $rowAssoc['nama'] ?? null,
                'email'         => $rowAssoc['email'] ?? null,
                'nomor_hp'      => $rowAssoc['nomor_hp'] ?? null,
                'jenis_usaha'   => $rowAssoc['jenis_usaha'] ?? null,
                'alamat_usaha'  => $rowAssoc['alamat_usaha'] ?? null,
                'provinsi'      => $rowAssoc['provinsi'] ?? null,
                'kabupaten_kota' => $kabupaten_kota,
                'kecamatan'     => $rowAssoc['silakan_isi_kecamatan'] ?? null,
                'created_at'    => $created_at,
                'updated_at'    => now(),
            ]);

            $inserted++;
        }

        Log::channel('rahasiaBisnis')->info("Import selesai, berhasil insert {$inserted} data dari CSV.");

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai! Total $inserted data berhasil dimasukkan"
        ], 201);
    }
    public function getDataPadiUmkm()
    {
        $url = "https://docs.google.com/spreadsheets/d/1Sdfhiy6DK2enNkM-L7SKgOBkAnfhND8IZXkakdPy07U/export?format=csv&gid=1905803457";

        try {
            $csv = @array_map('str_getcsv', file($url));
        } catch (\Exception $e) {
            Log::channel('padiUmkm')->error("Gagal mengambil file CSV: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal ambil CSV'], 500);
        }

        if (empty($csv)) {
            Log::channel('padiUmkm')->warning("CSV kosong dari URL: $url");
            return response()->json(['status' => 'error', 'message' => 'CSV kosong'], 400);
        }

        $header = array_map(fn($h) => Str::slug($h, '_'), $csv[0]);
        unset($csv[0]);

        $inserted = 0;

        foreach ($csv as $row) {
            if (count($row) < count($header)) continue;

            $rowAssoc = array_combine($header, $row);

            // ambil timestamp
            if (!empty($rowAssoc['timestamp'])) {
                try {
                    $created_at = Carbon::createFromFormat('d/m/Y H:i:s', $rowAssoc['timestamp'])
                        ->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $created_at = now();
                    Log::channel('padiUmkm')->warning("Gagal parsing timestamp: {$rowAssoc['timestamp']}");
                }
            } else {
                $created_at = now();
            }
            $normalizedPhone = $this->normalizePhone($rowAssoc['nomor_hp'] ?? null);

            $exists = PadiUmkm::where('no_hp', $normalizedPhone)
                ->exists();

            if (!$exists) {
                PadiUmkm::create([
                    'nama'          => $rowAssoc['nama_lengkap'] ?? null,
                    'nama_usaha'         => $rowAssoc['nama_usaha'] ?? null,
                    'email'      => $rowAssoc['email'] ?? null,
                    'no_hp'   => $normalizedPhone,
                    'created_at'    => $created_at,
                    'updated_at'    => now(),
                ]);

                $inserted++;
            }
        }

        Log::channel('padiUmkm')->info("Import selesai, berhasil insert {$inserted} data dari CSV.");

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai! Total $inserted data berhasil dimasukkan"
        ], 201);
    }
    public function getDataCreatorPartner()
    {
        $url = "https://docs.google.com/spreadsheets/d/1RxZpFhYXMjtwMxdinD9oT_0t3fFA8KqmXo59l0dcRbM/export?format=csv&gid=1067207517";

        try {
            $csv = @array_map('str_getcsv', file($url));
        } catch (\Exception $e) {
            Log::channel('creatorPartner')->error("Gagal mengambil file CSV: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal ambil CSV'], 500);
        }

        if (empty($csv)) {
            Log::channel('creatorPartner')->warning("CSV kosong dari URL: $url");
            return response()->json(['status' => 'error', 'message' => 'CSV kosong'], 400);
        }

        $header = array_map(fn($h) => Str::slug($h, '_'), $csv[0]);
        unset($csv[0]);

        $inserted = 0;

        foreach ($csv as $row) {
            if (count($row) < count($header)) continue;

            $rowAssoc = array_combine($header, $row);



            if (!empty($rowAssoc['timestamp'])) {
                $rawTimestamp = trim($rowAssoc['timestamp']);
                Log::channel('creatorPartner')->warning("Data {$rawTimestamp}");
                $formats = [
                    'd/m/Y H:i:s', // jam 2 digit
                    'd/m/Y G:i:s', // jam bisa 1 digit
                ];

                foreach ($formats as $format) {
                    try {
                        $created_at = Carbon::createFromFormat($format, $rawTimestamp)
                            ->format('Y-m-d H:i:s');
                        Log::channel('creatorPartner')->warning("created {$rawTimestamp}");
                        break; // kalau berhasil, langsung stop
                    } catch (\Exception $e) {
                        // coba format lain
                        continue;
                    }
                }

                if ($created_at === now()) {
                    Log::channel('creatorPartner')->warning("Gagal parsing timestamp: {$rawTimestamp}");
                }
            }


            $normalizedPhone = $this->normalizePhone($rowAssoc['no_hp_kol'] ?? null);
            // Cek apakah data sudah ada berdasarkan timestamp
            $exists = CreatorPartner::where('no_hp_kol', $normalizedPhone)
                ->exists();

            if (!$exists) {
                CreatorPartner::create([
                    'area'       => $rowAssoc['area'] ?? null,
                    'regional'   => $rowAssoc['regional'] ?? null,
                    'jenis_kol'  => $rowAssoc['jenis_kol'] ?? null,
                    'nama_kol'   => $rowAssoc['nama_kol'] ?? null,
                    'email_kol'  => $rowAssoc['email_kol'] ?? null,
                    'no_hp_kol'  => $normalizedPhone,
                    'created_at' => $created_at,
                    'updated_at' => now(),
                ]);

                $inserted++;
            }
        }

        Log::channel('creatorPartner')->info("Import selesai, berhasil insert {$inserted} data dari CSV.");

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai! Total $inserted data berhasil dimasukkan"
        ], 201);
    }
    public function getDataSimpatiTiktok()
    {
        $url = "https://docs.google.com/spreadsheets/d/1XOhtsI1nxGD_hbj2CO5BUBqf1LLHS_o_WhwTSCv5NIo/export?format=csv&gid=655754240";

        try {
            $csv = @array_map('str_getcsv', file($url));
        } catch (\Exception $e) {
            Log::channel('simpatiTiktok')->error("Gagal mengambil file CSV: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal ambil CSV'], 500);
        }

        if (empty($csv)) {
            Log::channel('simpatiTiktok')->warning("CSV kosong dari URL: $url");
            return response()->json(['status' => 'error', 'message' => 'CSV kosong'], 400);
        }

        $header = array_map(fn($h) => Str::slug($h, '_'), $csv[0]);
        unset($csv[0]);

        $inserted = 0;

        foreach ($csv as $row) {
            if (count($row) < count($header)) continue;

            $rowAssoc = array_combine($header, $row);

            if (!empty($rowAssoc['timestamp'])) {
                $rawTimestamp = trim($rowAssoc['timestamp']);
                Log::channel('simpatiTiktok')->warning("Data {$rawTimestamp}");
                $formats = [
                    'd/m/Y H:i:s', // jam 2 digit
                    'd/m/Y G:i:s', // jam bisa 1 digit
                ];

                foreach ($formats as $format) {
                    try {
                        $created_at = Carbon::createFromFormat($format, $rawTimestamp)
                            ->format('Y-m-d H:i:s');
                        Log::channel('simpatiTiktok')->warning("created {$rawTimestamp}");
                        break; // kalau berhasil, langsung stop
                    } catch (\Exception $e) {
                        // coba format lain
                        continue;
                    }
                }

                if ($created_at === now()) {
                    Log::channel('simpatiTiktok')->warning("Gagal parsing timestamp: {$rawTimestamp}");
                }
            }


            $rawPhone = $rowAssoc['nomor_telp_yg_di_daftarkan_di_myads'] ?? null;
            $normalizedPhone = $this->normalizePhone($rawPhone);

            // Cek apakah data sudah ada berdasarkan no hp
            $exists = SimpatiTiktok::where('no_hp', $normalizedPhone)->exists();

            // Cek apakah data sudah ada berdasarkan no hp
            $exists = SimpatiTiktok::where('no_hp', $normalizedPhone)->exists();

            if (!$exists) {
                SimpatiTiktok::insert([
                    'email'       => $rowAssoc['alamat_email'] ?? null,
                    'no_hp'       => $normalizedPhone,
                    'nama_lengkap' => $rowAssoc['nama_lengkap_sesuai_ktp'] ?? null,
                    'created_at'  => $created_at,
                    'updated_at'  => now(),
                ]);

                $inserted++;
            }
        }

        Log::channel('simpatiTiktok')->info("Import selesai, berhasil insert {$inserted} data dari CSV.");

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai! Total $inserted data berhasil dimasukkan"
        ], 201);
    }
    public function getDataReferralChampionAm()
    {
        $url = "https://docs.google.com/spreadsheets/d/1I6OvP09KH2K2o7Ksmijf2XPdoutvYlxKLJJ7b9igi_g/export?format=csv&gid=491427565";

        try {
            $csv = @array_map('str_getcsv', file($url));
        } catch (\Exception $e) {
            Log::channel('referralChampion')->error("Gagal mengambil file CSV: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal ambil CSV'], 500);
        }

        if (empty($csv)) {
            Log::channel('referralChampion')->warning("CSV kosong dari URL: $url");
            return response()->json(['status' => 'error', 'message' => 'CSV kosong'], 400);
        }

        $header = array_map(fn($h) => Str::slug($h, '_'), $csv[0]);
        unset($csv[0]);

        $inserted = 0;

        foreach ($csv as $row) {
            if (count($row) < count($header)) continue;

            $rowAssoc = array_combine($header, $row);

            if (!empty($rowAssoc['timestamp'])) {
                $rawTimestamp = trim($rowAssoc['timestamp']);
                Log::channel('referralChampion')->warning("Data {$rawTimestamp}");
                $formats = [
                    'd/m/Y H:i:s', // jam 2 digit
                    'd/m/Y G:i:s', // jam bisa 1 digit
                ];

                foreach ($formats as $format) {
                    try {
                        $created_at = Carbon::createFromFormat($format, $rawTimestamp)
                            ->format('Y-m-d H:i:s');
                        Log::channel('referralChampion')->warning("created {$rawTimestamp}");
                        break; // kalau berhasil, langsung stop
                    } catch (\Exception $e) {
                        // coba format lain
                        continue;
                    }
                }

                if ($created_at === now()) {
                    Log::channel('referralChampion')->warning("Gagal parsing timestamp: {$rawTimestamp}");
                }
            }


            $rawPhone = $rowAssoc['no_hp_tele_am'] ?? null;
            $normalizedPhone = $this->normalizePhone($rawPhone);


            // Cek apakah data sudah ada berdasarkan no hp
            $exists = ReferralChampionAm::where('no_hp', $normalizedPhone)->exists();

            if (!$exists) {
                ReferralChampionAm::insert([
                    'nama_tele_am'       => $rowAssoc['nama_tele_am'] ?? null,
                    'no_hp'       => $normalizedPhone,
                    'email' => $rowAssoc['email_tele_am'] ?? null,
                    'username_company_myads' => $rowAssoc['nama_akun_myAds_perusahaan_yang_diakuisisi'] ?? null,
                    'username' => $rowAssoc['akun_myads'] ?? null,
                    'created_at'  => $created_at,
                    'updated_at'  => now(),
                ]);

                $inserted++;
            }
        }

        Log::channel('referralChampion')->info("Import selesai, berhasil insert {$inserted} data dari CSV.");

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai! Total $inserted data berhasil dimasukkan"
        ], 201);
    }
    public function getDataSultamRacing()
    {
        $url = "https://docs.google.com/spreadsheets/d/1Z3Es9lMXvd9yxsHlE4phHvtBkpcQF65gpIQQ1P-yOs4/export?format=csv&gid=31344884";

        try {
            $csv = @array_map('str_getcsv', file($url));
        } catch (\Exception $e) {
            Log::channel('sultamRacing')->error("Gagal mengambil file CSV: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal ambil CSV'], 500);
        }

        if (empty($csv)) {
            Log::channel('sultamRacing')->warning("CSV kosong dari URL: $url");
            return response()->json(['status' => 'error', 'message' => 'CSV kosong'], 400);
        }

        $header = array_map(fn($h) => Str::slug($h, '_'), $csv[0]);
        unset($csv[0]);

        $inserted = 0;

        foreach ($csv as $row) {
            if (count($row) < count($header)) continue;

            $rowAssoc = array_combine($header, $row);

            if (!empty($rowAssoc['timestamp'])) {
                $rawTimestamp = trim($rowAssoc['timestamp']);
                Log::channel('sultamRacing')->warning("Data {$rawTimestamp}");
                $formats = [
                    'd/m/Y H:i:s', // jam 2 digit
                    'd/m/Y G:i:s', // jam bisa 1 digit
                ];

                foreach ($formats as $format) {
                    try {
                        $created_at = Carbon::createFromFormat($format, $rawTimestamp)
                            ->format('Y-m-d H:i:s');
                        Log::channel('sultamRacing')->warning("created {$rawTimestamp}");
                        break; // kalau berhasil, langsung stop
                    } catch (\Exception $e) {
                        // coba format lain
                        continue;
                    }
                }

                if ($created_at === now()) {
                    Log::channel('sultamRacing')->warning("Gagal parsing timestamp: {$rawTimestamp}");
                }
            }



            $email = $rowAssoc['email_new_register_myads'] ?? null;
            // Cek apakah data sudah ada berdasarkan no hp
            $exists = SultamRacing::where('email', $email)->exists();

            if (!$exists) {
                SultamRacing::insert([
                    'jenis_akun'       => $rowAssoc['account_am'] ?? null,
                    'nama_akun'       => $rowAssoc['nama_account'] ?? null,
                    'area'       => $rowAssoc['area'] ?? null,
                    'email'       => $email,
                    'nama_am'       => $rowAssoc['nama_am'] ?? null,
                    'created_at'  => $created_at,
                    'updated_at'  => now(),
                ]);

                $inserted++;
            }
        }

        Log::channel('sultamRacing')->info("Import selesai, berhasil insert {$inserted} data dari CSV.");

        return response()->json([
            'status' => 'success',
            'message' => "Import selesai! Total $inserted data berhasil dimasukkan"
        ], 201);
    }
    public function getDataEventSponsorhip()
    {
        $url = "https://docs.google.com/spreadsheets/d/1UUWgHvxNb8wX4CF-B8H1od2HHzGiS-nip_EclQmT-50/export?format=csv&gid=1042083776";

        try {
            $csv = @array_map('str_getcsv', file($url));
        } catch (\Exception $e) {
            Log::channel('EventSponsorhip')->error("Gagal mengambil file CSV: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal ambil CSV'], 500);
        }

        if (empty($csv)) {
            Log::channel('EventSponsorhip')->warning("CSV kosong dari URL: $url");
            return response()->json(['status' => 'error', 'message' => 'CSV kosong'], 400);
        }

        $header = array_map(fn($h) => Str::slug($h, '_'), $csv[0]);
        unset($csv[0]);

        $inserted = 0;

        foreach ($csv as $row) {
            if (count($row) < count($header)) continue;

            $rowAssoc = array_combine($header, $row);

            if (!empty($rowAssoc['timestamp'])) {
                $rawTimestamp = trim($rowAssoc['timestamp']);
                $created_at = null;

                $formats = ['d/m/Y H:i:s', 'd/m/Y G:i:s'];
                foreach ($formats as $format) {
                    try {
                        $created_at = Carbon::createFromFormat($format, $rawTimestamp)->format('Y-m-d H:i:s');
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if (!$created_at) {
                    Log::channel('EventSponsorhip')->warning("Gagal parsing timestamp: {$rawTimestamp}");
                    $created_at = now();
                }
            } else {
                $created_at = now();
            }

            $namaEvent = $rowAssoc['nama_event'] ?? null;
            $lokasiEvent = $rowAssoc['lokasi_event'] ?? null;

            // cek apakah sudah ada (contoh pakai nama_event + tanggal_event)
            $exists = EventSponsorhip::where('nama_event', $namaEvent)
                ->where('tanggal_event', $rowAssoc['tanggal_event'] ?? null)
                ->exists();

            if (!$exists) {
                EventSponsorhip::insert([
                    'area'            => $rowAssoc['area'] ?? null,
                    'regional'        => $rowAssoc['regional'] ?? null,
                    'nama_event'      => $namaEvent,
                    'lokasi_event'    => $lokasiEvent,
                    'tanggal_event' => !empty($rowAssoc['tanggal_event'])
                        ? Carbon::createFromFormat('d/m/Y', trim($rowAssoc['tanggal_event']))->format('Y-m-d')
                        : null,
                    'pic_event'       => $rowAssoc['pic_event_eo'] ?? null,
                    'telp_pic_event'  => $rowAssoc['no_telp_pic_event_eo'] ?? null,
                    'pic_tsel'        => $rowAssoc['nama_pic_tsel'] ?? null,
                    'telp_pic_tsel'   => $rowAssoc['no_telp_pic_tsel'] ?? null,
                    'upload_proposal' => $rowAssoc['upload_proposal_pdf'] ?? null,
                    'created_at'      => $created_at,
                    'updated_at'      => now(),
                ]);

                $inserted++;
            }
        }

        Log::channel('EventSponsorhip')->info("Import selesai, berhasil insert {$inserted} data dari CSV.");

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai! Total $inserted data berhasil dimasukkan"
        ], 201);
    }
    public function getDataRekruterKol()
    {
        $url = "https://docs.google.com/spreadsheets/d/1ZklhjrtHoNa3RscKuPKUJDJbd5tssF-9rWaqwRCurC8/export?format=csv&gid=1774471290";

        try {
            $csv = @array_map('str_getcsv', file($url));
        } catch (\Exception $e) {
            Log::channel('rekruterKol')->error("Gagal mengambil file CSV: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal ambil CSV'], 500);
        }

        if (empty($csv)) {
            Log::channel('rekruterKol')->warning("CSV kosong dari URL: $url");
            return response()->json(['status' => 'error', 'message' => 'CSV kosong'], 400);
        }

        // Ambil header → ubah ke snake_case
        $header = array_map(fn($h) => Str::slug($h, '_'), $csv[0]);
        unset($csv[0]);

        $inserted = 0;

        foreach ($csv as $row) {
            if (count($row) < count($header)) continue;

            $rowAssoc = array_combine($header, $row);

            $email = trim($rowAssoc['email_akun_myads'] ?? '');
            if (!$email) continue;

            // parse timestamp dari Google Form
            $rawTimestamp = $rowAssoc['timestamp'] ?? null;
            $created_at = null;

            if ($rawTimestamp) {
                $formats = ['d/m/Y H:i:s', 'd/m/Y G:i:s', 'Y-m-d H:i:s']; // variasi format
                foreach ($formats as $format) {
                    try {
                        $created_at = Carbon::createFromFormat($format, trim($rawTimestamp));
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
            if (!$created_at) {
                Log::channel('rekruterKol')->warning("Gagal parsing timestamp: {$rawTimestamp}");
                $created_at = now();
            }

            // cek email sudah ada atau belum
            $exists = RekruterKol::where('email', $email)->exists();

            if (!$exists) {
                RekruterKol::create([
                    'nama'          => $rowAssoc['nama'] ?? null,
                    'email'         => $email,
                    'no_hp'         => $rowAssoc['nomor_hp'] ?? null,
                    'referral_code' => $rowAssoc['referral_code_kol_influencer'] ?? null,
                    'created_at'    => $created_at,
                    'updated_at'    => now(),
                ]);

                $inserted++;
            }
        }

        Log::channel('rekruterKol')->info("Import selesai, berhasil insert {$inserted} data dari CSV.");

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai! Total $inserted data berhasil dimasukkan"
        ], 201);
    }
}
