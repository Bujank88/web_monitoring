<?php

namespace App\Http\Controllers;

use App\Models\CreatorPartner;
use App\Models\EventSponsorhip;
use App\Models\PadiUmkm;
use App\Models\RahasiaBisnis;
use App\Models\ReferralChampionAm;
use App\Models\RekruterKol;
use App\Models\SimpatiTiktok;
use App\Models\SultamRacing;
use Illuminate\Http\Request;
use App\Models\User;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Session;
use Carbon\Carbon;
use Dflydev\DotAccessData\Data;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class BackController extends Controller
{
    public function registerStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'nope' => 'required',
            'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
            'role' => 'required|in:Admin,Tsel',
        ]);
        $data = [
            'name' => $request->name,
            'nohp' => $request->nope,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make('123456'),
            'status' => 'Aktif'
        ];
        $nope = $request->nope;
        if (Str::startsWith($nope, '08')) {
            $nope = '62' . substr($nope, 1);
        }
        $data['nohp'] = $nope;
        User::create($data);
        return redirect()->back()->with('success', 'User berhasil didaftarkan!');
    }
    public function login(Request $request)
    {
        // 1. Validasi form
        $request->validate([
            'email' => [
                'required',
                'email',
                // 'regex:/^[a-zA-Z0-9._%+-]+@telkomsel\.co\.id$/', // hanya email @telkomsel.co.id
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // semua domain
            ],
            'password' => 'required',
        ], [
            'email.regex' => 'Email harus menggunakan domain @telkomsel.co.id',
        ]);

        // 2. Coba login
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            // 3. Cek status
            if ($user->status !== 'Aktif') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akun Anda belum aktif.',
                ])->withInput();
            }

            // 4. Log user login
            logUserLogin();

            // 5. Arahkan sesuai role
            switch ($user->role) {
                case 'Admin':
                case 'Tsel':
                    return redirect()->route('admin.home');
                case 'Treg':
                    return redirect()->route('race_summary_treg');
                case 'cvsr':
                    return redirect()->route('leads-master.index');
                default:
                    return redirect()->route('home'); // fallback
            }
        }

        // 6. Kalau gagal login
        return back()->withErrors([
            'email' => 'Email atau Password Anda salah.',
        ])->withInput();
    }

    public function getPadiUmkmData(Request $request)
    {
        if ($request->has('tanggal') && !empty($request->tanggal)) {
            $tanggal = $request->tanggal;
            $date = Carbon::parse($tanggal);
            $month = $date->month;
            $year = $date->year;
            $data = DB::table('summary_padi_umkm')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $data = DB::table('summary_padi_umkm')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }
    public function getPadiUmkmSummary(Request $request)
    {
        $query = DB::table('summary_padi_umkm');

        if ($request->has('tanggal') && !empty($request->tanggal)) {
            $tanggal = $request->tanggal;
            $date = Carbon::parse($tanggal);
            $month = $date->month;
            $year = $date->year;
            $query->whereMonth('created_at', $month)
                ->whereYear('created_at', $year);
        }

        $totalForm = $query->count();

        // Clone query for aggregation
        $topupQuery = clone $query;

        $jumlahTopup = $topupQuery->sum('jumlah_topup');
        $totalTopup = $topupQuery->sum('total_topup');

        return response()->json([
            'total_form'    => $totalForm,
            'jumlah_topup'  => $jumlahTopup,
            'total_topup'   => $totalTopup,
        ]);
    }
    public function getEventSponsorship(Request $request)
    {
        $data = EventSponsorhip::orderBy('created_at', 'desc')->get();

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }


    public function getCreatorPartner(Request $request)
    {
        // Ambil semua creator + total_invited (subquery)
        $creators = CreatorPartner::select(
            'creator_partner.*',
            DB::raw('(SELECT COUNT(*) FROM rekruter_kol WHERE rekruter_kol.referral_code = creator_partner.referral_code) as total_invited')
        )
            ->when($request->area, function ($query, $area) {
                $query->where('creator_partner.area', $area);
            })
            ->when($request->region, function ($query, $region) {
                $query->where('creator_partner.regional', $region);
            })
            ->when($request->jenis_kol, function ($query, $jenis_kol) {
                $query->where('creator_partner.jenis_kol', $jenis_kol);
            })
            ->orderBy('creator_partner.created_at', 'desc')
            ->get();

        // Hitung tier untuk setiap creator partner
        $creators->transform(function ($creator) use ($request) {
            // ambil list email rekruter untuk referral ini
            $rekruterEmails = DB::table('rekruter_kol')
                ->where('referral_code', $creator->referral_code)
                ->pluck('email')
                ->toArray();

            if (empty($rekruterEmails)) {
                $creator->tier = '-';
            } else {
                // ambil total topup per email
                $topupPerAkun = DB::table('revenue_kol')
                    ->whereIn('email', $rekruterEmails)
                    ->select('email', DB::raw('SUM(jumlah_top_up) as total_topup'))
                    ->groupBy('email')
                    ->pluck('total_topup', 'email');

                // set minimum sesuai jenis_kol
                $minTopup = 0;
                if ($creator->jenis_kol === 'KOL as a Buzzer') {
                    $minTopup = 250000;
                } elseif ($creator->jenis_kol === 'KOL as a Seller Online/Afiliate') {
                    $minTopup = 200000;
                }

                // hitung berapa akun yang memenuhi minimum
                $eligibleAccounts = collect($topupPerAkun)->filter(function ($total) use ($minTopup) {
                    return $total >= $minTopup;
                })->count();

                // tentukan tier
                if ($eligibleAccounts >= 30) {
                    $creator->tier = 'Platinum';
                } elseif ($eligibleAccounts >= 20) {
                    $creator->tier = 'Gold';
                } elseif ($eligibleAccounts >= 10) {
                    $creator->tier = 'Silver';
                } elseif ($eligibleAccounts >= 5) {
                    $creator->tier = 'Bronze';
                } else {
                    $creator->tier = '-';
                }
            }

            // Filter berdasarkan tier (kalau user pilih filter tier)
            if ($request->tier && $creator->tier !== $request->tier) {
                $creator->hide = true; // kasih flag biar nanti dihapus
            }

            return $creator;
        });

        // Buang data yang tidak sesuai tier filter
        if ($request->tier) {
            $creators = $creators->reject(function ($creator) {
                return isset($creator->hide) && $creator->hide === true;
            });
        }

        // Kembalikan collection ke DataTables
        return DataTables::of($creators)->make(true);
    }
    public function getRekrutBuzzer(Request $request)
    {
        // Ambil referral_code semua creator dengan jenis KOL = Buzzer
        $buzzerReferralCodes = DB::table('creator_partner')
            ->where('jenis_kol', 'KOL as a Buzzer')
            ->pluck('referral_code');

        // Ambil semua rekruter yang referral_code-nya ada di list buzzer
        $rekrut = DB::table('rekruter_kol')
            ->whereIn('referral_code', $buzzerReferralCodes)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform hasil biar sesuai table kamu
        $rekrut->transform(function ($item) {
            // Hitung total topup untuk email rekruter ini
            $totalTopup = DB::table('revenue_kol')
                ->where('email', $item->email)
                ->sum('jumlah_top_up');

            // Tentukan nilai minimal topup & remarks
            $minTopup = 250000; // karena jenis_kol = Buzzer
            $item->nilai_min_topup = $minTopup;
            $item->jumlah_top_up = $totalTopup;
            $item->remarks = $totalTopup >= $minTopup ? 'Eligible' : 'Not Eligible';

            return $item;
        });

        return DataTables::of($rekrut)
            ->addColumn('nilai_min_topup', function ($row) {
                return number_format($row->nilai_min_topup, 0, ',', '.');
            })
            ->addColumn('jumlah_top_up', function ($row) {
                return number_format($row->jumlah_top_up, 0, ',', '.');
            })
            ->addColumn('remarks', function ($row) {
                return $row->remarks;
            })
            ->make(true);
    }
    public function getRekruterInfluencer(Request $request)
    {
        // Ambil referral_code semua creator dengan jenis KOL = Influencer
        $influencerReferralCodes = DB::table('creator_partner')
            ->where('jenis_kol', 'KOL as a Seller Online/Afiliate')
            ->pluck('referral_code');

        // Ambil semua rekruter yang referral_code-nya ada di list influencer
        $rekrut = DB::table('rekruter_kol')
            ->whereIn('referral_code', $influencerReferralCodes)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transform hasil biar sesuai table kamu
        $rekrut->transform(function ($item) {
            // Hitung total topup untuk email rekruter ini
            $totalTopup = DB::table('revenue_kol')
                ->where('email', $item->email)
                ->sum('jumlah_top_up');

            // Tentukan nilai minimal topup & remarks
            $minTopup = 200000; // karena jenis_kol = Influencer
            $item->nilai_min_topup = $minTopup;
            $item->jumlah_top_up = $totalTopup;
            $item->remarks = $totalTopup >= $minTopup ? 'Eligible' : 'Not Eligible';

            return $item;
        });

        return DataTables::of($rekrut)
            ->addColumn('nilai_min_topup', function ($row) {
                return number_format($row->nilai_min_topup, 0, ',', '.');
            })
            ->addColumn('jumlah_top_up', function ($row) {
                return number_format($row->jumlah_top_up, 0, ',', '.');
            })
            ->addColumn('remarks', function ($row) {
                return $row->remarks;
            })
            ->make(true);
    }

    public function getAreaMarcom(Request $request)
    {
        $bulanIni = now()->format('Y-m'); // e.g. 2025-09

        // Ambil statistik per area (unikkan kol dengan COUNT DISTINCT)
        $areas = DB::table('creator_partner as cp')
            ->leftJoin('rekruter_kol as rk', DB::raw('cp.referral_code COLLATE utf8mb4_unicode_ci'), '=', DB::raw('rk.referral_code COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('revenue_kol as rv', DB::raw('rk.email COLLATE utf8mb4_unicode_ci'), '=', DB::raw('rv.email COLLATE utf8mb4_unicode_ci'))
            ->select(
                'cp.area',
                DB::raw("COUNT(DISTINCT cp.id) as total_kol"),
                DB::raw("SUM(CASE WHEN cp.jenis_kol = 'KOL as a Buzzer' THEN 1 ELSE 0 END) as jumlah_buzzer"),
                DB::raw("SUM(CASE WHEN cp.jenis_kol = 'KOL as a Seller Online/Afiliate' THEN 1 ELSE 0 END) as jumlah_influencer"),
                DB::raw("COUNT(DISTINCT rk.id) as total_rekruter"),
                DB::raw("COALESCE(SUM(rv.jumlah_top_up), 0) as total_topup"),
                // max topup dari revenue_kol yang terjadi pada bulan ini (untuk rule 3)
                DB::raw("MAX(CASE WHEN DATE_FORMAT(rv.created_at, '%Y-%m') = '{$bulanIni}' THEN rv.jumlah_top_up ELSE 0 END) as max_topup_bulan_ini")
            )
            ->groupBy('cp.area')
            ->orderBy('cp.area', 'asc')
            ->get();

        // Untuk setiap area: hitung bintang sesuai aturan:
        // 1) ada KOL -> 1 bintang
        // 2) ada minimal 1 creator di area yang punya >=5 akun rekruter eligible (eligible = akun punya total_topup >= minTopup tergantung jenis_kol)
        // 3) ada akun yang topup >= 1.000.000 pada bulan ini -> 1 bintang
        foreach ($areas as $areaRow) {
            $bintang = 0;

            // Rule 2: cek apakah ada creator di area yang sudah mencapai Tier >= Bronze
            // (Tier Bronze: ada >=5 rekruter dengan total_topup >= minTopup; minTopup berbeda per jenis_kol)
            $adaTierBronze = false;

            // Ambil semua creator di area (referral_code + jenis_kol)
            $creators = DB::table('creator_partner')
                ->where('area', $areaRow->area)
                ->select('referral_code', 'jenis_kol')
                ->get();

            foreach ($creators as $creator) {
                if (empty($creator->referral_code)) continue;

                // ambil list email rekruter untuk referral ini
                $emails = DB::table('rekruter_kol')
                    ->where('referral_code', $creator->referral_code)
                    ->pluck('email')
                    ->filter() // hapus null/empty
                    ->unique()
                    ->values()
                    ->toArray();

                if (empty($emails)) continue;

                // set minimum per jenis_kol
                $minTopup = $creator->jenis_kol === 'KOL as a Buzzer' ? 250000 : 200000;

                // ambil total topup per email (all-time)
                $topupPerEmail = DB::table('revenue_kol')
                    ->whereIn('email', $emails)
                    ->select('email', DB::raw('SUM(jumlah_top_up) as total'))
                    ->groupBy('email')
                    ->pluck('total')
                    ->toArray();

                // hitung berapa email yang memenuhi minTopup
                $eligibleCount = collect($topupPerEmail)->filter(fn($tot) => $tot >= $minTopup)->count();

                if ($eligibleCount >= 5) {
                    $adaTierBronze = true;
                    break; // cukup ada 1 creator yang memenuhi -> area dapat bintang tambahan
                }
            }

            if ($adaTierBronze) $bintang += 2;

            // Rule 3: ada akun rekruter yang topup >= 1jt pada bulan ini?
            if ((int)$areaRow->max_topup_bulan_ini >= 1000000) {
                $bintang++;
            }

            $areaRow->remarks = $bintang; // hanya kirim angka 0..3
        }

        return DataTables()->of($areas)
            ->addIndexColumn()
            ->make(true);
    }


    public function getSimpatiTiktok(Request $request)
    {
        $data = DB::table('summary_simpati_tiktok as sst')
            ->select(
                'sst.*'
            )
            ->orderBy('sst.created_at', 'desc')
            ->get();

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }

    public function getReferralChampionAm(Request $request)
    {
        $data = ReferralChampionAm::orderBy('created_at', 'desc')->get();

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }
    public function getSultamRacing(Request $request)
    {
        $data = SultamRacing::orderBy('created_at', 'desc')->get();

        return DataTables()->of($data)
            ->addIndexColumn()
            ->make(true);
    }
    public function getVoucherStats()
    {
        // Menghitung total semua voucher
        $totalVoucher = DB::table('myads_voucher')->count();

        // Menghitung voucher yang sudah diklaim (user_id tidak null)
        $totalClaimed = DB::table('myads_voucher')->whereNotNull('user_id')->count();

        // Menghitung sisa voucher yang belum diklaim
        $totalNotClaimed = $totalVoucher - $totalClaimed;

        $data = [
            'total_voucher'   => $totalVoucher,
            'total_claimed'   => $totalClaimed,
            'total_not_claim' => $totalNotClaimed,
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getVouchers(Request $request)
    {
        // Mengecek apakah ini adalah request AJAX dari DataTables
        if ($request->ajax()) {
            $data = DB::table('myads_voucher')
                ->select('id', 'voucher', 'created_at', 'user_id')
                ->orderBy('created_at', 'desc'); // Mengurutkan dari yang terbaru

            return DataTables::of($data)
                ->addColumn('status_klaim', function ($row) {
                    // Logika untuk menampilkan status klaim
                    if ($row->user_id) {
                        return 'claimed'; // Kirim 'claimed' jika sudah diklaim
                    }
                    return 'not_claimed'; // Kirim 'not_claimed' jika belum
                })
                ->addColumn('aksi', function ($row) {
                    // Menambahkan tombol Edit dan Hapus dengan ikon di setiap baris
                    $btn = '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-primary btn-sm editVoucher"><i class="fas fa-edit"></i> Edit</a> ';
                    $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" class="btn btn-danger btn-sm hapusVoucher"><i class="fas fa-trash-alt"></i> Hapus</a>';
                    return $btn;
                })
                ->rawColumns(['aksi']) // Memberitahu DataTables bahwa kolom 'aksi' berisi HTML
                ->make(true);
        }
    }

    /**
     * Function 2: Menyimpan data voucher baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tambahVoucher(Request $request)
    {
        // Validasi input dari form
        $validator = Validator::make($request->all(), [
            'voucher' => 'required|string|max:255|unique:myads_voucher,voucher',
        ], [
            'voucher.required' => 'Kolom voucher wajib diisi.',
            'voucher.unique'   => 'Kode voucher ini sudah ada.',
        ]);

        // Jika validasi gagal, kembalikan pesan error
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Jika validasi berhasil, simpan data ke database
        DB::table('myads_voucher')->insert([
            'voucher'    => $request->voucher,
            // user_id bisa ditambahkan jika perlu, contoh: 'user_id' => auth()->id()
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['success' => 'Voucher berhasil ditambahkan.']);
    }

    /**
     * Function 3: Mengupdate data voucher yang sudah ada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVoucher(Request $request, $id)
    {
        // Validasi input, pastikan voucher unik tapi abaikan id saat ini
        $validator = Validator::make($request->all(), [
            'voucher' => 'required|string|max:255|unique:myads_voucher,voucher,' . $id,
        ], [
            'voucher.required' => 'Kolom voucher wajib diisi.',
            'voucher.unique'   => 'Kode voucher ini sudah digunakan oleh data lain.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cari voucher berdasarkan ID
        $voucher = DB::table('myads_voucher')->where('id', $id)->first();

        // Jika voucher tidak ditemukan
        if (!$voucher) {
            return response()->json(['error' => 'Data voucher tidak ditemukan.'], 404);
        }

        // Update data di database
        DB::table('myads_voucher')->where('id', $id)->update([
            'voucher'    => $request->voucher,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['success' => 'Voucher berhasil diperbarui.']);
    }

    /**
     * Function 4: Menghapus data voucher.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function hapusVoucher($id)
    {
        // Cari voucher berdasarkan ID
        $voucher = DB::table('myads_voucher')->where('id', $id)->first();

        // Jika voucher tidak ditemukan
        if (!$voucher) {
            return response()->json(['error' => 'Data voucher tidak ditemukan.'], 404);
        }

        // Hapus data dari database
        DB::table('myads_voucher')->where('id', $id)->delete();

        return response()->json(['success' => 'Voucher berhasil dihapus.']);
    }

    public function getClaimedVouchers(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('myads_voucher as mv')
                ->join('myads_user as mu', 'mv.user_id', '=', 'mu.id')
                ->whereNotNull('mv.user_id') // Hanya tampilkan voucher yang sudah ada user_id-nya
                ->select(
                    'mu.id as user_id', // ID dari tabel user untuk proses update
                    'mv.id as voucher_id', // ID dari tabel voucher untuk proses 'unclaim'
                    'mu.created_at as tanggal_daftar',
                    'mu.nama',
                    'mu.usaha',
                    'mu.email',
                    'mu.nomor_hp',
                    'mv.voucher as kode_voucher'
                );

            return DataTables::of($data)
                ->addColumn('aksi', function ($row) {
                    // Tombol Edit merujuk pada user_id, Tombol Hapus merujuk pada voucher_id
                    $btn = '<a href="javascript:void(0)" data-user-id="' . $row->user_id . '" class="btn btn-primary btn-sm editUser"><i class="fas fa-edit"></i> Edit User</a> ';
                    $btn .= '<a href="javascript:void(0)" data-voucher-id="' . $row->voucher_id . '" class="btn btn-warning btn-sm unclaimVoucher"><i class="fas fa-unlink"></i> Lepas Klaim</a>';
                    return $btn;
                })
                ->rawColumns(['aksi'])
                ->make(true);
        }
    }

    /**
     * FUNGSI UPDATE: Mengupdate data user yang telah klaim voucher.
     */
    public function updateUser(Request $request, $user_id)
    {
        // Validasi input, pastikan email unik tapi abaikan email user saat ini
        $validator = Validator::make($request->all(), [
            'nama'      => 'required|string|max:191',
            'usaha'     => 'nullable|string|max:191',
            'email'     => 'required|email|max:191|unique:myads_user,email,' . $user_id,
            'nomor_hp'  => 'nullable|string|max:32',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique'  => 'Email ini sudah digunakan oleh user lain.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cari user berdasarkan ID
        $user = DB::table('myads_user')->where('id', $user_id)->first();
        if (!$user) {
            return response()->json(['error' => 'Data user tidak ditemukan.'], 404);
        }

        // Update data di tabel myads_user
        DB::table('myads_user')->where('id', $user_id)->update([
            'nama'       => $request->nama,
            'usaha'      => $request->usaha,
            'email'      => $request->email,
            'nomor_hp'   => $request->nomor_hp,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['success' => 'Data user berhasil diperbarui.']);
    }

    /**
     * FUNGSI DELETE: Melepaskan klaim voucher dari user (user_id di-set NULL).
     */
    public function unclaimVoucher($voucher_id)
    {
        // Cari voucher berdasarkan ID-nya
        $voucher = DB::table('myads_voucher')->where('id', $voucher_id)->first();

        if (!$voucher) {
            return response()->json(['error' => 'Data voucher tidak ditemukan.'], 404);
        }

        // Set kolom user_id menjadi NULL
        DB::table('myads_voucher')->where('id', $voucher_id)->update([
            'user_id' => null,
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['success' => 'Klaim voucher berhasil dilepaskan.']);
    }
    public function downloadVouchers()
    {
        // 1. Tentukan nama file
        $fileName = 'claimed_vouchers_' . Carbon::now()->format('Y-m-d') . '.csv';

        // 2. Tentukan header untuk file CSV
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // 3. Kolom yang akan ada di file CSV
        $columns = ['Tanggal Daftar', 'Nama', 'Usaha', 'Email', 'Nomor HP', 'Kode Voucher'];

        // 4. Buat callback untuk streaming data
        $callback = function () use ($columns) {
            // Buka output stream
            $file = fopen('php://output', 'w');

            // Tulis baris header ke file CSV dengan delimiter ~
            fputcsv($file, $columns, '~');

            // Ambil data dari database
            $data = DB::table('myads_voucher as mv')
                ->join('myads_user as mu', 'mv.user_id', '=', 'mu.id')
                ->whereNotNull('mv.user_id')
                ->select(
                    'mu.created_at',
                    'mu.nama',
                    'mu.usaha',
                    'mu.email',
                    'mu.nomor_hp',
                    'mv.voucher'
                )
                ->orderBy('mu.created_at', 'desc')
                ->get();

            // Tulis setiap baris data ke file CSV
            foreach ($data as $row) {
                $rowData = [
                    Carbon::parse($row->created_at)->format('Y-m-d H:i:s'),
                    $row->nama,
                    $row->usaha,
                    $row->email,
                    $row->nomor_hp,
                    $row->voucher,
                ];
                fputcsv($file, $rowData, '~');
            }

            // Tutup output stream
            fclose($file);
        };

        // 5. Kembalikan response sebagai file download
        return response()->stream($callback, 200, $headers);
    }

    public function getLogLogin(Request $request)
    {
        $query = DB::table('loglogin')
            ->select([
                'loglogin.id',
                'loglogin.user_id',
                'loglogin.tgl',
                'loglogin.nama',
                'loglogin.role',
                'loglogin.email',
                'loglogin.created_at',
                'loglogin.updated_at',
            ])
            ->orderBy('loglogin.tgl', 'desc')
            ->orderBy('loglogin.updated_at', 'desc');

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('loglogin.tgl', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->where('loglogin.tgl', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->where('loglogin.tgl', '<=', $request->end_date);
        }

        // Filter by role
        if ($request->filled('role') && $request->role != 'all') {
            $query->where('loglogin.role', $request->role);
        }

        return DataTables::of($query)
            ->editColumn('tgl', function ($row) {
                return \Carbon\Carbon::parse($row->tgl)->format('d-m-Y');
            })
            ->editColumn('updated_at', function ($row) {
                return \Carbon\Carbon::parse($row->updated_at)->format('d-m-Y H:i:s');
            })
            ->addColumn('action', function ($row) {
                return '<span class="badge badge-success">Logged</span>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**     * Export PowerHouse Voucher Report to Excel
     */
    public function exportPowerHouseVoucher()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        // Voucher codes untuk PowerHouse
        $powerHouseCodes = ['SUPER1', 'SUPER2', 'SUPER3', 'SUPER4', 'SUPER5', 'SUPER6', 'SUPER7', 'SUPER8'];
        
        // Query dengan JOIN untuk mendapatkan data lengkap
        $data = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'rb.email_client',
                'rb.company_name',
                'dv.voucher_code',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as amount'),
                DB::raw('CAST(rb.discount_voucer AS DECIMAL(15,2)) as discount'),
                DB::raw('CAST(rb.total_settlement_klien AS DECIMAL(15,2)) as total'),
                'rb.payment_method_name',
                'rb.paid_date'
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $powerHouseCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$currentMonth])
            ->orderBy('dv.voucher_code')
            ->get();
        
        // Mapping untuk PowerHouse names
        $powerHouseMapping = [
            'SUPER1' => 'Angga Satria Gusti',
            'SUPER2' => 'Abdul Halim',
            'SUPER3' => 'Raden Agie S. Akbar',
            'SUPER4' => 'Sony Widjaya',
            'SUPER5' => 'Deni Setiawan',
            'SUPER6' => 'Muhammad Arief Syahbana',
            'SUPER7' => 'Naqsyabandi',
            'SUPER8' => 'Ikrar Dharmawan',
        ];
        
        // Transform data untuk Excel
        $exportData = $data->map(function ($item) use ($powerHouseMapping) {
            return [
                'Email' => $item->email_client,
                'Perusahaan' => $item->company_name,
                'Voucher Code' => $item->voucher_code,
                'PowerHouse' => $powerHouseMapping[$item->voucher_code] ?? '-',
                'Amount' => $item->amount,
                'Discount' => $item->discount,
                'Total Settlement' => $item->total,
                'Payment Method' => $item->payment_method_name,
                'Tanggal Pembayaran' => Carbon::parse($item->paid_date)->format('d-m-Y H:i:s')
            ];
        });
        
        // Create Excel file
        $fileName = 'PowerHouse_Referral_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        
        return response()->streamDownload(function () use ($exportData) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set header
            $headers = ['Email', 'Perusahaan', 'Voucher Code', 'PowerHouse', 'Amount', 'Discount', 'Total Settlement', 'Payment Method', 'Tanggal Pembayaran'];
            $sheet->fromArray($headers, null, 'A1');
            
            // Style header
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '667EEA']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($exportData as $item) {
                $sheet->fromArray((array)$item, null, 'A' . $row);
                $row++;
            }
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(25);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(18);
            $sheet->getColumnDimension('H')->setWidth(20);
            $sheet->getColumnDimension('I')->setWidth(20);
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }
    
    /**
     * Export Canvasser Voucher Report to Excel
     */
    public function exportCanvasserVoucher()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        // Voucher codes untuk Canvasser
        $canvasserCodes = ['EXTRA1', 'EXTRA2', 'EXTRA3', 'EXTRA4', 'EXTRA5', 'EXTRA6', 'EXTRA7', 'EXTRA8', 'EXTRA9', 'EXTRA10', 'EXTRA11', 'EXTRA12', 'EXTRA13'];
        
        // Query dengan JOIN (per akun, bukan aggregate)
        $data = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'rb.email_client',
                'rb.company_name',
                'dv.voucher_code',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as amount'),
                'rb.payment_method_name',
                'rb.paid_date'
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $canvasserCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$currentMonth])
            ->orderBy('dv.voucher_code')
            ->orderBy('rb.email_client')
            ->get();
        
        // Mapping untuk Canvasser names
        $canvasserMapping = [
            'EXTRA1' => 'Amanah',
            'EXTRA2' => 'Indah',
            'EXTRA3' => 'Maria',
            'EXTRA4' => 'Meisya',
            'EXTRA5' => 'Hardi',
            'EXTRA6' => 'Bustomi',
            'EXTRA7' => 'Intan',
            'EXTRA8' => 'Hika Rochmah',
            'EXTRA9' => 'Akbar Zikron',
            'EXTRA10' => 'Riva',
            'EXTRA11' => 'Fanni',
            'EXTRA12' => 'Maiph',
            'EXTRA13' => 'Rizky'
        ];
        
        // Transform data untuk Excel (per akun dengan insentif per akun)
        $exportData = $data->map(function ($item) use ($canvasserMapping) {
            $totalAmount = (float)$item->amount;
            // Insentif per akun: jika total > 500K, dapat 100K
            $insentif = $totalAmount > 500000 ? 100000 : 0;
            
            return [
                'Email' => $item->email_client,
                'Perusahaan' => $item->company_name,
                'Voucher Code' => $item->voucher_code,
                'Canvasser' => $canvasserMapping[$item->voucher_code] ?? '-',
                'Total Top Up' => $totalAmount,
                'Insentif' => $insentif,
                'Payment Method' => $item->payment_method_name,
                'Tanggal Pembayaran' => Carbon::parse($item->paid_date)->format('d-m-Y H:i:s')
            ];
        });
        
        // Create Excel file
        $fileName = 'Canvasser_Referral_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        
        return response()->streamDownload(function () use ($exportData) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set header
            $headers = ['Email', 'Perusahaan', 'Voucher Code', 'Canvasser', 'Total Top Up', 'Insentif', 'Payment Method', 'Tanggal Pembayaran'];
            $sheet->fromArray($headers, null, 'A1');
            
            // Style header
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '667EEA']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];
            $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($exportData as $item) {
                $sheet->fromArray((array)$item, null, 'A' . $row);
                $row++;
            }
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(18);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(20);
            $sheet->getColumnDimension('H')->setWidth(20);
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }

    /**
     * Get PowerHouse Referral Report
     * Data dari JOIN report_balance_top_up + data_voucher dengan sistem POIN
     * 1 Poin = 1 juta rupiah
     * Added: Jumlah Leads dan Jumlah Visit dari LeadsMaster dan Booking
     */
    public function getPowerHouseVoucher(Request $request)
    {
        // Get month from request (format Y-m-d) or use current month
        $monthParam = $request->get('month', Carbon::now()->format('Y-m-d'));
        // Extract only Y-m from the date parameter
        $month = Carbon::parse($monthParam)->format('Y-m');
        
        // Voucher codes untuk PowerHouse
        $powerHouseCodes = ['SUPER1', 'SUPER2', 'SUPER3', 'SUPER4', 'SUPER5', 'SUPER6', 'SUPER7', 'SUPER8'];
        
        // Mapping voucher code ke nama PowerHouse
        $powerHouseMapping = [
            'SUPER1' => 'Angga Satria Gusti',
            'SUPER2' => 'Abdul Halim',
            'SUPER3' => 'Raden Agie S. Akbar',
            'SUPER4' => 'Sony Widjaya',
            'SUPER5' => 'Deni Setiawan',
            'SUPER6' => 'Muhammad Arief Syahbana',
            'SUPER7' => 'Naqsyabandi',
            'SUPER8' => 'Ikrar Dharmawan',
        ];

        // Get users associated with PowerHouse teams
        $powerHouseUserMap = [
            'Angga Satria Gusti' => ['Angga Satria Gusti'],
            'Abdul Halim' => ['Abdul Halim'],
            'Raden Agie S. Akbar' => ['Raden Agie Satria Akbar', 'Raden Agie S. Akbar'],
            'Sony Widjaya' => ['Sony Widjaya'],
            'Deni Setiawan' => ['Deni Setiawan'],
            'Muhammad Arief Syahbana' => ['Muhammad Arief Syahbana', 'Muhammad Arief'],
            'Naqsyabandi' => ['Naqsyabandi'],
            'Ikrar Dharmawan' => ['Ikrar Dharmawan'],
        ];

        // Ambil data dari JOIN report_balance_top_up + data_voucher
        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                DB::raw('UPPER(dv.voucher_code) as voucher_code'),
                DB::raw('COUNT(*) as jumlah_akun'),
                DB::raw('SUM(CAST(rb.amount AS DECIMAL(15,2))) as total_topup'),
                DB::raw('MAX(rb.tgl_transaksi) as tgl_transaksi_terakhir')
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $powerHouseCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$month])
            ->groupBy('dv.voucher_code')
            ->get()
            ->keyBy('voucher_code');

        // Build result dari mapping
        $result = [];
        foreach ($powerHouseMapping as $voucherCode => $powerHouseName) {
            $voucherInfo = $voucherData->get($voucherCode);
            
            // Count leads for this PowerHouse team (from leads_master table)
            $userNames = $powerHouseUserMap[$powerHouseName] ?? [];
            
            // Get user IDs for this PowerHouse
            $userIds = DB::table('users')
                ->whereIn('name', $userNames)
                ->pluck('id')
                ->toArray();

            // Count new leads created by this user in the selected month
            $jumlahLeads = 0;
            if (!empty($userIds)) {
                $jumlahLeads = DB::table('leads_master')
                    ->whereIn('user_id', $userIds)
                    ->whereRaw('DATE_FORMAT(created_at, "%Y-%m") = ?', [$month])
                    ->count();
            }

            // Count visits from bookings for this user in the selected month
            $jumlahVisit = 0;
            if (!empty($userNames)) {
                $jumlahVisit = DB::table('bookings')
                    ->whereIn('nama', $userNames)
                    ->whereRaw('DATE_FORMAT(tanggal, "%Y-%m") = ?', [$month])
                    ->count();
            }
            
            if ($voucherInfo) {
                $totalTopup = (float)($voucherInfo->total_topup ?? 0);
                // Hitung POIN: 1 Poin = 1 juta rupiah
                $poin = floor($totalTopup / 1000000);

                $tglFormatted = $voucherInfo->tgl_transaksi_terakhir ? 
                    Carbon::parse($voucherInfo->tgl_transaksi_terakhir)->format('d M Y') : '-';
                    
                $result[] = [
                    'referral_code' => $voucherCode,
                    'team_powerhouse' => $powerHouseName,
                    'jumlah_akun' => (int)($voucherInfo->jumlah_akun ?? 0),
                    'jumlah_leads' => $jumlahLeads,
                    'jumlah_visit' => $jumlahVisit,
                    'total_topup' => $totalTopup,
                    'poin' => $poin,
                    'tgl_transaksi_terakhir' => $tglFormatted,
                ];
            } else {
                // Voucher tanpa data
                $result[] = [
                    'referral_code' => $voucherCode,
                    'team_powerhouse' => $powerHouseName,
                    'jumlah_akun' => 0,
                    'jumlah_leads' => $jumlahLeads,
                    'jumlah_visit' => $jumlahVisit,
                    'total_topup' => 0,
                    'poin' => 0,
                    'tgl_transaksi_terakhir' => '-',
                ];
            }
        }

        return DataTables::of($result)
            ->addIndexColumn()
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('poin', function ($row) {
                return (int)$row['poin'];
            })
            ->make(true);
    }

    /**
     * Get Canvasser Voucher Report
     * Data dari JOIN report_balance_top_up + data_voucher
     */
    public function getCanvasserVoucher(Request $request)
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        // Voucher codes untuk Canvasser
        $canvasserCodes = ['EXTRA1', 'EXTRA2', 'EXTRA3', 'EXTRA4', 'EXTRA5', 'EXTRA6', 'EXTRA7', 'EXTRA8', 'EXTRA9', 'EXTRA10', 'EXTRA11', 'EXTRA12', 'EXTRA13'];
        
        // Mapping voucher code ke nama canvasser
        $canvasserMapping = [
            'EXTRA1' => 'Amanah',
            'EXTRA2' => 'Indah',
            'EXTRA3' => 'Maria',
            'EXTRA4' => 'Meisya',
            'EXTRA5' => 'Hardi',
            'EXTRA6' => 'Bustomi',
            'EXTRA7' => 'Intan',
            'EXTRA8' => 'Hika Rochmah',
            'EXTRA9' => 'Akbar Zikron',
            'EXTRA10' => 'Riva',
            'EXTRA11' => 'Fanni',
            'EXTRA12' => 'Maiph',
            'EXTRA13' => 'Rizky',
        ];

        // Ambil data dari JOIN report_balance_top_up + data_voucher (PER AKUN, bukan di-aggregate)
        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'rb.email_client',
                'rb.company_name',
                'dv.voucher_code',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as total_topup'),
                'rb.tgl_transaksi',
                'rb.paid_date'
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $canvasserCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$currentMonth])
            ->orderBy('dv.voucher_code')
            ->orderBy('rb.email_client')
            ->get();

        // Build result per akun (per email_client)
        $result = [];
        foreach ($voucherData as $data) {
            $totalTopup = (float)$data->total_topup;
            // Insentif per akun: jika total top-up >= 500K, dapat 100K
            $insentif = $totalTopup >= 500000 ? 100000 : 0;

            $tglFormatted = $data->paid_date ? 
                Carbon::parse($data->paid_date)->format('d M Y') : '-';
                
            $result[] = [
                'referral_code' => $data->voucher_code,
                'canvasser' => $canvasserMapping[$data->voucher_code] ?? $data->voucher_code,
                'email_client' => $data->email_client,
                'company_name' => $data->company_name,
                'total_topup' => $totalTopup,
                'insentif' => $insentif,
                'tgl_transaksi_terakhir' => $tglFormatted,
            ];
        }

        return DataTables::of($result)
            ->addIndexColumn()
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('insentif', function ($row) {
                return $row['insentif'] > 0 ? 'Rp ' . number_format($row['insentif'], 0, ',', '.') : '-';
            })
            ->make(true);
    }

    /**
     * Get Canvasser Voucher Summary
     * Summary per canvasser (grouped by voucher code)
     * Total client count, total topup, total insentif
     */
    public function getCanvasserVoucherSummary(Request $request)
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        // Voucher codes untuk Canvasser
        $canvasserCodes = ['EXTRA1', 'EXTRA2', 'EXTRA3', 'EXTRA4', 'EXTRA5', 'EXTRA6', 'EXTRA7', 'EXTRA8', 'EXTRA9', 'EXTRA10', 'EXTRA11', 'EXTRA12', 'EXTRA13'];
        
        // Mapping voucher code ke nama canvasser
        $canvasserMapping = [
            'EXTRA1' => 'Amanah',
            'EXTRA2' => 'Indah',
            'EXTRA3' => 'Maria',
            'EXTRA4' => 'Meisya',
            'EXTRA5' => 'Hardi',
            'EXTRA6' => 'Bustomi',
            'EXTRA7' => 'Intan',
            'EXTRA8' => 'Hika Rochmah',
            'EXTRA9' => 'Akbar Zikron',
            'EXTRA10' => 'Riva',
            'EXTRA11' => 'Fanni',
            'EXTRA12' => 'Maiph',
            'EXTRA13' => 'Rizky',
        ];

        // Ambil data dari JOIN report_balance_top_up + data_voucher
        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'dv.voucher_code',
                'rb.email_client',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as total_topup'),
                'rb.paid_date'
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $canvasserCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$currentMonth])
            ->orderBy('dv.voucher_code')
            ->orderBy('rb.email_client')
            ->get();

        // Group by voucher code and calculate summary
        $summaryData = [];
        $grouped = $voucherData->groupBy('voucher_code');
        
        foreach ($canvasserCodes as $voucherCode) {

            $items = $grouped[$voucherCode] ?? collect();

            $totalTopup = 0;
            $totalInsentif = 0;
            $totalClient = 0;

            foreach ($items as $item) {
                $amount = (float) $item->total_topup;
                $totalTopup += $amount;
                $totalClient++;

                if ($amount >= 500000) {
                    $totalInsentif += 100000;
                }
            }

            $summaryData[] = [
                'referral_code' => $voucherCode,
                'canvasser' => $canvasserMapping[$voucherCode] ?? $voucherCode,
                'total_client' => $totalClient,
                'total_topup' => $totalTopup,
                'total_insentif' => $totalInsentif,
            ];
        }

        return DataTables::of($summaryData)
            ->addIndexColumn()
            ->editColumn('total_topup', function ($row) {
                return 'Rp ' . number_format($row['total_topup'], 0, ',', '.');
            })
            ->editColumn('total_insentif', function ($row) {
                return 'Rp ' . number_format($row['total_insentif'], 0, ',', '.');
            })
            ->make(true);
    }

    /**
     * Export Canvasser Voucher Summary to Excel
     */
    public function exportCanvasserVoucherSummary()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        
        // Voucher codes untuk Canvasser
        $canvasserCodes = ['EXTRA1', 'EXTRA2', 'EXTRA3', 'EXTRA4', 'EXTRA5', 'EXTRA6', 'EXTRA7', 'EXTRA8', 'EXTRA9', 'EXTRA10', 'EXTRA11', 'EXTRA12', 'EXTRA13'];
        
        // Mapping voucher code ke nama canvasser
        $canvasserMapping = [
            'EXTRA1' => 'Amanah',
            'EXTRA2' => 'Indah',
            'EXTRA3' => 'Maria',
            'EXTRA4' => 'Meisya',
            'EXTRA5' => 'Hardi',
            'EXTRA6' => 'Bustomi',
            'EXTRA7' => 'Intan',
            'EXTRA8' => 'Hika Rochmah',
            'EXTRA9' => 'Akbar Zikron',
            'EXTRA10' => 'Riva',
            'EXTRA11' => 'Fanni',
            'EXTRA12' => 'Maiph',
            'EXTRA13' => 'Rizky',
        ];

        // Ambil data dari JOIN report_balance_top_up + data_voucher
        $voucherData = DB::table('report_balance_top_up as rb')
            ->join('data_voucher as dv', 'rb.no_invoice', '=', 'dv.id_transaksi')
            ->select(
                'dv.voucher_code',
                'rb.email_client',
                DB::raw('CAST(rb.amount AS DECIMAL(15,2)) as total_topup')
            )
            ->whereIn(DB::raw('UPPER(dv.voucher_code)'), $canvasserCodes)
            ->whereRaw('DATE_FORMAT(rb.paid_date, "%Y-%m") = ?', [$currentMonth])
            ->orderBy('dv.voucher_code')
            ->orderBy('rb.email_client')
            ->get();

        // Group by voucher code and calculate summary
        $summaryData = [];
        $grouped = $voucherData->groupBy('voucher_code');
        
        foreach ($grouped as $voucherCode => $items) {
            $totalTopup = 0;
            $totalInsentif = 0;
            $emailClients = [];
            
            foreach ($items as $item) {
                $amount = (float)$item->total_topup;
                $totalTopup += $amount;
                
                // Insentif per akun: jika total top-up >= 500K, dapat 100K
                if ($amount >= 500000) {
                    $totalInsentif += 100000;
                }
                
                // Collect unique email clients
                if (!in_array($item->email_client, $emailClients)) {
                    $emailClients[] = $item->email_client;
                }
            }
            
            $summaryData[] = [
                'referral_code' => $voucherCode,
                'canvasser' => $canvasserMapping[$voucherCode] ?? $voucherCode,
                'total_client' => count($emailClients),
                'total_topup' => $totalTopup,
                'total_insentif' => $totalInsentif,
            ];
        }

        // Create Excel file
        $fileName = 'Canvasser_Summary_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        
        return response()->streamDownload(function () use ($summaryData) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set header
            $headers = ['Referral Code', 'Nama Canvasser', 'Total Client', 'Total Top Up', 'Total Insentif'];
            $sheet->fromArray($headers, null, 'A1');
            
            // Style header
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '36B9CC']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
            ];
            $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
            
            // Add data
            $row = 2;
            foreach ($summaryData as $item) {
                $sheet->setCellValue('A' . $row, $item['referral_code']);
                $sheet->setCellValue('B' . $row, $item['canvasser']);
                $sheet->setCellValue('C' . $row, $item['total_client']);
                $sheet->setCellValue('D' . $row, $item['total_topup']);
                $sheet->setCellValue('E' . $row, $item['total_insentif']);
                $row++;
            }
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(20);
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }

    /**
     * Export Daily TopUp Report to Excel
     */
    public function exportDailyTopup()
    {
        try {
            // Get current month from request or use current month
            $monthFilter = request()->get('month');
            $leadProgramController = new LeadProgramController();
            $data = $leadProgramController->getDailyTopupData($monthFilter);

            if (empty($data)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk diekspor');
            }

            // Prepare export data
            $exportData = [];
            foreach ($data as $row) {
                $exportData[] = [
                    'Tanggal' => $row['date'],
                    'Mitra SBP (Settlement)' => $row['mitra_sbp_settle'],
                    'Mitra SBP (User)' => $row['mitra_sbp_user'],
                    'Internal (Settlement)' => $row['internal_settle'],
                    'Internal (User)' => $row['internal_user'],
                    'Canvasser (Settlement)' => $row['canvasser_settle'],
                    'Canvasser (User)' => $row['canvasser_user'],
                    'Self Service (Settlement)' => $row['self_service_settle'],
                    'Self Service (User)' => $row['self_service_user'],
                    'Agency (Settlement)' => $row['agency_settle'],
                    'Agency (User)' => $row['agency_user'],
                    'Total (Settlement)' => $row['total'],
                    'Total (User)' => $row['total_user'],
                ];
            }

            // Create Excel file
            $fileName = 'Daily_TopUp_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

            return response()->streamDownload(function () use ($exportData) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // Set header
                $headers = ['Tanggal', 'Mitra SBP (Settlement)', 'Mitra SBP (User)', 'Internal (Settlement)', 'Internal (User)', 
                            'Canvasser (Settlement)', 'Canvasser (User)', 'Self Service (Settlement)', 'Self Service (User)', 
                            'Agency (Settlement)', 'Agency (User)', 'Total (Settlement)', 'Total (User)'];
                $sheet->fromArray($headers, null, 'A1');

                // Style header
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '667EEA']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                ];
                $sheet->getStyle('A1:M1')->applyFromArray($headerStyle);

                // Add data
                $row = 2;
                foreach ($exportData as $item) {
                    $sheet->fromArray((array)$item, null, 'A' . $row);
                    $row++;
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(18);
                $sheet->getColumnDimension('B')->setWidth(18);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(18);
                $sheet->getColumnDimension('K')->setWidth(15);
                $sheet->getColumnDimension('L')->setWidth(18);
                $sheet->getColumnDimension('M')->setWidth(15);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error("Error in exportDailyTopup: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }

    public function exportRegional()
    {
        try {
            $monthFilter = request()->get('month');
            $leadProgramController = new LeadProgramController();
            $data = $leadProgramController->getRegionalData($monthFilter);

            if (empty($data)) {
                return redirect()->back()->with('error', 'Tidak ada data untuk diekspor');
            }

            // Create Excel file
            $fileName = 'Regional_Report_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';

            return response()->streamDownload(function () use ($data) {
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // Set header
                $headers = [
                    'No', 'Canvaser Name', 'Leads', 'Existing Akun', 'New Akun', 
                    'Top Up Existing Akun Count', 'Top Up New Akun (Rp)', 'Top Up Existing Akun (Rp)', 
                    'Total Top Up (Rp)', 'Target (Rp)', 'Achievement (%)', 'Gap (Rp)', 
                    'Gap Daily (Rp)', 'MOM (1-Current Date Prev)', 'MOM (1-Current Date Current)',
                    'MOM (Remaining Prev)', 'MOM Gap (Rp)'
                ];
                $sheet->fromArray($headers, null, 'A1');

                // Style header
                $headerStyle = [
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F54D9']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
                ];
                $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);

                // Add data
                $row = 2;
                $no = 1;
                foreach ($data as $item) {
                    $sheet->setCellValue('A' . $row, $no);
                    $sheet->setCellValue('B' . $row, $item['canvaser_name']);
                    $sheet->setCellValue('C' . $row, $item['leads']);
                    $sheet->setCellValue('D' . $row, $item['existing_akun']);
                    $sheet->setCellValue('E' . $row, $item['new_akun']);
                    $sheet->setCellValue('F' . $row, $item['top_up_existing_akun_count']);
                    $sheet->setCellValue('G' . $row, $item['top_up_new_akun_rp']);
                    $sheet->setCellValue('H' . $row, $item['top_up_existing_akun_rp']);
                    $sheet->setCellValue('I' . $row, $item['total_top_up_rp']);
                    $sheet->setCellValue('J' . $row, $item['target']);
                    $sheet->setCellValue('K' . $row, $item['achievement_percent']);
                    $sheet->setCellValue('L' . $row, $item['gap']);
                    $sheet->setCellValue('M' . $row, $item['gap_daily']);
                    $sheet->setCellValue('N' . $row, $item['mom_prev_partial']);
                    $sheet->setCellValue('O' . $row, $item['mom_current_partial']);
                    $sheet->setCellValue('P' . $row, $item['mom_prev_remaining']);
                    $sheet->setCellValue('Q' . $row, $item['mom_gap']);
                    
                    // Add color for total rows
                    if ($item['is_total']) {
                        $styleTotal = [
                            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC107']],
                            'font' => ['bold' => true],
                        ];
                        $sheet->getStyle('A' . $row . ':Q' . $row)->applyFromArray($styleTotal);
                    }

                    $row++;
                    $no++;
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(12);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(18);
                $sheet->getColumnDimension('H')->setWidth(18);
                $sheet->getColumnDimension('I')->setWidth(18);
                $sheet->getColumnDimension('J')->setWidth(15);
                $sheet->getColumnDimension('K')->setWidth(15);
                $sheet->getColumnDimension('L')->setWidth(15);
                $sheet->getColumnDimension('M')->setWidth(15);
                $sheet->getColumnDimension('N')->setWidth(18);
                $sheet->getColumnDimension('O')->setWidth(18);
                $sheet->getColumnDimension('P')->setWidth(18);
                $sheet->getColumnDimension('Q')->setWidth(15);

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName);
        } catch (\Exception $e) {
            \Log::error("Error in exportRegional: " . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }
}
