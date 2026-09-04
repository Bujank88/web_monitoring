<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\LeadsSource;
use App\Models\Sector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FrontController extends Controller
{
    public function index()
    {
        if (Auth::user() or 'sukseslogin' == Session::get('login')) {
            if ('User' == Auth::user()->role) {
                return redirect('/home');
            } else if ('Treg' == Auth::user()->role) {
                return redirect()->route('race_summary_treg');
            } else if (in_array(Auth::user()->role, ['Admin', 'Tsel'])) {
                return redirect('/admin/home');
            } else if ('cvsr' == Auth::user()->role) {
                return redirect()->route('presensi.index');
            } else if ('TCD' == Auth::user()->role) {
                return redirect()->route('report-agency-advertising');
            } else if ('Internal' == Auth::user()->role) {
                return redirect()->route('mitra-sbp');
            } else if (in_array(Auth::user()->role, ['SBP', 'Canvasser SBP'])) {
                return redirect()->route('pilot-sbp-sme');
            } else if ('Dormant' == Auth::user()->role) {
                return redirect()->route('data-dormant');
            } else if ('GOTO' == Auth::user()->role) {
                return redirect()->route('report-goto');
            } else if ('Area 2' == Auth::user()->role) {
                return redirect()->route('area2.leads-master.index');
            } else if ('AM' == Auth::user()->role) {
                return redirect()->route('am.referral.index');
            } else if ('b2b' == Auth::user()->role) {
                return redirect()->route('amlevelup.index');
            } else {
            return redirect('/admin/home');
            }
        }
        // return view('errors.503');
        return view('auth.login');
    }
    public function register()
    {
        logUserLogin();
        return view('auth.register');
    }
    public function homeAdmin()
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
        return view('admin.home', compact('months'));
    }
    
    public function dailyTopupChannel()
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
        return view('dailytopup.daily_topup', compact('months'));
    }

    public function dailyTopupRegional()
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

        $regionalName = Auth::user()->regional ?? '-';

        return view('dailytopup.daily_topup_regional', compact('months', 'regionalName'));
    }
    public function logout()
    {
        // Menghapus sesi dan logout
        Session::flush();
        Auth::logout();
        // Redirect ke halaman utama
        return redirect('/');
    }
    public function changePassword()
    {
        // Redirect ke halaman utama
        logUserLogin();
        return view('auth.change-password');
    }
    public function updatePassword(Request $request)
    {
        // Validate input
        $request->validate([
            'current_password' => 'required',
            'current_password_confirmation' => 'required|same:current_password',
            'new_password' => 'required|min:6',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, auth()->user()->password)) {
            // session(['password_attempts' => $attempts + 1]);

            return back()->withErrors([
                'current_password' => 'Current password is incorrect.'
            ]);
        }


        // Update password
        auth()->user()->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Logout user after password change
        Auth::logout();

        // Invalidate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to login with success message
        return redirect()->route('login')->with('success', 'Password changed successfully. Please login again.');
    }
    public function uploadMyAds()
    {
        logUserLogin();
        $myAdsUploads = [
            "Top Up Naik, Cuan Naik",
            "Top Up Ceria",
            "Revenue Top Up",
            "Grup Manajemen User Klien"
        ];
        return view('admin.upload', compact('myAdsUploads'));
    }
    public function downloadTipsSalesPdf()
    {
        $filePath = public_path('images/tips-sales-guide.pdf');

        if (!File::exists($filePath)) {
            abort(404, 'File PDF contoh wording tidak ditemukan.');
        }

        return response()->download($filePath, 'Contoh Wording Tips Sales.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
    public function uploadAutomatechReport()
    {
        logUserLogin();

        $uploadPath = storage_path('app/automatech-report-uploads');
        $uploadedFiles = collect();

        if (File::exists($uploadPath)) {
            $uploadedFiles = collect(File::files($uploadPath))
                ->sortByDesc(fn ($file) => $file->getMTime())
                ->map(function ($file) {
                    return [
                        'name' => $file->getFilename(),
                        'size' => number_format($file->getSize() / 1024, 2) . ' KB',
                        'uploaded_at' => Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i'),
                    ];
                })
                ->values();
        }

        $templateFile = route('admin.upload.automatech-report.template');

        return view('admin.upload-automatech-report', compact('uploadedFiles', 'templateFile'));
    }

    public function uploadGotoReport()
    {
        logUserLogin();

        $uploadPath = storage_path('app/goto-report-uploads');
        $uploadedFiles = collect();

        if (File::exists($uploadPath)) {
            $uploadedFiles = collect(File::files($uploadPath))
                ->sortByDesc(fn ($file) => $file->getMTime())
                ->map(function ($file) {
                    return [
                        'name' => $file->getFilename(),
                        'size' => number_format($file->getSize() / 1024, 2) . ' KB',
                        'uploaded_at' => Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i'),
                    ];
                })
                ->values();
        }

        $templateFile = route('admin.upload.goto-report.template');
        $pageTitle = 'Upload Report GOTO';
        $uploadTitle = 'Upload Excel Report GOTO';
        $uploadDescription = 'Upload file report GOTO khusus admin. Format file mengikuti template yang sudah disediakan.';
        $storeRoute = 'admin.upload.goto-report.store';
        $emptyUploadText = 'Belum ada file report GOTO yang diupload.';
        $templateButtonText = 'Download Template Laporan GOTO';

        return view('admin.upload-automatech-report', compact(
            'uploadedFiles',
            'templateFile',
            'pageTitle',
            'uploadTitle',
            'uploadDescription',
            'storeRoute',
            'emptyUploadText',
            'templateButtonText'
        ));
    }

    public function uploadAvalonKemangBogorReport()
    {
        logUserLogin();

        $uploadPath = storage_path('app/avalon-kemang-bogor-report-uploads');
        $uploadedFiles = collect();

        if (File::exists($uploadPath)) {
            $uploadedFiles = collect(File::files($uploadPath))
                ->sortByDesc(fn ($file) => $file->getMTime())
                ->map(function ($file) {
                    return [
                        'name' => $file->getFilename(),
                        'size' => number_format($file->getSize() / 1024, 2) . ' KB',
                        'uploaded_at' => Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i'),
                    ];
                })
                ->values();
        }

        $templateFile = route('admin.upload.avalon-kemang-bogor-report.template');
        $pageTitle = 'Upload Report Avalon Kemang Bogor';
        $uploadTitle = 'Upload Excel Report Avalon Kemang Bogor';
        $uploadDescription = 'Upload file report Avalon Kemang Bogor khusus admin. Format file mengikuti template yang sudah disediakan.';
        $storeRoute = 'admin.upload.avalon-kemang-bogor-report.store';
        $emptyUploadText = 'Belum ada file report Avalon Kemang Bogor yang diupload.';
        $templateButtonText = 'Download Template Laporan Avalon';

        return view('admin.upload-automatech-report', compact(
            'uploadedFiles',
            'templateFile',
            'pageTitle',
            'uploadTitle',
            'uploadDescription',
            'storeRoute',
            'emptyUploadText',
            'templateButtonText'
        ));
    }

    public function downloadAutomatechReportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['ID IKLAN', 'TGL TAYANG', 'JUDUL PESAN IKLAN', 'OPERATOR SELULER', 'KATEGORI IKLAN', 'TIPE KANAL', 'DETIL STATUS', 'REFUNDED', 'READ', 'CLICK', 'TOTAL HARGA'],
            ['1649001', '11 May 2026', 'WABA PROMO DUMMY 1', 'TELKOMSEL', 'WABA', 'LBA', 'Sukses: 125 Gagal: 7', '5000', '92', '37', '132000'],
            ['1649002', '12 May 2026', 'SMS PROMO DUMMY 2', 'TELKOMSEL', 'LBA', 'SMS', 'Sukses: 98 Gagal: 12', '2000', '41', '18', '98000'],
        ], null, 'A1');

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template Laporan Automatech Dummy.xlsx');
    }

    public function downloadGotoReportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['ID IKLAN', 'TGL TAYANG', 'JUDUL PESAN IKLAN', 'OPERATOR SELULER', 'KATEGORI IKLAN', 'TIPE KANAL', 'DETIL STATUS', 'REFUNDED', 'READ', 'CLICK', 'TOTAL HARGA'],
            ['3649001', '11 May 2026', 'GOTO PROMO DUMMY 1', 'TELKOMSEL', 'WABA', 'LBA', 'Sukses: 125 Gagal: 7', '5000', '92', '37', '132000'],
            ['3649002', '12 May 2026', 'GOTO PROMO DUMMY 2', 'TELKOMSEL', 'LBA', 'SMS', 'Sukses: 98 Gagal: 12', '2000', '41', '18', '98000'],
        ], null, 'A1');

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template Laporan GOTO Dummy.xlsx');
    }

    public function downloadAvalonKemangBogorReportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['ID IKLAN', 'TGL TAYANG', 'JUDUL PESAN IKLAN', 'OPERATOR SELULER', 'KATEGORI IKLAN', 'TIPE KANAL', 'DETIL STATUS', 'REFUNDED', 'READ', 'CLICK', 'TOTAL HARGA'],
            ['2649001', '11 May 2026', 'AVALON KEMANG BOGOR DUMMY 1', 'TELKOMSEL', 'WABA', 'LBA', 'Sukses: 125 Gagal: 7', '5000', '92', '37', '132000'],
            ['2649002', '12 May 2026', 'AVALON KEMANG BOGOR DUMMY 2', 'TELKOMSEL', 'LBA', 'SMS', 'Sukses: 98 Gagal: 12', '2000', '41', '18', '98000'],
        ], null, 'A1');

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template Laporan Avalon Kemang Bogor Dummy.xlsx');
    }


    public function uploadCdsiReport()
    {
        logUserLogin();

        $uploadPath = storage_path('app/cdsi-report-uploads');
        $uploadedFiles = collect();

        if (File::exists($uploadPath)) {
            $uploadedFiles = collect(File::files($uploadPath))
                ->sortByDesc(fn ($file) => $file->getMTime())
                ->map(function ($file) {
                    return [
                        'name' => $file->getFilename(),
                        'size' => number_format($file->getSize() / 1024, 2) . ' KB',
                        'uploaded_at' => Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i'),
                    ];
                })
                ->values();
        }

        $templateFile = route('admin.upload.cdsi-report.template');

        return view('admin.upload-cdsi-report', compact('uploadedFiles', 'templateFile'));
    }

    public function downloadCdsiReportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['ID IKLAN', 'TGL TAYANG', 'JUDUL PESAN IKLAN', 'OPERATOR SELULER', 'KATEGORI IKLAN', 'TIPE KANAL', 'DETIL STATUS', 'REFUNDED', 'READ', 'CLICK', 'TOTAL HARGA'],
            ['1849001', '11 May 2026', 'WABA CDSI DUMMY 1', 'TELKOMSEL', 'WABA', 'LBA', 'Sukses: 105 Gagal: 9', '3000', '75', '29', '118000'],
            ['1849002', '12 May 2026', 'SMS CDSI DUMMY 2', 'TELKOMSEL', 'LBA', 'SMS', 'Sukses: 87 Gagal: 11', '1000', '38', '14', '91000'],
        ], null, 'A1');

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template Laporan CDSI Dummy.xlsx');
    }
    public function uploadMaximReport()
    {
        logUserLogin();

        $uploadPath = storage_path('app/maxim-report-uploads');
        $uploadedFiles = collect();

        if (File::exists($uploadPath)) {
            $uploadedFiles = collect(File::files($uploadPath))
                ->sortByDesc(fn ($file) => $file->getMTime())
                ->map(function ($file) {
                    return [
                        'name' => $file->getFilename(),
                        'size' => number_format($file->getSize() / 1024, 2) . ' KB',
                        'uploaded_at' => Carbon::createFromTimestamp($file->getMTime())->format('d M Y H:i'),
                    ];
                })
                ->values();
        }

        $templateFile = route('admin.upload.maxim-report.template');

        return view('admin.upload-maxim-report', compact('uploadedFiles', 'templateFile'));
    }

    public function downloadMaximReportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['ID IKLAN', 'TGL TAYANG', 'JUDUL PESAN IKLAN', 'OPERATOR SELULER', 'KATEGORI IKLAN', 'TIPE KANAL', 'DETIL STATUS', 'REFUNDED', 'READ', 'CLICK', 'TOTAL HARGA'],
            ['1749001', '12 May 2026', 'WABA MAXIM DUMMY 1', 'TELKOMSEL', 'WABA', 'LBA', 'Sukses: 145 Gagal: 10', '7000', '101', '41', '154000'],
            ['1749002', '13 May 2026', 'SMS MAXIM DUMMY 2', 'TELKOMSEL', 'LBA', 'SMS', 'Sukses: 88 Gagal: 6', '1000', '33', '12', '88000'],
        ], null, 'A1');

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC3545'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template Laporan Maxim Dummy.xlsx');
    }
    public function monitoring_padi_umkm()
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
        return view('admin.monitoring_padi_umkm', compact('months'));
    }
    public function monitoringEventSponsorship()
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
        return view('admin.event_sponsorship', compact('months'));
    }
    public function monitoringCreatorPartner()
    {
        logUserLogin();
        return view('admin.creator_partner');
    }
    public function monitoringSimpatiTiktok()
    {
        logUserLogin();
        return view('admin.simpati_tiktok');
    }
    public function monitoringReferralChampionAm()
    {
        logUserLogin();
        return view('admin.referral_champion');
    }
    public function monitoringReferralChampionTeleAm()
    {
        logUserLogin();
        return view('admin.referral_tele_am');
    }
    public function monitoringReferralChampionCanvasser()
    {
        logUserLogin();
        return view('admin.referral_canvasser');
    }
    public function monitoringSultamRacing()
    {
        logUserLogin();
        return view('admin.sultam_racing');
    }
    public function monitoringCanvasserVoucher()
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
        return view('admin.canvaser_voucher', compact('months'));
    }
    public function monitoringPowerHouseReferral()
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
        
        return view('admin.powerhouse_referral', compact('months'));
    }
    public function monitoringMpccReport()
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

        return view('admin.mpcc_report', compact('months'));
    }
    public function monitoringMpccAreaBranchReport()
    {
        logUserLogin();

        return view('admin.mpcc_area_branch_report');
    }
    public function monitoringPowerHouseSemester()
    {
        logUserLogin();

        $currentDate = Carbon::now();
        $currentSemester = $currentDate->month <= 6 ? 1 : 2;
        $selectedSemester = request('semester', $currentDate->year . '-' . $currentSemester);
        $semesters = [];

        for ($year = $currentDate->year - 1; $year <= $currentDate->year + 1; $year++) {
            foreach ([1, 2] as $semester) {
                $startMonth = $semester === 1 ? 1 : 7;
                $endMonth = $semester === 1 ? 6 : 12;
                $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
                $endDate = Carbon::create($year, $endMonth, 1)->endOfMonth();
                $value = $year . '-' . $semester;

                $semesters[] = [
                    'value' => $value,
                    'label' => sprintf(
                        'Semester %d %d (%s - %s)',
                        $semester,
                        $year,
                        $startDate->translatedFormat('F'),
                        $endDate->translatedFormat('F')
                    ),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'selected' => $value === $selectedSemester,
                ];
            }
        }

        return view('admin.powerhouse_semester', compact('semesters', 'selectedSemester'));
    }
    public function powerhouse2mInputLeads()
    {
        logUserLogin();
        $leadSources = LeadsSource::all();
        $sectors = Sector::all();

        return view('admin.powerhouse_2m_input_leads', compact('leadSources', 'sectors'));
    }

    public function powerhouse2mSummaryLeads()
    {
        logUserLogin();
        return redirect()->route('powerhouse.2m.summary');
    }

    public function powerhouse2mReport()
    {
        logUserLogin();
        return view('admin.powerhouse_2m_report');
    }

    public function pilotSbpToSme()
    {
        logUserLogin();
        return view('admin.pilot_sbp_to_sme');
    }

    public function pilotSbpTopupReferral()
    {
        logUserLogin();
        return view('admin.pilot_sbp_topup_referral');
    }

    public function pilotSbpReferralCanvasserAgent()
    {
        logUserLogin();
        return view('admin.pilot_sbp_referral_canvasser_agent');
    }

    public function pilotSbpDataLeads()
    {
        logUserLogin();
        return view('admin.pilot_sbp_data_leads');
    }
    public function refreshSummarySimpatiTiktok()
    {
        $data = DB::table('simpati_tiktok as st')
            ->select(
                'st.email',
                DB::raw('MAX(st.id) as simpati_tiktok_id'), // ambil id terakhir utk referensi
                DB::raw('MAX(st.no_hp) as no_hp'),
                DB::raw('MAX(st.nama_lengkap) as nama_lengkap'),
                DB::raw('COUNT(r.id) as jumlah_topup'),
                DB::raw('COALESCE(SUM(r.saldo_utama), 0) as total_saldo_utama'),
                DB::raw('COALESCE(SUM(r.saldo_bonus), 0) as total_saldo_bonus'),
                DB::raw('COALESCE(SUM(r.total), 0) as total_topup'),
                DB::raw('MIN(st.created_at) as created_at'),
                DB::raw('MAX(st.updated_at) as updated_at')
            )
            ->leftJoin('manajemen_user_register as mur', 'st.email', '=', 'mur.email')
            ->leftJoin('revenue as r', 'mur.reg_id', '=', 'r.id_klien')
            ->where('r.status', 'PAID')
            ->groupBy('st.email')
            ->get();

        $totalData = count($data);
        $inserted = 0;

        foreach ($data as $row) {
            $result = DB::table('summary_simpati_tiktok')->updateOrInsert(
                ['email' => $row->email], // pakai email sbg unique key
                [
                    'simpati_tiktok_id' => $row->simpati_tiktok_id,
                    'no_hp'         => $row->no_hp,
                    'nama_lengkap'  => $row->nama_lengkap,
                    'jumlah_topup'  => $row->jumlah_topup,
                    'total_saldo_utama' => $row->total_saldo_utama,
                    'total_saldo_bonus' => $row->total_saldo_bonus,
                    'total_topup'   => $row->total_topup,
                    'created_at'    => $row->created_at,
                    'updated_at'    => now(),
                ]
            );
            // updateOrInsert tidak return affected rows, jadi kita anggap semua proses sukses
            $inserted++;
        }

        // Logging ke channel simpatiTiktok
        Log::channel('simpatiTiktok')->info('refreshSummarySimpatiTiktok executed', [
            'total_data_from_query' => $totalData,
            'total_processed' => $inserted,
            'timestamp' => now()->toDateTimeString()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Summary Simpati Tiktok by email updated. Total data: $totalData, processed: $inserted"
        ]);
    }

    public function refreshSummaryPadiUmkm()
    {
        $data = DB::table('padi_umkm as pd')
            ->select(
                'pd.email',
                DB::raw('MAX(pd.id) as padi_umkm_id'),
                DB::raw('MAX(pd.no_hp) as no_hp'),
                DB::raw('MAX(pd.nama) as nama'),
                DB::raw('MAX(pd.nama_usaha) as nama_usaha'),
                DB::raw('COUNT(r.id) as jumlah_topup'),
                DB::raw('COALESCE(SUM(r.total), 0) as total_topup'),
                DB::raw('MIN(pd.created_at) as created_at'),
                DB::raw('MAX(pd.updated_at) as updated_at')
            )
            ->leftJoin('manajemen_user_register as mur', 'pd.email', '=', 'mur.email')
            ->leftJoin('revenue as r', 'mur.reg_id', '=', 'r.id_klien')
            ->groupBy('pd.email')
            ->get();

        foreach ($data as $row) {
            DB::table('summary_padi_umkm')->updateOrInsert(
                ['email' => $row->email],
                [
                    'padi_umkm_id' => $row->padi_umkm_id,
                    'no_hp'        => $row->no_hp,
                    'nama'         => $row->nama,
                    'nama_usaha'   => $row->nama_usaha,
                    'jumlah_topup' => $row->jumlah_topup,
                    'total_topup'  => $row->total_topup,
                    'created_at'   => $row->created_at,
                    'updated_at'   => now(),
                ]
            );
        }

        return response()->json(['status' => 'success', 'message' => 'Summary Padi UMKM by email updated']);
    }
    public function getRegionals(Request $request)
    {
        $query = DB::table('creator_partner')
            ->select('regional')
            ->distinct();

        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        $regionals = $query->orderBy('regional')->pluck('regional');

        return response()->json($regionals);
    }
    public function rekruterKolBuzzer()
    {
        logUserLogin();
        return view('admin.kol_buzzer');
    }
    public function rekruterKolInfluencer()
    {
        logUserLogin();
        return view('admin.kol_influencer');
    }
    public function areaMarkomKol()
    {
        logUserLogin();
        return view('admin.area_marcom');
    }
    public function botVoucher(){
        logUserLogin();
        return view('admin.voucher');
    }
    public function claimedVoucher(){
        logUserLogin();
        return view('admin.user_voucher');
    }
    public function akuisisiVoucherTreg(){
        logUserLogin();
        return view('treg.race_akuisisi');
    }
    
    public function raceSummaryTreg(){
        logUserLogin();
        return view('treg.race_summary');
    }
    public function newLeadsProgram(){
        logUserLogin();
        return view('admin.LeadsProgram.new_leads_program');
    }
    public function loglogin(){
        logUserLogin();
        return view('auth.loglogin');
    }
}





