<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPanenPoinV2;
use App\Models\User;
use App\Models\AkunPanenPoinV2;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PanenPoinV2Controller extends Controller
{
    // Tampilkan halaman input data
    public function index()
    {
        logUserLogin();
        return view('panenpoinv2.inputdatapoin');
    }
    
    // Tampilkan halaman list akun yang sudah terdaftar
    public function listAkun()
    {
        logUserLogin();
        return view('panenpoinv2.listakun');
    }
    
    // Get data akun untuk DataTable
    public function getAkunData(Request $request)
    {
        try {
            $query = AkunPanenPoinV2::query();
            
            // Filter berdasarkan role: kalau cvsr, hanya tampilkan akun dia sendiri
            if (Auth::user()->role === 'cvsr') {
                $query->where('user_id', Auth::id());
            }
            
            $query->select('id', 'nama_akun', 'email_client', 'user_id', 'created_at')
                ->with('user:id,name') // Join dengan user untuk nama canvasser
                ->orderBy('created_at', 'desc');
            
            $data = $query->get()->map(function($item) {
                // Ambil nomor HP dari user_panen_poin_v2 atau leads_master
                $nomorHp = DB::table('user_panen_poin_v2')
                    ->where('user_id', $item->user_id)
                    ->where('akun_myads_pelanggan', $item->email_client)
                    ->value('nomor_hp_pelanggan');
                
                if (!$nomorHp) {
                    $nomorHp = DB::table('leads_master')
                        ->where('user_id', $item->user_id)
                        ->where('email', $item->email_client)
                        ->value('mobile_phone');
                }
                
                return [
                    'id' => $item->id,
                    'nama_akun' => $item->nama_akun,
                    'email_client' => $item->email_client,
                    'nomor_hp' => $nomorHp ?? '-',
                    'nama_canvasser' => $item->user->name ?? '-',
                    'created_at' => $item->created_at->format('d M Y H:i'),
                    'created_at_raw' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });
            
            return datatables()->of(collect($data))
                ->make(true);
                
        } catch (\Exception $e) {
            \Log::error("Error in getAkunData: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Simpan data panen poin
    public function store(Request $request)
    {
        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'akun_myads_pelanggan' => 'required|max:255',
            'nomor_hp_pelanggan' => 'required|string|max:20',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Auto-create akun di akun_panen_poin_v2
            $emailClient = strtolower(trim($request->akun_myads_pelanggan));


            
            // Cek apakah akun sudah ada
            $existingAkun = AkunPanenPoinV2::where('user_id', Auth::id())
                ->where('email_client', $emailClient)
                ->first();
            
            $isNewAccount = false;
            
            if (!$existingAkun) {
                // Create akun baru
                $akun = AkunPanenPoinV2::create([
                    'user_id' => Auth::id(),
                    'nama_akun' => $request->nama_pelanggan,
                    'email_client' => $emailClient,
                    'password' => bcrypt('123456'), // Default password
                    'source' => 'user_panen_poin_v2',
                ]);
                
                \Log::info("Akun created for: {$emailClient}");
                $isNewAccount = true;
                
                // Simpan ke user_panen_poin_v2 hanya jika akun baru
                $panenPoin = UserPanenPoinV2::create([
                    'user_id' => Auth::id(),
                    'nama_pelanggan' => $request->nama_pelanggan,
                    'akun_myads_pelanggan' => strtolower($request->akun_myads_pelanggan),
                    'nomor_hp_pelanggan' => $request->nomor_hp_pelanggan,
                ]);
            } else {
                $akun = $existingAkun;
                \Log::info("Akun already exists for: {$emailClient}");
                $isNewAccount = false;
            }
            
            // Kirim notifikasi email & WhatsApp (untuk akun baru atau existing)
            try {
                $this->sendAccountNotification(
                    $akun,
                    $request->nomor_hp_pelanggan,
                    '123456' // Plain password untuk notifikasi
                );
            } catch (\Exception $e) {
                \Log::warning("Notification failed (not blocking transaction): " . $e->getMessage());
                // Jangan block transaksi - akun sudah dibuat, notifikasi bisa dicoba ulang
            }
            
            DB::commit();
            
            // Refresh summary panen poin untuk user ini (langsung setelah input)
            try {
                $this->refreshSummaryForSingleUser(Auth::id(), $emailClient);
                \Log::info("Summary refreshed immediately after input for email: {$emailClient}");
            } catch (\Exception $e) {
                \Log::warning("Failed to refresh summary immediately: " . $e->getMessage());
                // Tidak masalah, akan diupdate scheduler jam 7 pagi
            }
            
            // Tentukan pesan berdasarkan apakah akun baru atau existing
            if ($isNewAccount) {
                $successMessage = 'Data pelanggan berhasil disimpan dan akun telah dibuat!';
            } else {
                $successMessage = 'Akun sudah pernah dibuat, notifikasi telah dikirimkan ulang!';
            }
            
            return redirect()->route('panenpoinv2.index')
                ->with('success', $successMessage)
                ->with('is_existing_account', !$isNewAccount);
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error in store: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    // Tampilkan halaman report
    public function report()
    {
        logUserLogin();
        $months = $this->getPanenPoinV2ReportMonths();
        $prizes = DB::table('prizes_v2')
            ->where('stock', '>', 0)
            ->orderBy('point')
            ->get();

        return view('panenpoinv2.reportpoin', compact('months', 'prizes'));
    }

    // Tampilkan halaman report canvasser (ringkasan)
    public function reportCanvasser()
    {
        logUserLogin();
        $months = $this->getPanenPoinV2ReportMonths();

        return view('panenpoinv2.report-canvasser-panenpoinv2', compact('months'));
    }
    
    // Get data untuk DataTable
    public function getReportData(Request $request)
    {
        \Log::info('=== GET REPORT DATA CALLED ===');
        \Log::info('User: ' . Auth::user()->name);
        \Log::info('Request URI: ' . $request->getRequestUri());
        \Log::info('Filter Tanggal: ' . $request->tanggal);
        
        try {
            \Log::info('Starting calculatePanenPoinV2Data...');
            $result = $this->calculatePanenPoinV2Data($request->tanggal);
            
            return datatables()->of(collect($result))
                ->make(true);
                
        } catch (\Exception $e) {
            \Log::error("Error in getReportData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get data ringkasan per canvasser
    public function getReportCanvasserData(Request $request)
    {
        \Log::info('=== GET REPORT CANVASSER DATA CALLED ===');
        \Log::info('User: ' . Auth::user()->name);
        \Log::info('Request URI: ' . $request->getRequestUri());
        \Log::info('Filter Tanggal: ' . $request->tanggal);

        try {
            $query = $this->buildReportByRole('cvsr', $request->tanggal);

            return datatables()->of($query)->addIndexColumn()->make(true);

        } catch (\Exception $e) {
            \Log::error("Error in getReportCanvasserData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Tampilkan halaman report powerhouse
    public function reportPowerhouse()
    {
        logUserLogin();
        $months = $this->getPanenPoinV2ReportMonths();

        return view('panenpoinv2.report-ph-panenpoinv2', compact('months'));
    }

    private function getPanenPoinV2ReportMonths()
    {
        $startMonth = Carbon::create(2026, 5, 1);
        $endMonth = Carbon::create(2026, 6, 1);
        $currentMonth = Carbon::now()->startOfMonth();
        $selectedMonth = $currentMonth->betweenIncluded($startMonth, $endMonth)
            ? $currentMonth->format('Y-m-d')
            : $endMonth->format('Y-m-d');

        $months = [];
        $monthCursor = $startMonth->copy();

        while ($monthCursor->lte($endMonth)) {
            $months[] = [
                'value' => $monthCursor->format('Y-m-d'),
                'label' => $monthCursor->translatedFormat('F Y'),
                'selected' => $monthCursor->format('Y-m-d') === $selectedMonth,
            ];

            $monthCursor->addMonth();
        }

        return $months;
    }

    // Get data ringkasan per powerhouse
    public function getReportPowerhouseData(Request $request)
    {
        \Log::info('=== GET REPORT POWERHOUSE DATA CALLED ===');
        \Log::info('User: ' . Auth::user()->name);
        \Log::info('Request URI: ' . $request->getRequestUri());
        \Log::info('Filter Tanggal: ' . $request->tanggal);

        try {
            $query = $this->buildReportByRole('PH', $request->tanggal);

            return datatables()->of($query)->addIndexColumn()->make(true);

        } catch (\Exception $e) {
            \Log::error("Error in getReportPowerhouseData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Build report query by role (include zero)
    private function buildReportByRole(string $role, $tanggal = null)
    {
        $query = DB::table('users')
            ->select(
                'users.id as user_id',
                'users.name as nama_canvasser',
                DB::raw('COALESCE(COUNT(CASE WHEN akun_panen_poin_v2.email_client IS NOT NULL AND mitra_sbp.id IS NULL THEN summary_panen_poin_v2.id END), 0) as jumlah_terdaftar'),
                DB::raw('COALESCE(SUM(CASE WHEN akun_panen_poin_v2.email_client IS NOT NULL AND mitra_sbp.id IS NULL AND (COALESCE(summary_panen_poin_v2.poin, 0) > 0 OR COALESCE(summary_panen_poin_v2.poin_package, 0) > 0) THEN 1 ELSE 0 END), 0) as jumlah_akun_punya_poin'),
                DB::raw('COALESCE(SUM(CASE WHEN akun_panen_poin_v2.email_client IS NOT NULL AND mitra_sbp.id IS NULL THEN summary_panen_poin_v2.poin ELSE 0 END), 0) as jumlah_poin')
            )
            ->where('users.role', $role)
            ->leftJoin('summary_panen_poin_v2', function ($join) use ($tanggal) {
                $join->on('summary_panen_poin_v2.user_id', '=', 'users.id');

                if ($tanggal) {
                    $date = Carbon::parse($tanggal);
                    $join->whereMonth('summary_panen_poin_v2.created_at', $date->month)
                         ->whereYear('summary_panen_poin_v2.created_at', $date->year);
                }
            })
            ->leftJoin('akun_panen_poin_v2', 'summary_panen_poin_v2.email_client', '=', 'akun_panen_poin_v2.email_client')
            ->leftJoin('mitra_sbp', 'summary_panen_poin_v2.email_client', '=', 'mitra_sbp.email_myads')
            ->groupBy('users.id', 'users.name')
            ->orderByRaw('COALESCE(SUM(CASE WHEN akun_panen_poin_v2.email_client IS NOT NULL AND mitra_sbp.id IS NULL THEN summary_panen_poin_v2.poin ELSE 0 END), 0) DESC');

        // Filter berdasarkan role: kalau cvsr/PH, hanya tampilkan data dia sendiri
        if (Auth::user()->role === $role) {
            $query->where('users.id', Auth::id());
            \Log::info("Filtering by User ID: " . Auth::id() . " (Role {$role})");
        }

        return $query;
    }
    
    // Hitung data panen poin (ambil dari summary table)
    private function calculatePanenPoinV2Data($tanggal = null)
    {
        try {
            
            $query = DB::table('summary_panen_poin_v2')
                ->select(
                    'summary_panen_poin_v2.nama_canvasser',
                    'summary_panen_poin_v2.email_client',
                    'akun_panen_poin_v2.id as akun_id',
                    'summary_panen_poin_v2.nomor_hp_client',
                    'summary_panen_poin_v2.source',
                    DB::raw('CAST(summary_panen_poin_v2.total_settlement AS DECIMAL(15,2)) as total_settlement_raw'),
                    'summary_panen_poin_v2.poin_bulan_ini',
                    'summary_panen_poin_v2.poin_akumulasi',
                    'summary_panen_poin_v2.poin',
                    'summary_panen_poin_v2.poin_package',
                    DB::raw('COALESCE(summary_panen_poin_v2.poin_redeem, 0) as poin_redeem'),
                    DB::raw('(summary_panen_poin_v2.poin - COALESCE(summary_panen_poin_v2.poin_redeem, 0) + COALESCE(summary_panen_poin_v2.poin_package, 0)) as poin_sisa'),
                    'summary_panen_poin_v2.remark',
                    'summary_panen_poin_v2.bulan'
                )
                // JOIN dengan akun_panen_poin_v2 - hanya hitung jika email ada di akun_panen_poin_v2
                ->join('akun_panen_poin_v2', 'summary_panen_poin_v2.email_client', '=', 'akun_panen_poin_v2.email_client')
                // LEFT JOIN dengan mitra_sbp untuk exclude email yang ada di mitra_sbp
                ->leftJoin('mitra_sbp', 'summary_panen_poin_v2.email_client', '=', 'mitra_sbp.email_myads')
                // Exclude email yang ada di mitra_sbp
                ->whereNull('mitra_sbp.id');
            
            \Log::info("Filtering: Email must exist in akun_panen_poin_v2 AND not exist in mitra_sbp");
            
            // Filter berdasarkan role: kalau cvsr, hanya tampilkan data dia sendiri
            if (Auth::user()->role === 'cvsr') {
                $query->where('summary_panen_poin_v2.user_id', Auth::id());
                \Log::info("Filtering by User ID: " . Auth::id() . " (Canvasser)");
            }
            
            // Filter berdasarkan bulan jika ada parameter tanggal
            if ($tanggal) {
                $date = Carbon::parse($tanggal);
                $month = $date->month;
                $year = $date->year;
                $query->whereMonth('summary_panen_poin_v2.created_at', $month)
                      ->whereYear('summary_panen_poin_v2.created_at', $year);
                \Log::info("Filtering by Month: {$month}, Year: {$year}");
            }
            
            // Filter berdasarkan source
            if (request()->has('source') && request()->source != '') {
                $query->where('summary_panen_poin_v2.source', request()->source);
                \Log::info("Filtering by Source: " . request()->source);
            }
            
            // Filter berdasarkan remark
            if (request()->has('remark') && request()->remark != '') {
                $query->where('summary_panen_poin_v2.remark', request()->remark);
                \Log::info("Filtering by Remark: " . request()->remark);
            }
            
            $result = $query->orderByRaw('(summary_panen_poin_v2.poin_package + summary_panen_poin_v2.poin - COALESCE(summary_panen_poin_v2.poin_redeem, 0)) DESC')
                ->get()
                ->map(function($item) {
                    return [
                        'nama_canvasser' => $item->nama_canvasser,
                        'email_client' => $item->email_client,
                        'nomor_hp_client' => $item->nomor_hp_client,
                        'source' => $item->source,
                        'total_settlement' => number_format($item->total_settlement_raw, 0, ',', '.'),
                        'total_settlement_raw' => $item->total_settlement_raw,
                        'poin_bulan_ini' => $item->poin_bulan_ini,
                        'poin_akumulasi' => $item->poin_akumulasi,
                        'poin_package' => $item->poin_package,
                        'poin' => $item->poin,
                        'poin_redeem' => $item->poin_redeem,
                        'poin_sisa' => $item->poin_sisa,
                        'remark' => $item->remark,
                        'bulan' => $item->bulan
                    ];
                })
                ->toArray();
            
            \Log::info("Total Results from Summary: " . count($result));
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error("Error in calculatePanenPoinV2Data: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [];
        }
    }
    
    // Export ke Excel
    public function export(Request $request)
    {
        try {
            $data = $this->calculatePanenPoinV2Data($request->tanggal);
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Header
            $monthYear = Carbon::now()->locale('id')->translatedFormat('F Y');
            $sheet->setCellValue('A1', 'LAPORAN PANEN POIN - ' . strtoupper($monthYear));
            $sheet->mergeCells('A1:J1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Column headers
            $sheet->setCellValue('A3', 'No');
            $sheet->setCellValue('B3', 'Nama Canvasser');
            $sheet->setCellValue('C3', 'Email Client');
            $sheet->setCellValue('D3', 'Nomor HP Client');
            $sheet->setCellValue('E3', 'Source');
            $sheet->setCellValue('F3', 'Total Settlement');
            $sheet->setCellValue('G3', 'Total Poin');
            $sheet->setCellValue('H3', 'Poin Redeem');
            $sheet->setCellValue('I3', 'Poin Sisa');
            $sheet->setCellValue('J3', 'Remark');
            
            $sheet->getStyle('A3:J3')->getFont()->setBold(true);
            $sheet->getStyle('A3:J3')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD9D9D9');
            
            // Data
            $row = 4;
            $no = 1;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $item['nama_canvasser']);
                $sheet->setCellValue('C' . $row, $item['email_client']);
                $sheet->setCellValue('D' . $row, $item['nomor_hp_client']);
                $sheet->setCellValue('E' . $row, $item['source']);
                $sheet->setCellValue('F' . $row, $item['total_settlement']);
                $sheet->setCellValue('G' . $row, $item['poin']);
                $sheet->setCellValue('H' . $row, $item['poin_redeem']);
                $sheet->setCellValue('I' . $row, $item['poin_sisa']);
                $sheet->setCellValue('J' . $row, $item['remark']);
                $row++;
            }
            
            // Auto width
            foreach (range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Download
            $fileName = 'Laporan_Panen_Poin_' . $monthYear . '.xlsx';
            $writer = new Xlsx($spreadsheet);
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
            
        } catch (\Exception $e) {
            \Log::error("Error in export: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }
    
    // Refresh Summary Panen Poin V2 (untuk di-schedule)
    public function refreshSummaryPanenPoinV2()
    {
        try {
            \Log::info('=== REFRESH SUMMARY PANEN POIN STARTED ===');
            
            // Tentukan range tanggal bulan berjalan
            $startDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            // $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            // $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            
            // Ambil semua canvasser
            $canvassers = User::whereIn('role', ['cvsr', 'PH'])->get();
            
            $totalProcessed = 0;
            $totalUpdated = 0;
            $totalInserted = 0;
            
            foreach ($canvassers as $canvasser) {
                $clientEmails = [];
                $leadsMasterEmails = [];
                
                // Ambil email dari user_panen_poin_v2 yang diinput oleh canvasser ini
                $panenPoinData = UserPanenPoinV2::where('user_id', $canvasser->id)
                    ->select('akun_myads_pelanggan', 'nomor_hp_pelanggan')
                    ->get();
                
                foreach ($panenPoinData as $data) {
                    $clientEmails[] = [
                        'email' => strtolower(trim($data->akun_myads_pelanggan)),
                        'nomor_hp' => $data->nomor_hp_pelanggan,
                        'source' => 'user_panen_poin_v2'
                    ];
                }
                
                // Ambil juga dari leads_master
                $leadsData = DB::table('leads_master')
                    ->where('user_id', $canvasser->id)
                    ->select('email', 'mobile_phone')
                    ->get();
                
                foreach ($leadsData as $lead) {
                    $leadEmail = strtolower(trim($lead->email));
                    $leadsMasterEmails[$leadEmail] = true;

                    $clientEmails[] = [
                        'email' => $leadEmail,
                        'nomor_hp' => $lead->mobile_phone ?? '-',
                        'source' => 'leads_master'
                    ];
                }
                
                if (empty($clientEmails)) {
                    continue;
                }

                $clientEmails = collect($clientEmails)
                    ->reject(function ($client) use ($leadsMasterEmails) {
                        return $client['source'] === 'user_panen_poin_v2'
                            && isset($leadsMasterEmails[$client['email']]);
                    })
                    ->unique(function ($client) {
                        return $client['email'] . '|' . $client['source'];
                    })
                    ->values()
                    ->all();
                
                $emails = array_column($clientEmails, 'email');
                
                // Query settlement bulan ini
                $settlementsThisMonth = DB::table('report_balance_top_up')
                    ->select(DB::raw('LOWER(TRIM(email_client)) as email'), DB::raw('SUM(CAST(total_settlement_klien AS DECIMAL(15,2))) as total'))
                    ->whereBetween('tgl_transaksi', [$startDate, $endDate])
                    ->whereNotNull('total_settlement_klien')
                    ->whereIn(DB::raw('LOWER(TRIM(email_client))'), $emails)
                    ->groupBy(DB::raw('LOWER(TRIM(email_client))'))
                    ->pluck('total', 'email')
                    ->toArray();
                
                // Ambil poin_sisa dari bulan sebelumnya (untuk akumulasi)
                $previousMonthPoints = [];
                $currentMonth = Carbon::now()->month;
                $currentYear = Carbon::now()->year;
                
                if ($currentMonth > 1) {
                    // Ambil dari bulan sebelumnya di tahun yang sama
                    $previousMonth = $currentMonth - 1;
                    $previousYear = $currentYear;
                } else {
                    // Jika bulan Januari, ambil dari Desember tahun sebelumnya
                    $previousMonth = 12;
                    $previousYear = $currentYear - 1;
                }
                
                // Query poin_sisa dari summary bulan sebelumnya
                $previousSummary = DB::table('summary_panen_poin_v2')
                    ->select('email_client', DB::raw('(poin - COALESCE(poin_redeem, 0)) as poin_sisa'))
                    ->where('user_id', $canvasser->id)
                    ->whereMonth('created_at', $previousMonth)
                    ->whereYear('created_at', $previousYear)
                    ->get();
                
                foreach ($previousSummary as $prev) {
                    $previousMonthPoints[strtolower(trim($prev->email_client))] = $prev->poin_sisa;
                }
                
                $packagePoint = DB::table('data_paket_seasonal as a')
                    ->join('panen_poin_package_v2 as b', function ($join) {
                        $join->on(
                            DB::raw('LOWER(TRIM(a.name))'),
                            '=',
                            DB::raw('LOWER(TRIM(b.code))')
                        );
                    })
                    ->selectRaw('LOWER(TRIM(email)) as email, SUM(COALESCE(b.point, 0)) as point')
                    ->groupBy(DB::raw('LOWER(TRIM(a.email))'))
                    ->pluck('point', 'email')
                    ->toArray();
                    
                
                // Hitung total poin yang sudah di-redeem dari table prize_redeem (bulan ini)
                // $totalPoinRedeem = DB::table('prize_redeems_v2')
                //     ->where('user_id', $canvasser->id)
                //     ->whereMonth('created_at', Carbon::now()->month)
                //     ->whereYear('created_at', Carbon::now()->year)
                //     ->sum('point_used') ?? 0;
                
                // Update or Insert ke summary table
                foreach ($clientEmails as $client) {
                    $email = $client['email'];
                    $totalSettlement = $settlementsThisMonth[$email] ?? 0;
                    
                    // Ambil poin sisa dari bulan sebelumnya
                    $poinSisaBulanLalu = $previousMonthPoints[$email] ?? 0;
                    
                    $totalpackagePoint = $packagePoint[$email] ?? 0;

                    if ($totalSettlement == 0 && $poinSisaBulanLalu == 0) {
                        continue;
                    }
                    $userPoin = AkunPanenPoinV2::whereRaw('LOWER(TRIM(email_client)) = ?', [$email])->first();
                    $totalPoinRedeem = 0;

                    if ($userPoin) {
                        $totalPoinRedeem = DB::table('prize_redeems_v2')
                            ->where('user_id', $userPoin->id)
                            ->whereMonth('created_at', Carbon::now()->month)
                            ->whereYear('created_at', Carbon::now()->year)
                            ->sum('point_used') ?? 0;
                    }

                    $poinBulanIni = floor($totalSettlement / 250000);
                    $poinAkumulasi = $poinSisaBulanLalu; // Gunakan poin sisa bulan lalu
                    $totalPoin = $poinBulanIni + $poinAkumulasi;
                    $poinSisa = $totalPoin - $totalPoinRedeem;
                    
                    // Tentukan remark berdasarkan poin_sisa
                    $remark = $this->calculateRemark($poinSisa + $totalpackagePoint);
                    
                    // Cek apakah data sudah ada
                    $existing = DB::table('summary_panen_poin_v2')
                        ->where('user_id', $canvasser->id)
                        ->where('email_client', $email)
                        ->whereMonth('created_at', Carbon::now()->month)
                        ->whereYear('created_at', Carbon::now()->year)
                        ->first();
                    
                    $dataToSave = [
                        'user_id' => $canvasser->id,
                        'nama_canvasser' => $canvasser->name,
                        'email_client' => $email,
                        'nomor_hp_client' => $client['nomor_hp'],
                        'source' => $client['source'],
                        'total_settlement' => $totalSettlement,
                        'poin_bulan_ini' => $poinBulanIni,
                        'poin_akumulasi' => $poinAkumulasi,
                        'poin' => $totalPoin,
                        'poin_redeem' => $totalPoinRedeem,
                        'poin_package' => $totalpackagePoint,
                        'remark' => $remark,
                        'bulan' => Carbon::now()->locale('id')->translatedFormat('F Y'),
                        'updated_at' => now()
                    ];
                    
                    if ($existing) {
                        // Update data yang sudah ada, including poin_redeem
                        DB::table('summary_panen_poin_v2')
                            ->where('id', $existing->id)
                            ->update($dataToSave);
                        $totalUpdated++;
                    } else {
                        // Insert data baru
                        $dataToSave['created_at'] = now();
                        DB::table('summary_panen_poin_v2')->insert($dataToSave);
                        $totalInserted++;
                    }
                    
                    $totalProcessed++;
                }
            }

            $leadEmails = DB::table('leads_master')
                ->selectRaw('LOWER(TRIM(email)) as email')
                ->whereNotNull('email')
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($leadEmails)) {
                DB::table('summary_panen_poin_v2')
                    ->where('source', 'user_panen_poin_v2')
                    ->whereIn(DB::raw('LOWER(TRIM(email_client))'), $leadEmails)
                    ->delete();


            }


            
            \Log::info("Summary Panen Poin V2 refreshed. Total: {$totalProcessed}, Updated: {$totalUpdated}, Inserted: {$totalInserted}");
            
            return response()->json([
                'status' => 'success',
                'message' => "Summary Panen Poin V2 updated. Total: {$totalProcessed} (Updated: {$totalUpdated}, Inserted: {$totalInserted})"
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error in refreshSummaryPanenPoinV2: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Hitung remark berdasarkan poin sisa
    private function calculateRemark($poinSisa)
    {
        if ($poinSisa >= 0 && $poinSisa <= 100) {
            return 'Rookie';
        } elseif ($poinSisa >= 101 && $poinSisa <= 300) {
            return 'Rising Star';
        } elseif ($poinSisa >= 301) {
            return 'Champion';
        }
        return 'Rookie'; // default
    }
    
    // Update summary setelah redeem (dipanggil dari RedeemController)
    public function updateSummaryAfterRedeem($userId)
    {
        try {
            \Log::info("=== UPDATE SUMMARY AFTER REDEEM FOR USER: {$userId} ===");
            
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            
            // Hitung total poin yang sudah di-redeem user ini bulan ini
            $totalPoinRedeem = DB::table('prize_redeems_v2')
                ->where('user_id', $userId)
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->sum('point_used') ?? 0;
            
            \Log::info("Total poin redeem for user {$userId}: {$totalPoinRedeem}");
            
            // Update semua record summary user ini di bulan ini
            $summaries = DB::table('summary_panen_poin_v2')
                ->where('user_id', $userId)
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->get();
            
            $updatedCount = 0;
            foreach ($summaries as $summary) {
                $poinSisa = $summary->poin - $totalPoinRedeem;
                $remark = $this->calculateRemark($poinSisa);
                
                DB::table('summary_panen_poin_v2')
                    ->where('id', $summary->id)
                    ->update([
                        'poin_redeem' => $totalPoinRedeem,
                        'remark' => $remark,
                        'updated_at' => now()
                    ]);
                
                $updatedCount++;
            }
            
            \Log::info("Updated {$updatedCount} summary records after redeem");
            
            return [
                'success' => true,
                'updated' => $updatedCount,
                'total_redeem' => $totalPoinRedeem
            ];
            
        } catch (\Exception $e) {
            \Log::error("Error in updateSummaryAfterRedeem: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Sync Akun Panen Poin V2 dari Summary (untuk di-schedule)
    public function syncAkunPanenPoinV2()
    {
        try {
            \Log::info('=== SYNC AKUN PANEN POIN STARTED ===');
            
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            
            // Ambil semua data dari summary bulan ini
            $summaries = DB::table('summary_panen_poin_v2')
                ->select('user_id', 'email_client', 'source')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->groupBy('user_id', 'email_client', 'source')
                ->get();
            
            $totalCreated = 0;
            $totalSkipped = 0;
            
            foreach ($summaries as $summary) {
                // Cek apakah akun sudah ada
                $exists = AkunPanenPoinV2::where('user_id', $summary->user_id)
                    ->where('email_client', $summary->email_client)
                    ->exists();
                
                if ($exists) {
                    $totalSkipped++;
                    continue;
                }
                
                // Ambil nama_akun berdasarkan source
                $namaAkun = null;
                
                if ($summary->source === 'user_panen_poin_v2') {
                    // Ambil dari user_panen_poin_v2
                    $panenPoin = UserPanenPoinV2::where('user_id', $summary->user_id)
                        ->where(DB::raw('LOWER(TRIM(akun_myads_pelanggan))'), strtolower(trim($summary->email_client)))
                        ->first();
                    
                    $namaAkun = $panenPoin ? $panenPoin->nama_pelanggan : null;
                    
                } elseif ($summary->source === 'leads_master') {
                    // Ambil dari report_balance_top_up (company_name)
                    $balanceData = DB::table('report_balance_top_up')
                        ->select('company_name')
                        ->where(DB::raw('LOWER(TRIM(email_client))'), strtolower(trim($summary->email_client)))
                        ->whereNotNull('company_name')
                        ->first();
                    
                    $namaAkun = $balanceData ? $balanceData->company_name : null;
                }
                
                // Jika nama_akun tidak ditemukan, skip
                if (!$namaAkun) {
                    \Log::warning("Nama akun tidak ditemukan untuk email: {$summary->email_client}, source: {$summary->source}");
                    $totalSkipped++;
                    continue;
                }
                
                // Create akun baru
                $akun = AkunPanenPoinV2::create([
                    'user_id' => $summary->user_id,
                    'nama_akun' => $namaAkun,
                    'email_client' => $summary->email_client,
                    'password' => bcrypt('123456'), // Default password
                    'source' => $summary->source,
                ]);
                
                $totalCreated++;                
                // Kirim notifikasi email & WhatsApp
                // Ambil nomor HP dari user_panen_poin_v2 atau leads_master
                $nomorHp = null;
                if ($summary->source === 'user_panen_poin_v2') {
                    $panenPoin = UserPanenPoinV2::where('user_id', $summary->user_id)
                        ->where(DB::raw('LOWER(TRIM(akun_myads_pelanggan))'), strtolower(trim($summary->email_client)))
                        ->first();
                    $nomorHp = $panenPoin ? $panenPoin->nomor_hp_pelanggan : null;
                } elseif ($summary->source === 'leads_master') {
                    $lead = DB::table('leads_master')
                        ->where('user_id', $summary->user_id)
                        ->where(DB::raw('LOWER(TRIM(email))'), strtolower(trim($summary->email_client)))
                        ->first();
                    $nomorHp = $lead ? $lead->mobile_phone : null;
                }
                
                if ($nomorHp) {
                    $this->sendAccountNotification($akun, $nomorHp, '123456');
                }            }
            
            \Log::info("Sync Akun Panen Poin V2 completed. Created: {$totalCreated}, Skipped: {$totalSkipped}");
            
            return response()->json([
                'status' => 'success',
                'message' => "Sync completed. Created: {$totalCreated}, Skipped: {$totalSkipped}"
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error in syncAkunPanenPoinV2: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function manualNotifyAll(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'nama' => 'required',
            'email' => 'required|email',
            'pass' => 'required',
            'uuid' => 'required',
            'hp' => 'nullable'
        ]);

        // 2. Bungkus dalam objek agar kompatibel dengan fungsi private Anda
        $akun = (object) [
            'nama_akun' => $request->nama,
            'email_client' => $request->email,
            'uuid' => $request->uuid
        ];

        $nomorHp = $request->hp;
        $plainPassword = $request->pass;

        // 3. Panggil fungsi private Anda
        try {
            $this->sendAccountNotification($akun, $nomorHp, $plainPassword);
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Notifikasi manual berhasil dipicu untuk ' . $request->email
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    // Kirim notifikasi akun via Email & WhatsApp
    private function sendAccountNotification($akun, $nomorHp, $plainPassword)
    {
        try {
            \Log::info("Sending notification to: {$akun->email_client}");
            
            // Data untuk notifikasi
            $data = [
                'nama_akun' => $akun->nama_akun,
                'email' => $akun->email_client,
                'password' => $plainPassword,
                'uuid' => $akun->uuid,
            ];
            
            // Kirim Email
            // $this->sendEmailNotification($data);
            
            // Kirim WhatsApp
            if ($nomorHp) {
                // $this->sendWhatsAppNotification($nomorHp, $data);
            }
            
            \Log::info("Notification sent successfully to: {$akun->email_client}");
            
        } catch (\Exception $e) {
            \Log::error("Error sending notification: " . $e->getMessage());
            // Don't throw exception, just log it
        }
    }
    
    // Kirim Email
    private function sendEmailNotification($data)
    {
        try {
            \Log::info("Attempting to send email to: {$data['email']}");
            
            // Gunakan send() untuk mengirim langsung (synchronous)
            \Mail::send('emails.akun_panen_poin_v2', $data, function($message) use ($data) {
                $message->to($data['email'], $data['nama_akun'])
                    ->subject('Akun Panen Poin V2 Anda Telah Dibuat')
                    ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            });
            
            \Log::info("Email sent successfully for: {$data['email']}");
            
        } catch (\Exception $e) {
            \Log::error("Error sending email to {$data['email']}: " . $e->getMessage());
            \Log::error("Email config - MAIL_MAILER: " . env('MAIL_MAILER'));
            \Log::error("Email config - MAIL_HOST: " . env('MAIL_HOST'));
            \Log::error("Email config - MAIL_PORT: " . env('MAIL_PORT'));
            throw $e; // Propagate error untuk debugging
        }
    }
    
    // Kirim WhatsApp menggunakan Bot WA Baileys (HTTP API)
    private function sendWhatsAppNotification($nomorHp, $data)
    {
        try {
            // Format nomor HP (hapus 0 di depan, tambah 62)
            $phone = preg_replace('/^0/', '62', $nomorHp);
            
            // URL Bot WA API (sesuaikan dengan config)
            $botUrl = env('WA_BOT_URL') . '/api/send-wa';
            
            \Log::info("Attempting WhatsApp to: {$phone}");
            \Log::info("Bot URL: {$botUrl}");
            
            if (!$botUrl || $botUrl === '/api/send-wa') {
                throw new \Exception("WA_BOT_URL not configured in .env");
            }
            
            // Data yang akan dikirim ke bot
            $postData = [
                'phone' => $phone,
                'nama_akun' => $data['nama_akun'],
                'email' => $data['email'],
                'password' => $data['password'],
                'uuid' => $data['uuid'] ?? null,
                'message' => '' // Bot akan format otomatis jika ada data akun
            ];
            
            // Kirim via HTTP POST ke Bot WA
            $ch = curl_init($botUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new \Exception("cURL Error: {$error}");
            }
            
            \Log::info("Bot API response code: {$httpCode}");
            \Log::info("Bot API response: {$response}");
            
            if ($httpCode !== 200) {
                throw new \Exception("Bot API returned HTTP {$httpCode}: {$response}");
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['success']) && $result['success']) {
                \Log::info("WhatsApp sent successfully to: {$phone}");
            } else {
                $errorMsg = $result['error'] ?? 'Unknown error';
                throw new \Exception("Bot API error: {$errorMsg}");
            }
            
        } catch (\Exception $e) {
            \Log::error("Error sending WhatsApp: " . $e->getMessage());
            throw $e; // Propagate untuk debugging
        }
    }
    
    // Refresh summary untuk single user + email (dipanggil langsung setelah input)
    private function refreshSummaryForSingleUser($userId, $emailClient)
    {
        try {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            
            \Log::info("=== REFRESH SUMMARY FOR SINGLE USER ===");
            \Log::info("User ID: {$userId}, Email: {$emailClient}");
            
            // Ambil email dari user_panen_poin_v2 atau leads_master user ini
            $clientData = UserPanenPoinV2::where('user_id', $userId)
                ->where('akun_myads_pelanggan', $emailClient)
                ->select('akun_myads_pelanggan', 'nomor_hp_pelanggan')
                ->first();
            
            if (!$clientData) {
                // Cek di leads_master jika tidak ada di user_panen_poin_v2
                $clientData = DB::table('leads_master')
                    ->where('user_id', $userId)
                    ->where('email', $emailClient)
                    ->select('email as akun_myads_pelanggan', 'mobile_phone as nomor_hp_pelanggan')
                    ->first();
            }
            
            if (!$clientData) {
                \Log::warning("No client data found for {$emailClient}");
                return;
            }
            
            $email = strtolower(trim($clientData->akun_myads_pelanggan));
            $nomorHp = $clientData->nomor_hp_pelanggan ?? '-';
            
            // Query settlement bulan ini untuk email ini saja
            $settlement = DB::table('report_balance_top_up')
                ->select(DB::raw('SUM(CAST(total_settlement_klien AS DECIMAL(15,2))) as total'))
                ->whereBetween('tgl_transaksi', [$startDate, $endDate])
                ->whereNotNull('total_settlement_klien')
                ->where(DB::raw('LOWER(TRIM(email_client))'), $email)
                ->first();
            
            $totalSettlement = $settlement->total ?? 0;
            
            // Ambil poin sisa dari bulan sebelumnya
            $previousMonth = Carbon::now()->month - 1;
            $previousYear = Carbon::now()->year;
            
            if ($previousMonth < 1) {
                $previousMonth = 12;
                $previousYear = $previousYear - 1;
            }
            
            $previousSummary = DB::table('summary_panen_poin_v2')
                ->select(DB::raw('(poin - COALESCE(poin_redeem, 0)) as poin_sisa'))
                ->where('user_id', $userId)
                ->where('email_client', $email)
                ->whereMonth('created_at', $previousMonth)
                ->whereYear('created_at', $previousYear)
                ->first();
            
            $poinSisaBulanLalu = $previousSummary->poin_sisa ?? 0;
            
            // Hitung total poin redeem bulan ini
            $totalPoinRedeem = DB::table('prize_redeems_v2')
                ->where('user_id', $userId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('point_used') ?? 0;
            
            // Hitung poin bulan ini dan total
            $poinBulanIni = floor($totalSettlement / 250000);
            $poinAkumulasi = $poinSisaBulanLalu;
            $totalPoin = $poinBulanIni + $poinAkumulasi;
            $poinSisa = $totalPoin - $totalPoinRedeem;
            
            // Tentukan remark
            $remark = $this->calculateRemark($poinSisa);
            
            // Cek apakah data sudah ada
            $existing = DB::table('summary_panen_poin_v2')
                ->where('user_id', $userId)
                ->where('email_client', $email)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->first();
            
            $canvasser = User::find($userId);
            $source = $this->getSourceFromUserData($userId, $email);
            
            $dataToSave = [
                'user_id' => $userId,
                'nama_canvasser' => $canvasser->name,
                'email_client' => $email,
                'nomor_hp_client' => $nomorHp,
                'source' => $source,
                'total_settlement' => $totalSettlement,
                'poin_bulan_ini' => $poinBulanIni,
                'poin_akumulasi' => $poinAkumulasi,
                'poin' => $totalPoin,
                'poin_redeem' => $totalPoinRedeem,
                'remark' => $remark,
                'bulan' => Carbon::now()->locale('id')->translatedFormat('F Y'),
                'updated_at' => now()
            ];
            
            if ($existing) {
                DB::table('summary_panen_poin_v2')
                    ->where('id', $existing->id)
                    ->update($dataToSave);
                \Log::info("Summary updated for email: {$email}");
            } else {
                $dataToSave['created_at'] = now();
                DB::table('summary_panen_poin_v2')->insert($dataToSave);
                \Log::info("Summary inserted for email: {$email}");
            }
            
        } catch (\Exception $e) {
            \Log::error("Error in refreshSummaryForSingleUser: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            throw $e;
        }
    }
    
    public function redeemPrize(Request $request)
    {
        $request->validate([
            'akun_id' => 'required|integer',
            'prize_id' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            $akun = AkunPanenPoinV2::findOrFail($request->akun_id);
            $prize = DB::table('prizes_v2')->where('id', $request->prize_id)->lockForUpdate()->first();

            if (!$prize) {
                throw new \Exception('Hadiah tidak ditemukan.');
            }

            if ((int) $prize->stock <= 0) {
                throw new \Exception('Stok hadiah habis.');
            }

            $summary = DB::table('summary_panen_poin_v2')
                ->whereRaw('LOWER(TRIM(email_client)) = ?', [strtolower(trim($akun->email_client))])
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->first();

            if (!$summary) {
                throw new \Exception('Summary poin akun ini tidak ditemukan.');
            }

            $availablePoints = ($summary->poin - ($summary->poin_redeem ?? 0) + ($summary->poin_package ?? 0));

            if ($availablePoints < $prize->point) {
                throw new \Exception('Poin tidak cukup untuk redeem hadiah ini.');
            }

            DB::table('prize_redeems_v2')->insert([
                'user_id' => $akun->id,
                'prize_id' => $prize->id,
                'point_used' => $prize->point,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('prizes_v2')
                ->where('id', $prize->id)
                ->update([
                    'stock' => DB::raw('stock - 1'),
                    'updated_at' => now(),
                ]);

            $this->updateSummaryAfterRedeem($akun->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Redeem berhasil disimpan.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    // Helper: Determine source dari user data
    private function getSourceFromUserData($userId, $email)
    {
        $isManualInput = DB::table('user_panen_poin_v2')
            ->where('user_id', $userId)
            ->where('akun_myads_pelanggan', $email)
            ->exists();

        if ($isManualInput) {
            return 'user_panen_poin_v2';
        }

        return DB::table('leads_master')
            ->where('user_id', $userId)
            ->where('email', $email)
            ->value('source') ?? 'leads_master';
    }

}















