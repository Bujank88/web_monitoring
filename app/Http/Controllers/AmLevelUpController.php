<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAmLevelUp;
use App\Models\User;
use App\Models\AkunAmLevelUp;
use App\Models\Sector;
use App\Models\LeadsMaster;
use App\Models\B2BAmPointSummary;
use App\Models\B2BClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AmLevelUpController extends Controller
{
    // Tampilkan halaman input data
    public function index()
    {
        logUserLogin();
        $sectors = Sector::all();
        return view('amlevelup.inputdata', compact('sectors'));
    }
    
    // Tampilkan halaman list akun yang sudah terdaftar
    public function listAkun()
    {
        logUserLogin();
        return view('amlevelup.listakun');
    }
    
    // Get data akun untuk DataTable
    public function getAkunData(Request $request)
    {
        try {
            $query = AkunAmLevelUp::query();
            
            // Filter berdasarkan role: kalau cvsr, hanya tampilkan akun dia sendiri
            if (Auth::user()->role === 'cvsr') {
                $query->where('user_id', Auth::id());
            }
            
            $query->select('id', 'nama_akun', 'email_client', 'user_id', 'created_at')
                ->with('user:id,name') // Join dengan user untuk nama canvasser
                ->orderBy('created_at', 'desc');
            
            $data = $query->get()->map(function($item) {
                // Ambil nomor HP dari user_am_level_up atau leads_master
                $nomorHp = DB::table('user_am_level_up')
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
                    'action' => '<button class="btn btn-sm btn-primary btn-lihat-akun" data-id="' . $item->id . '"><i class="fas fa-eye mr-1"></i>Lihat</button>',
                ];
            });
            
            return datatables()->of(collect($data))
                ->rawColumns(['action'])
                ->make(true);
                
        } catch (\Exception $e) {
            \Log::error("Error in getAkunData: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAkunDetail($id)
    {
        try {
            $akun = DB::table('akun_am_level_up as a')
                ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
                ->leftJoin('leads_master as lm', function ($join) {
                    $join->on('a.leads_master_id', '=', 'lm.id')
                        ->orOn(DB::raw('LOWER(TRIM(a.email_client))'), '=', DB::raw('LOWER(TRIM(lm.myads_account))'));
                })
                ->where('a.id', $id)
                ->select(
                    'a.id',
                    'a.user_id',
                    'a.uuid',
                    'a.nama_akun',
                    'a.email_client',
                    'a.source',
                    'a.created_at',
                    'u.name as nama_canvasser',
                    'lm.id as leads_master_id',
                    'lm.company_name',
                    'lm.mobile_phone',
                    'lm.email',
                    'lm.nama as nama_pelanggan',
                    'lm.myads_account',
                    'lm.remarks',
                    'lm.data_type'
                )
                ->first();

            if (!$akun) {
                return response()->json(['message' => 'Data akun tidak ditemukan'], 404);
            }

            if (Auth::user()->role === 'cvsr' && (int)$akun->user_id !== (int)Auth::id()) {
                return response()->json(['message' => 'Anda tidak punya akses ke detail akun ini'], 403);
            }

            return response()->json($akun);
        } catch (\Exception $e) {
            \Log::error("Error in getAkunDetail: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Simpan data AM Level UP
    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'mobile_phone' => ['required', 'string', 'max:20', 'regex:/^62\\d{9,14}$/'],
            'email' => 'required|email|max:255|unique:leads_master,email',
            'nama' => 'nullable|string|max:255',
            'sector_id' => 'nullable|exists:sectors,id',
            'myads_account' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
        ], [
            'mobile_phone.regex' => 'Nomor HP harus diawali 62 dan hanya angka (9-14 digit setelah 62).',
            'email.unique' => 'Email sudah terdaftar di leads master, tidak bisa input ulang.',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Auto-create akun di akun_am_level_up
            $emailClient = strtolower(trim($request->myads_account));
            $nomorHp = trim($request->mobile_phone);
            $namaPelanggan = $request->nama ?: $request->company_name;

            // Simpan field input tambahan ke leads_master (bukan user_am_level_up)
            $leadMaster = LeadsMaster::create([
                'user_id' => Auth::id(),
                'source_id' => null,
                'sector_id' => $request->sector_id,
                'company_name' => $request->company_name,
                'mobile_phone' => $nomorHp,
                'email' => strtolower(trim($request->email)),
                'status' => 1,
                'nama' => $request->nama,
                'address' => null,
                'remarks' => $request->remarks,
                'myads_account' => $emailClient,
                'data_type' => 'Eksisting Akun',
            ]);
            
            // Cek apakah akun sudah ada
            $existingAkun = AkunAmLevelUp::where('user_id', Auth::id())
                ->where('email_client', $emailClient)
                ->first();
            
            $isNewAccount = false;
            
            if (!$existingAkun) {
                // Create akun baru
                $akun = AkunAmLevelUp::create([
                    'user_id' => Auth::id(),
                    'leads_master_id' => $leadMaster->id,
                    'nama_akun' => $namaPelanggan,
                    'email_client' => $emailClient,
                    'password' => bcrypt('123456'), // Default password
                    'source' => 'user_am_level_up',
                ]);
                
                \Log::info("Akun created for: {$emailClient}");
                $isNewAccount = true;
                
                // Simpan ke user_am_level_up hanya jika akun baru
                $amlevelup = UserAmLevelUp::create([
                    'user_id' => Auth::id(),
                    'nama_pelanggan' => $namaPelanggan,
                    'akun_myads_pelanggan' => $emailClient,
                    'nomor_hp_pelanggan' => $nomorHp,
                ]);
            } else {
                $akun = $existingAkun;
                if (!$akun->leads_master_id || $akun->leads_master_id !== $leadMaster->id) {
                    $akun->update(['leads_master_id' => $leadMaster->id]);
                }
                \Log::info("Akun already exists for: {$emailClient}");
                $isNewAccount = false;
            }
            
            // Kirim notifikasi email & WhatsApp (untuk akun baru atau existing)
            try {
                $this->sendAccountNotification(
                    $akun,
                    $nomorHp,
                    '123456' // Plain password untuk notifikasi
                );
            } catch (\Exception $e) {
                \Log::warning("Notification failed (not blocking transaction): " . $e->getMessage());
                // Jangan block transaksi - akun sudah dibuat, notifikasi bisa dicoba ulang
            }
            
            DB::commit();
            
            // Refresh summary AM Level UP untuk user ini (langsung setelah input)
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
            
            return redirect()->route('amlevelup.index')
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
        $months = [];

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->format('Y-m-01'); // bulan sekarang, tanggal 01

        for ($i = 1; $i <= 12; ++$i) {
            $date = Carbon::create($currentYear, $i, 1);
            $months[] = [
                'value' => $date->format('Y-m-d'), // e.g., 2025-05-01
                'label' => $date->translatedFormat('F Y'), // e.g., Mei 2025
                'selected' => $date->format('Y-m-d') === $currentMonth,
            ];
        }
        return view('amlevelup.reportlevelup', compact('months'));
    }

    // Tampilkan halaman report canvasser (ringkasan)
    public function reportCanvasser()
    {
        logUserLogin();
        $months = [];

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->format('Y-m-01');

        for ($i = 1; $i <= 12; ++$i) {
            $date = Carbon::create($currentYear, $i, 1);
            $months[] = [
                'value' => $date->format('Y-m-d'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m-d') === $currentMonth,
            ];
        }

        return view('amlevelup.report-canvasser', compact('months'));
    }
    
    // Get data untuk DataTable
    public function getReportData(Request $request)
    {
        \Log::info('=== GET REPORT DATA CALLED ===');
        \Log::info('User: ' . Auth::user()->name);
        \Log::info('Request URI: ' . $request->getRequestUri());
        \Log::info('Filter Tanggal: ' . $request->tanggal);
        
        try {
            \Log::info('Starting calculateamlevelupData...');
            $result = $this->calculateamlevelupData($request->tanggal);
            
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
        $months = [];

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->format('Y-m-01');

        for ($i = 1; $i <= 12; ++$i) {
            $date = Carbon::create($currentYear, $i, 1);
            $months[] = [
                'value' => $date->format('Y-m-d'),
                'label' => $date->translatedFormat('F Y'),
                'selected' => $date->format('Y-m-d') === $currentMonth,
            ];
        }

        return view('amlevelup.report-ph', compact('months'));
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
                DB::raw('COALESCE(COUNT(CASE WHEN akun_am_level_up.email_client IS NOT NULL AND mitra_sbp.id IS NULL THEN summary_am_level_up.id END), 0) as jumlah_terdaftar'),
                DB::raw('COALESCE(SUM(CASE WHEN akun_am_level_up.email_client IS NOT NULL AND mitra_sbp.id IS NULL AND (COALESCE(summary_am_level_up.poin, 0) > 0 OR COALESCE(summary_am_level_up.poin_package, 0) > 0) THEN 1 ELSE 0 END), 0) as jumlah_akun_punya_poin'),
                DB::raw('COALESCE(SUM(CASE WHEN akun_am_level_up.email_client IS NOT NULL AND mitra_sbp.id IS NULL THEN summary_am_level_up.poin ELSE 0 END), 0) as jumlah_poin')
            )
            ->where('users.role', $role)
            ->leftJoin('summary_am_level_up', function ($join) use ($tanggal) {
                $join->on('summary_am_level_up.user_id', '=', 'users.id');

                if ($tanggal) {
                    $date = Carbon::parse($tanggal);
                    $join->whereMonth('summary_am_level_up.created_at', $date->month)
                         ->whereYear('summary_am_level_up.created_at', $date->year);
                }
            })
            ->leftJoin('akun_am_level_up', 'summary_am_level_up.email_client', '=', 'akun_am_level_up.email_client')
            ->leftJoin('mitra_sbp', 'summary_am_level_up.email_client', '=', 'mitra_sbp.email_myads')
            ->groupBy('users.id', 'users.name')
            ->orderByRaw('COALESCE(SUM(CASE WHEN akun_am_level_up.email_client IS NOT NULL AND mitra_sbp.id IS NULL THEN summary_am_level_up.poin ELSE 0 END), 0) DESC');

        // Filter berdasarkan role: kalau cvsr/PH, hanya tampilkan data dia sendiri
        if (Auth::user()->role === $role) {
            $query->where('users.id', Auth::id());
            \Log::info("Filtering by User ID: " . Auth::id() . " (Role {$role})");
        }

        return $query;
    }
    
    // Hitung data AM Level UP (ambil dari summary table)
    private function calculateamlevelupData($tanggal = null)
    {
        try {
            
            $query = DB::table('summary_am_level_up')
                ->select(
                    'summary_am_level_up.nama_canvasser',
                    'summary_am_level_up.email_client',
                    'summary_am_level_up.nomor_hp_client',
                    'summary_am_level_up.source',
                    DB::raw('CAST(summary_am_level_up.total_settlement AS DECIMAL(15,2)) as total_settlement_raw'),
                    'summary_am_level_up.poin_bulan_ini',
                    'summary_am_level_up.poin_akumulasi',
                    'summary_am_level_up.poin',
                    'summary_am_level_up.poin_package',
                    DB::raw('COALESCE(summary_am_level_up.poin_redeem, 0) as poin_redeem'),
                    DB::raw('(summary_am_level_up.poin - COALESCE(summary_am_level_up.poin_redeem, 0) + COALESCE(summary_am_level_up.poin_package, 0)) as poin_sisa'),
                    'summary_am_level_up.remark',
                    'summary_am_level_up.bulan'
                )
                // JOIN dengan akun_am_level_up - hanya hitung jika email ada di akun_am_level_up
                ->join('akun_am_level_up', 'summary_am_level_up.email_client', '=', 'akun_am_level_up.email_client')
                // LEFT JOIN dengan mitra_sbp untuk exclude email yang ada di mitra_sbp
                ->leftJoin('mitra_sbp', 'summary_am_level_up.email_client', '=', 'mitra_sbp.email_myads')
                // Exclude email yang ada di mitra_sbp
                ->whereNull('mitra_sbp.id');
            
            \Log::info("Filtering: Email must exist in akun_am_level_up AND not exist in mitra_sbp");
            
            // Filter berdasarkan role: kalau cvsr, hanya tampilkan data dia sendiri
            if (Auth::user()->role === 'cvsr') {
                $query->where('summary_am_level_up.user_id', Auth::id());
                \Log::info("Filtering by User ID: " . Auth::id() . " (Canvasser)");
            }
            
            // Filter berdasarkan bulan jika ada parameter tanggal
            if ($tanggal) {
                $date = Carbon::parse($tanggal);
                $month = $date->month;
                $year = $date->year;
                $query->whereMonth('summary_am_level_up.created_at', $month)
                      ->whereYear('summary_am_level_up.created_at', $year);
                \Log::info("Filtering by Month: {$month}, Year: {$year}");
            }
            
            // Filter berdasarkan source
            if (request()->has('source') && request()->source != '') {
                $query->where('summary_am_level_up.source', request()->source);
                \Log::info("Filtering by Source: " . request()->source);
            }
            
            // Filter berdasarkan remark
            if (request()->has('remark') && request()->remark != '') {
                $query->where('summary_am_level_up.remark', request()->remark);
                \Log::info("Filtering by Remark: " . request()->remark);
            }
            
            $result = $query->orderByRaw('(summary_am_level_up.poin_package + summary_am_level_up.poin - COALESCE(summary_am_level_up.poin_redeem, 0)) DESC')
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
            \Log::error("Error in calculateamlevelupData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [];
        }
    }
    
    // Export ke Excel
    public function export(Request $request)
    {
        try {
            $data = $this->calculateamlevelupData($request->tanggal);
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Header
            $monthYear = Carbon::now()->locale('id')->translatedFormat('F Y');
            $sheet->setCellValue('A1', 'LAPORAN AM Level UP - ' . strtoupper($monthYear));
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
    
    // Refresh Summary AM Level UP (untuk di-schedule)
    public function refreshSummaryamlevelup()
    {
        try {
            \Log::info('=== REFRESH SUMMARY AM Level UP STARTED ===');
            
            // Tentukan range tanggal bulan berjalan
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            
            // Ambil semua canvasser
            $canvassers = User::whereIn('role', ['cvsr', 'PH'])->get();
            
            $totalProcessed = 0;
            $totalUpdated = 0;
            $totalInserted = 0;
            
            foreach ($canvassers as $canvasser) {
                $clientEmails = [];
                
                // Ambil email dari user_am_level_up yang diinput oleh canvasser ini
                $amlevelupData = UserAmLevelUp::where('user_id', $canvasser->id)
                    ->select('akun_myads_pelanggan', 'nomor_hp_pelanggan')
                    ->get();
                
                foreach ($amlevelupData as $data) {
                    $clientEmails[] = [
                        'email' => strtolower(trim($data->akun_myads_pelanggan)),
                        'nomor_hp' => $data->nomor_hp_pelanggan,
                        'source' => 'user_am_level_up'
                    ];
                }
                
                // Ambil juga dari leads_master
                $leadsData = DB::table('leads_master')
                    ->where('user_id', $canvasser->id)
                    ->select('email', 'mobile_phone')
                    ->get();
                
                foreach ($leadsData as $lead) {
                    $clientEmails[] = [
                        'email' => strtolower(trim($lead->email)),
                        'nomor_hp' => $lead->mobile_phone ?? '-',
                        'source' => 'leads_master'
                    ];
                }
                
                if (empty($clientEmails)) {
                    continue;
                }
                
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
                $previousSummary = DB::table('summary_am_level_up')
                    ->select('email_client', DB::raw('(poin - COALESCE(poin_redeem, 0)) as poin_sisa'))
                    ->where('user_id', $canvasser->id)
                    ->whereMonth('created_at', $previousMonth)
                    ->whereYear('created_at', $previousYear)
                    ->get();
                
                foreach ($previousSummary as $prev) {
                    $previousMonthPoints[strtolower(trim($prev->email_client))] = $prev->poin_sisa;
                }
                
                $packagePoint = DB::table('data_paket_seasonal as a')
                    ->join('panen_poin_package as b', function ($join) {
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
                $totalPoinRedeem = DB::table('prize_redeems')
                    ->where('user_id', $canvasser->id)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->sum('point_used') ?? 0;
                
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
                    
                    $poinBulanIni = floor($totalSettlement / 1000000);
                    $poinAkumulasi = $poinSisaBulanLalu; // Gunakan poin sisa bulan lalu
                    $totalPoin = $poinBulanIni + $poinAkumulasi;
                    $poinSisa = $totalPoin - $totalPoinRedeem;
                    
                    // Tentukan remark berdasarkan poin_sisa
                    $remark = $this->calculateRemark($poinSisa + $totalpackagePoint);
                    
                    // Cek apakah data sudah ada
                    $existing = DB::table('summary_am_level_up')
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
                        DB::table('summary_am_level_up')
                            ->where('id', $existing->id)
                            ->update($dataToSave);
                        $totalUpdated++;
                    } else {
                        // Insert data baru
                        $dataToSave['created_at'] = now();
                        DB::table('summary_am_level_up')->insert($dataToSave);
                        $totalInserted++;
                    }
                    
                    $totalProcessed++;
                }
            }
            
            \Log::info("Summary AM Level UP refreshed. Total: {$totalProcessed}, Updated: {$totalUpdated}, Inserted: {$totalInserted}");
            
            return response()->json([
                'status' => 'success',
                'message' => "Summary AM Level UP updated. Total: {$totalProcessed} (Updated: {$totalUpdated}, Inserted: {$totalInserted})"
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error in refreshSummaryamlevelup: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Hitung remark berdasarkan poin sisa
    private function calculateRemark($poinSisa)
    {
        if ($poinSisa >= 0 && $poinSisa <= 100) {
            return 'Rookie';
        } elseif ($poinSisa >= 101 && $poinSisa <= 200) {
            return 'Rising Star';
        } elseif ($poinSisa >= 201) {
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
            $totalPoinRedeem = DB::table('prize_redeems')
                ->where('user_id', $userId)
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->sum('point_used') ?? 0;
            
            \Log::info("Total poin redeem for user {$userId}: {$totalPoinRedeem}");
            
            // Update semua record summary user ini di bulan ini
            $summaries = DB::table('summary_am_level_up')
                ->where('user_id', $userId)
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->get();
            
            $updatedCount = 0;
            foreach ($summaries as $summary) {
                $poinSisa = $summary->poin - $totalPoinRedeem;
                $remark = $this->calculateRemark($poinSisa);
                
                DB::table('summary_am_level_up')
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
    
    // Sync Akun AM Level UP dari Summary (untuk di-schedule)
    public function syncAkunAmLevelUp()
    {
        try {
            \Log::info('=== SYNC AKUN AM Level UP STARTED ===');
            
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            
            // Ambil semua data dari summary bulan ini
            $summaries = DB::table('summary_am_level_up')
                ->select('user_id', 'email_client', 'source')
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->groupBy('user_id', 'email_client', 'source')
                ->get();
            
            $totalCreated = 0;
            $totalSkipped = 0;
            
            foreach ($summaries as $summary) {
                // Cek apakah akun sudah ada
                $exists = AkunAmLevelUp::where('user_id', $summary->user_id)
                    ->where('email_client', $summary->email_client)
                    ->exists();
                
                if ($exists) {
                    $totalSkipped++;
                    continue;
                }
                
                // Ambil nama_akun berdasarkan source
                $namaAkun = null;
                
                if ($summary->source === 'user_am_level_up') {
                    // Ambil dari user_am_level_up
                    $amlevelup = UserAmLevelUp::where('user_id', $summary->user_id)
                        ->where(DB::raw('LOWER(TRIM(akun_myads_pelanggan))'), strtolower(trim($summary->email_client)))
                        ->first();
                    
                    $namaAkun = $amlevelup ? $amlevelup->nama_pelanggan : null;
                    
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
                AkunAmLevelUp::create([
                    'user_id' => $summary->user_id,
                    'nama_akun' => $namaAkun,
                    'email_client' => $summary->email_client,
                    'password' => bcrypt('123456'), // Default password
                    'source' => $summary->source,
                ]);
                
                $totalCreated++;                
                // Kirim notifikasi email & WhatsApp
                // Ambil nomor HP dari user_am_level_up atau leads_master
                $nomorHp = null;
                if ($summary->source === 'user_am_level_up') {
                    $amlevelup = UserAmLevelUp::where('user_id', $summary->user_id)
                        ->where(DB::raw('LOWER(TRIM(akun_myads_pelanggan))'), strtolower(trim($summary->email_client)))
                        ->first();
                    $nomorHp = $amlevelup ? $amlevelup->nomor_hp_pelanggan : null;
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
            
            \Log::info("Sync Akun AM Level UP completed. Created: {$totalCreated}, Skipped: {$totalSkipped}");
            
            return response()->json([
                'status' => 'success',
                'message' => "Sync completed. Created: {$totalCreated}, Skipped: {$totalSkipped}"
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error in syncAkunAmLevelUp: " . $e->getMessage());
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
            $this->sendEmailNotification($data);
            
            // Kirim WhatsApp
            if ($nomorHp) {
                $this->sendWhatsAppNotification($nomorHp, $data);
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
            \Mail::send('emails.akun_am_level_up', $data, function($message) use ($data) {
                $message->to($data['email'], $data['nama_akun'])
                    ->subject('Akun AM Level UP Anda Telah Dibuat')
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
            $botUrl = env('WA_BOT_URL') . '/api/send-wa-am';
            
            \Log::info("Attempting WhatsApp to: {$phone}");
            \Log::info("Bot URL: {$botUrl}");
            
            if (!$botUrl || $botUrl === '/api/send-wa-am') {
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
            
            // Ambil email dari user_am_level_up atau leads_master user ini
            $clientData = UserAmLevelUp::where('user_id', $userId)
                ->where('akun_myads_pelanggan', $emailClient)
                ->select('akun_myads_pelanggan', 'nomor_hp_pelanggan')
                ->first();
            
            if (!$clientData) {
                // Cek di leads_master jika tidak ada di user_am_level_up
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
            
            $previousSummary = DB::table('summary_am_level_up')
                ->select(DB::raw('(poin - COALESCE(poin_redeem, 0)) as poin_sisa'))
                ->where('user_id', $userId)
                ->where('email_client', $email)
                ->whereMonth('created_at', $previousMonth)
                ->whereYear('created_at', $previousYear)
                ->first();
            
            $poinSisaBulanLalu = $previousSummary->poin_sisa ?? 0;
            
            // Hitung total poin redeem bulan ini
            $totalPoinRedeem = DB::table('prize_redeems')
                ->where('user_id', $userId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('point_used') ?? 0;
            
            // Hitung poin bulan ini dan total
            $poinBulanIni = floor($totalSettlement / 1000000);
            $poinAkumulasi = $poinSisaBulanLalu;
            $totalPoin = $poinBulanIni + $poinAkumulasi;
            $poinSisa = $totalPoin - $totalPoinRedeem;
            
            // Tentukan remark
            $remark = $this->calculateRemark($poinSisa);
            
            // Cek apakah data sudah ada
            $existing = DB::table('summary_am_level_up')
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
                DB::table('summary_am_level_up')
                    ->where('id', $existing->id)
                    ->update($dataToSave);
                \Log::info("Summary updated for email: {$email}");
            } else {
                $dataToSave['created_at'] = now();
                DB::table('summary_am_level_up')->insert($dataToSave);
                \Log::info("Summary inserted for email: {$email}");
            }
            
        } catch (\Exception $e) {
            \Log::error("Error in refreshSummaryForSingleUser: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            throw $e;
        }
    }
    
    // Helper: Determine source dari user data
    private function getSourceFromUserData($userId, $email)
    {
        $existsInUserAmLevelUp = DB::table('user_am_level_up')
            ->where('user_id', $userId)
            ->where('akun_myads_pelanggan', $email)
            ->exists();
        
        if ($existsInUserAmLevelUp) {
            return 'user_am_level_up';
        }

        $existsInLeadsMaster = DB::table('leads_master')
            ->where('user_id', $userId)
            ->where('email', $email)
            ->exists();

        if ($existsInLeadsMaster) {
            return 'leads_master';
        }
        
        return 'user_am_level_up';
    }

    public function summaryReportB2B()
    {
        $users = User::where('role', 'b2b')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('amlevelup.summary', compact('users'));
    }

    public function summaryReportB2BData(Request $request)
    {
        $userId = $request->user_id;

        $query = User::where('role', 'b2b');

        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        $rows = $users->map(function ($user) {

            // 🔹 1️⃣ Total Topup Akumulasi Semua Bulan
            $totalTopup = B2BAmPointSummary::where('user_id', $user->id)
                ->sum('total_topup');

            // 🔹 2️⃣ Ambil Data Bulan Terakhir
            $lastMonth = B2BAmPointSummary::where('user_id', $user->id)
                ->latest('period_month')
                ->first();

            $clientCount = $lastMonth->client_count ?? 0;
            $totalPoint  = $lastMonth->point_rounded ?? 0;
            $redeem      = $lastMonth->total_redeem_point ?? 0;

            $sisa = max($totalPoint - $redeem, 0);

            return [
                'nama_user'    => $user->name,
                'jumlah_klien' => $clientCount,
                'total_topup'  => number_format($totalTopup, 0, ',', '.'),
                'total_poin'   => $totalPoint,
                'redeem_poin'  => $redeem,
                'sisa_poin'    => $sisa,
                'action'       => '<a href="'.route('amlevelup.clients',['user_id'=>$user->id]).'" 
                                    class="btn btn-sm btn-primary">
                                    Lihat Klien
                                </a>'
            ];
        });

        return response()->json([
            'data' => $rows
        ]);
    }

    public function reportB2BClients(Request $request)
    {
        $userId = $request->user_id;
        $month  = $request->month; // format: 2026-02

        /*
        |--------------------------------------------------------------------------
        | Ambil Data Client + Filter User (AM)
        |--------------------------------------------------------------------------
        */
        $clients = B2BClient::with('user')
            ->when($userId, function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Query Topup (Case Insensitive Email)
        |--------------------------------------------------------------------------
        */
         $topupQuery = DB::table('report_balance_top_up')
        ->selectRaw("
            LOWER(email_client) as email_client,
            DATE_FORMAT(tgl_transaksi, '%Y-%m') as bulan,
            SUM(total_settlement_klien) as total_topup,
            FLOOR(SUM(total_settlement_klien) / 1000000) as total_point,
            MAX(tgl_transaksi) as last_transaction_date
        ")
        ->whereDate('tgl_transaksi', '>', '2026-01-01')
        ->groupBy(
            DB::raw("LOWER(email_client)"),
            DB::raw("DATE_FORMAT(tgl_transaksi, '%Y-%m')")
        );

        /*
        |--------------------------------------------------------------------------
        | Filter Bulan Jika Dipilih
        |--------------------------------------------------------------------------
        */
        if ($month) {
            $parsedMonth = Carbon::parse($month);

            $topupQuery->whereMonth('tgl_transaksi', $parsedMonth->month)
                    ->whereYear('tgl_transaksi', $parsedMonth->year);
        }

        /*
        |--------------------------------------------------------------------------
        | Eksekusi Query & Jadikan KeyBy Email
        |--------------------------------------------------------------------------
        */
$topups = $topupQuery->get()
        ->groupBy('email_client'); // supaya per email ada list bulanan

        /*
        |--------------------------------------------------------------------------
        | Ambil List User Untuk Dropdown Filter
        |--------------------------------------------------------------------------
        */
        $users = User::where('role', 'b2b')->orderBy('name')->get();

        return view('amlevelup.clients', compact(
            'clients',
            'topups',
            'users',
            'userId',
            'month'
        ));
    }

}
