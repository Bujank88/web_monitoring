<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\GetDataController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TregController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadsMasterController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\LogbookDailyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PanenPoinController;
use App\Http\Controllers\AmLevelUpController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\LeadProgramController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\LocationPresensiController;
use App\Http\Controllers\MitraSbpController;

Route::get('/', [FrontController::class, 'index'])->name('home');
Route::get('/login', [FrontController::class, 'index']);
Route::post('/login', [BackController::class, 'login'])->name('login');
Route::get('/register', [FrontController::class, 'register'])->name('register');
Route::post('/register-simpan', [BackController::class, 'registerStore'])->name('register.store');

Route::get('/get-data-rahasia-bisnis-kuesioner', [GetDataController::class, 'getDataRahasiaBisnis']);
Route::get('/get-data-padi-umkm', [GetDataController::class, 'getDataPadiUmkm']);
Route::get('/get-data-creator-partner', [GetDataController::class, 'getDataCreatorPartner']);
// routes/web.php
Route::get('/get-regionals', [FrontController::class, 'getRegionals'])->name('get_regionals');

Route::get('/get-data-simpati-tiktok', [GetDataController::class, 'getDataSimpatiTiktok']);
Route::get('/get-data-referral-champion-am', [GetDataController::class, 'getDataReferralChampionAm']);
Route::get('/get-data-sultam-racing', [GetDataController::class, 'getDataSultamRacing']);
Route::get('/get-data-event-sponsorship', [GetDataController::class, 'getDataEventSponsorhip']);
Route::get('/get-data-rekruter-kol', [GetDataController::class, 'getDataRekruterKol']);

Route::get('/summary-padi-umkm', [FrontController::class, 'refreshSummaryPadiUmkm']);
Route::get('/summary-tiktok', [FrontController::class, 'refreshSummarySimpatiTiktok']);


Route::get('/change-password', [FrontController::class, 'changePassword'])->name('change-password');
Route::post('/change-password', [FrontController::class, 'updatePassword'])->name('password.update');
Route::get('/logout', [FrontController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'checkrole:Admin,Treg'])->group(function (){
    Route::get('/monitoring-akuisisi-treg', [FrontController::class, 'akuisisiVoucherTreg'])->name('monitoring_akuisisi_treg');
    Route::get('/race-summary-treg', [FrontController::class, 'raceSummaryTreg'])->name('race_summary_treg');
    Route::get('/get-akuisisi-data', [TregController::class, 'getDetailAkuisisi'])->name('akuisisi_data');
    Route::get('/get-treg-summary-data', [TregController::class, 'getTregSummaryData'])->name('treg_summary_data');
    Route::post('/upload-voucher-csv', [TregController::class, 'uploadCsv'])->name('upload.voucher.csv');
    Route::get('/download-format-voucher-treg', [TregController::class, 'downloadFormatVoucherTreg'])
    ->name('download.format.voucher.treg');
});

Route::middleware(['auth', 'checkrole:Admin,Tsel,cvsr,PH'])->group(function () {
    Route::get('/admin/home', [HomeController::class, 'index'])->name('admin.home');
    Route::get('/daily-topup-channel', [FrontController::class, 'dailyTopupChannel'])->name('daily.topup.channel');
    Route::get('/get-daily-topup-data', [LeadProgramController::class, 'getDailyTopupDataTable'])->name('daily_topup_data');
    Route::get('/get-daily-topup-by-province-data', [LeadProgramController::class, 'getDailyTopupByProvinceDataTable'])->name('daily_topup_by_province_data');
    Route::get('/export-daily-topup', [BackController::class, 'exportDailyTopup'])->name('export.daily_topup');
    Route::get('/export-daily-topup-by-province', [LeadProgramController::class, 'exportDailyTopupByProvince'])->name('export.daily_topup_by_province');
    Route::get('/export-regional', [BackController::class, 'exportRegional'])->name('export.regional');
    Route::get('/get-leads-data-api', [LeadProgramController::class, 'getLeadsDataApi'])->name('leads_data_api');
    Route::get('/get-regional-data', [LeadProgramController::class, 'getRegionalDataTable'])->name('regional_data');
    Route::get('/get-regional-chart-data', [LeadProgramController::class, 'getRegionalChartData'])->name('regional_chart_data');
    Route::get('/get-regional-chart-data-for-ph', [LeadProgramController::class, 'getRegionalChartDataForPH'])->name('regional_chart_data_for_ph');

    Route::get('/upload-file-myads', [FrontController::class, 'uploadMyAds'])->name('admin.upload');
    Route::post('/store-csv-myads', [BackController::class, 'storeUploadMyAds'])->name('upload.myads.store');
    Route::post('/get-table-name', [BackController::class, 'getTableName'])->name('upload.myads.getTableName');
    Route::get('/download-format/{table}', [BackController::class, 'downloadFormat'])->name('upload.myads.downloadFormat');

    // Padi UMKM
    Route::get('/monitoring-padi-umkm', [FrontController::class, 'monitoring_padi_umkm'])->name('admin.monitoring.padi_umkm');
    Route::get('/get-padi-umkm-data', [BackController::class, 'getPadiUmkmData'])->name('padi_umkm_data');
    Route::get('/padi-umkm/summary', [BackController::class, 'getPadiUmkmSummary'])
    ->name('padi_umkm.summary');

    // Event Sponsorship
    Route::get('/monitoring-event-sponsorship', [FrontController::class, 'monitoringEventSponsorship'])->name('admin.monitoring.event_sponsorship');
    Route::get('/get-event-sponsorship-data', [BackController::class, 'getEventSponsorship'])->name('event_sponsorship_data');

    // Creator Partner
    Route::get('/monitoring-creator-partner', [FrontController::class, 'monitoringCreatorPartner'])->name('admin.monitoring.creator_partner');
    Route::get('/get-creator-partner-data', [BackController::class, 'getCreatorPartner'])->name('creator_partner_data');

    // Rekruter Kol Buzzer
    Route::get('/monitoring-rekruter-kol-buzzer', [FrontController::class, 'rekruterKolBuzzer'])->name('admin.monitoring.rekruter_kol_buzzer');
    Route::get('/get-rekruter-kol-buzzer-data', [BackController::class, 'getRekrutBuzzer'])->name('rekruter_kol_buzzer_data');
    
    // Rekruter Kol Influencer
    Route::get('/monitoring-rekruter-kol-influencer', [FrontController::class, 'rekruterKolInfluencer'])->name('admin.monitoring.rekruter_kol_influencer');
    Route::get('/get-rekruter-kol-influencer-data', [BackController::class, 'getRekruterInfluencer'])->name('rekruter_kol_influencer_data');

    // Rekruter KOL Area Marcom
    Route::get('/monitoring-kol-area-marcom', [FrontController::class, 'areaMarkomKol'])->name('admin.monitoring.area_marcom');
    Route::get('/get-monitoring-kol-area-marcom-data', [BackController::class, 'getAreaMarcom'])->name('area_marcom_kol_data');

    
    // Simpati Tiktok
    Route::get('/monitoring-simpati-tiktok', [FrontController::class, 'monitoringSimpatiTiktok'])->name('admin.monitoring.simpati_tiktok');
    Route::get('/get-simpati-tiktok-data', [BackController::class, 'getSimpatiTiktok'])->name('simpati_tiktok_data');

    // Referral Champion AM
    Route::get('/monitoring-referral-champion-am', [FrontController::class, 'monitoringReferralChampionAm'])->name('admin.monitoring.referral_champion');
    Route::get('/get-referral-champion-am-data', [BackController::class, 'getReferralChampionAm'])->name('referral_champion_am_data');

    // Referral Champion Tele AM
    Route::get('/monitoring-referral-champion-tele-am', [FrontController::class, 'monitoringReferralChampionTeleAm'])->name('admin.monitoring.referral_tele_am');

    // Referral Champion Canvasser
    Route::get('/referral-champion-canvasser', [FrontController::class, 'monitoringCanvasserVoucher'])->name('admin.monitoring.canvasser_voucher');
    Route::get('/get-canvasser-voucher-data', [BackController::class, 'getCanvasserVoucher'])->name('canvasser_voucher_data');
    Route::get('/get-canvasser-voucher-summary', [BackController::class, 'getCanvasserVoucherSummary'])->name('canvasser_voucher_summary');
    Route::get('/export-canvasser-voucher', [BackController::class, 'exportCanvasserVoucher'])->name('export.canvasser_voucher');
    Route::get('/export-canvasser-voucher-summary', [BackController::class, 'exportCanvasserVoucherSummary'])->name('export.canvasser_voucher_summary');

    // PowerHouse Referral
    Route::get('/powerhouse-referral', [FrontController::class, 'monitoringPowerHouseReferral'])->name('admin.monitoring.powerhouse_referral');
    Route::get('/get-powerhouse-voucher-data', [BackController::class, 'getPowerHouseVoucher'])->name('powerhouse_voucher_data');
    Route::get('/get-powerhouse-deal-topup-mom-data', [BackController::class, 'getPowerHouseDealTopupMom'])->name('powerhouse_deal_topup_mom_data');
    Route::get('/export-powerhouse-voucher', [BackController::class, 'exportPowerHouseVoucher'])->name('export.powerhouse_voucher');

    Route::get('/monitor-voucher', [FrontController::class, 'botVoucher'])->name('admin.voucher');
    Route::get('/monitor-claim-voucher', [FrontController::class, 'claimedVoucher'])->name('admin.claim.voucher');
    Route::prefix('vouchers')->name('vouchers.')->group(function () {
    
    // == ROUTE UNTUK MANAJEMEN VOUCHER ==
    Route::get('/data', [BackController::class, 'getVouchers'])->name('data');
    Route::post('/tambah', [BackController::class, 'tambahVoucher'])->name('tambah');
    Route::post('/update/{id}', [BackController::class, 'updateVoucher'])->name('update');
    Route::delete('/hapus/{id}', [BackController::class, 'hapusVoucher'])->name('hapus');
    Route::get('/download-claimed-voucher', [BackController::class, 'downloadVouchers'])->name('download-claim-voucher'); // Route untuk download

    Route::get('/stats', [BackController::class, 'getVoucherStats'])->name('stats');
    // --- Pemisah untuk kejelasan ---

    // == ROUTE UNTUK USER YANG KLAIM VOUCHER ==

    /**
     * Route untuk mengambil data user yang sudah klaim (untuk DataTables).
     * URL: /vouchers/claimed/data
     */
    Route::get('/claimed/data', [BackController::class, 'getClaimedVouchers'])->name('claimed.data');

    /**
     * Route untuk proses update data user.
     * URL: /vouchers/update-user/{user_id}
     */
    Route::post('/update-user/{user_id}', [BackController::class, 'updateUser'])->name('update.user');

    /**
     * Route untuk proses melepaskan klaim voucher dari user.
     * URL: /vouchers/unclaim/{voucher_id}
     */
    Route::delete('/unclaim/{voucher_id}', [BackController::class, 'unclaimVoucher'])->name('unclaim');
});

    Route::get('/users', [UserController::class, 'index'])->name('users.page');
    Route::get('/users-data', [UserController::class, 'getUsers'])->name('users.data');
    Route::post('/users-store', [UserController::class, 'storeUser'])->name('users.store');
    Route::get('/users-edit/{id}', [UserController::class, 'editUser'])->name('users.edit');
    Route::post('/users-update/{id}', [UserController::class, 'updateUser'])->name('users.update');
    Route::post('/users-delete/{id}', [UserController::class, 'deleteUser'])->name('users.delete');

    // Sultam Racing
    Route::get('/monitoring-sultam-racing', [FrontController::class, 'monitoringSultamRacing'])->name('admin.monitoring.sultam_racing');
    Route::get('/get-sultam-racing-data', [BackController::class, 'getSultamRacing'])->name('sultam_racing_data');

    // Log Login
    Route::get('/loglogin', [FrontController::class, 'loglogin'])->name('loglogin');
    Route::get('/get-loglogin-data', [BackController::class, 'getLogLogin'])->name('loglogin.data');
    
});
Route::middleware(['auth', 'checkrole:Admin,cvsr,PH'])->group(function (){
    Route::view('faq-l0', 'faq.l0')->name('faq-l0');

    Route::get('leads-master/export', [LeadsMasterController::class, 'export'])->name('leads-master.export');
    Route::get('leads-master', [LeadsMasterController::class, 'index'])->name('leads-master.index');
    Route::get('leads-master/create', [LeadsMasterController::class, 'create'])->name('leads-master.create');
    Route::get('leads-master/create-existing', [LeadsMasterController::class, 'createExisting'])->name('leads-master.create-existing');
    Route::get('leads-master/create-enterprise', [LeadsMasterController::class, 'createEnterprise'])->name('leads-master.create-enterprise');
    Route::get('leads-master/data', [LeadsMasterController::class, 'data'])->name('leads-master.data');
    Route::post('leads-master/store', [LeadsMasterController::class, 'store'])->name('leads-master.store');
    Route::post('leads-master/store-existing', [LeadsMasterController::class, 'storeExisting'])->name('leads-master.store-existing');
    Route::post('leads-master/store-enterprise', [LeadsMasterController::class, 'storeEnterprise'])->name('leads-master.store-enterprise');
    Route::get('leads-master/{id}', [LeadsMasterController::class, 'show'])->name('leads-master.show');
    Route::get('leads-master/{lead}/edit', [LeadsMasterController::class, 'edit'])->name('leads-master.edit')->whereNumber('lead');
    Route::put('leads-master/{lead}', [LeadsMasterController::class, 'update'])->name('leads-master.update')->whereNumber('lead');


    Route::get('logbook', [LogbookController::class, 'index'])->name('logbook.index');
    Route::get('logbook/data', [LogbookController::class, 'data'])->name('logbook.data');
    Route::post('/logbook/update', [LogbookController::class, 'update'])->name('logbook.update');
    Route::post('/logbook/insert', [LogbookController::class, 'insert'])->name('logbook.insert');
    Route::post('/logbook/day', [LogbookController::class, 'insertDaily'])->name('logbook.day');
    
    Route::get('/logbook-daily', [LogbookDailyController::class, 'index'])->name('logbook-daily.index');
    Route::get('logbook-daily/data', [LogbookDailyController::class, 'data'])->name('logbook-daily.data');
    Route::get('/logbook-daily/summary', [LogbookDailyController::class, 'summary'])->name('logbook-daily.summary');
    Route::get('/logbook-daily/refresh', [LogbookDailyController::class, 'refreshLogbookDaily']);
    Route::post('/logbook-daily/realisasi', [LogbookDailyController::class, 'realisasiLogbook'])->name('logbook-daily.realisasiLogbook');
    Route::get('/logbook-daily/foto/{filePath}', [LogbookDailyController::class, 'getFoto'])->name('logbook-daily.foto')->where('filePath', '.*');
    Route::post('/logbook-daily/realisasi', [LogbookDailyController::class, 'realisasiLogbook'])->name('logbook-daily.realisasiLogbook');

    Route::get('logbook-event', [LogbookController::class, 'logbookEvent'])->name('logbook-event.index');
    Route::get('logbook-event/data', [LogbookController::class, 'logbookEventData'])->name('logbook-event.data');

    // Route::get('topup-canvasser', [ReportController::class, 'topupCanvasser'])->name('topup-canvasser');
    Route::get('topup-canvasser', [ReportController::class, 'topupCanvasser'])->name('topup-canvasser');
    Route::get('topup-canvasser/data', [ReportController::class, 'topupCanvasserData']);
    Route::get('topup-canvasser/excel', [ReportController::class, 'exportTopupCanvasserExcel'])->name('topup-canvasser.excel');
    Route::get('topup-canvasser/pdf', [ReportController::class, 'exportTopupCanvasserPdf'])->name('topup-canvasser.pdf');

    // Panen Poin Routes
    Route::get('panen-poin/input', [PanenPoinController::class, 'index'])->name('panenpoin.index');
    Route::post('panen-poin/store', [PanenPoinController::class, 'store'])->name('panenpoin.store');
    Route::get('panen-poin/report', [PanenPoinController::class, 'report'])->name('panenpoin.report');
    Route::get('panen-poin/report-data', [PanenPoinController::class, 'getReportData'])->name('panenpoin.report-data');
    Route::get('panen-poin/report-canvasser', [PanenPoinController::class, 'reportCanvasser'])->name('panenpoin.report-canvasser');
    Route::get('panen-poin/report-canvasser-data', [PanenPoinController::class, 'getReportCanvasserData'])->name('panenpoin.report-canvasser-data');
    Route::get('panen-poin/report-ph', [PanenPoinController::class, 'reportPowerhouse'])->name('panenpoin.report-ph');
    Route::get('panen-poin/report-ph-data', [PanenPoinController::class, 'getReportPowerhouseData'])->name('panenpoin.report-ph-data');
    Route::get('panen-poin/export', [PanenPoinController::class, 'export'])->name('panenpoin.export');
    Route::get('panen-poin/refresh-summary', [PanenPoinController::class, 'refreshSummaryPanenPoin'])->name('panenpoin.refresh');
    Route::get('panen-poin/list-akun', [PanenPoinController::class, 'listAkun'])->name('panenpoin.list-akun');
    Route::get('panen-poin/akun-data', [PanenPoinController::class, 'getAkunData'])->name('panenpoin.akun-data');

    Route::get('region-target', [ReportController::class, 'reportRegionTargetVsTopup'])->name('region-target');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [CalendarController::class, 'events']);
    Route::post('/calendar/store', [CalendarController::class, 'store']);
    Route::post('/calendar/update/{id}', [CalendarController::class, 'update']);
    Route::delete('/calendar/delete/{id}', [CalendarController::class, 'delete']);
    Route::get('/calendar/download', [CalendarController::class, 'download']);

// Route::get('/send-manual-notif', [PanenPoinController::class, 'manualNotifyAll']);
});

Route::middleware(['auth', 'checkrole:Admin,cvsr,PH,b2b'])->group(function () {
    // AM Level UP Routes
    Route::get('am-level-up/input', [AmLevelUpController::class, 'index'])->name('amlevelup.index');
    Route::post('am-level-up/store', [AmLevelUpController::class, 'store'])->name('amlevelup.store');
    Route::get('am-level-up/report', [AmLevelUpController::class, 'report'])->name('amlevelup.report');
    Route::get('am-level-up/report-data', [AmLevelUpController::class, 'getReportData'])->name('amlevelup.report-data');
    Route::get('am-level-up/report-canvasser', [AmLevelUpController::class, 'reportCanvasser'])->name('amlevelup.report-canvasser');
    Route::get('am-level-up/report-canvasser-data', [AmLevelUpController::class, 'getReportCanvasserData'])->name('amlevelup.report-canvasser-data');
    Route::get('am-level-up/report-ph', [AmLevelUpController::class, 'reportPowerhouse'])->name('amlevelup.report-ph');
    Route::get('am-level-up/report-ph-data', [AmLevelUpController::class, 'getReportPowerhouseData'])->name('amlevelup.report-ph-data');
    Route::get('am-level-up/export', [AmLevelUpController::class, 'export'])->name('amlevelup.export');
    Route::get('am-level-up/refresh-summary', [AmLevelUpController::class, 'refreshSummaryPanenPoin'])->name('amlevelup.refresh');
    Route::get('am-level-up/list-akun', [AmLevelUpController::class, 'listAkun'])->name('amlevelup.list-akun');
    Route::get('am-level-up/akun-data', [AmLevelUpController::class, 'getAkunData'])->name('amlevelup.akun-data');
    Route::get('am-level-up/akun-detail/{id}', [AmLevelUpController::class, 'getAkunDetail'])->name('amlevelup.akun-detail');
    
    Route::get('/am-level-up/summary', [AmLevelUpController::class, 'summaryReportB2B'])
    ->name('amlevelup.summary');
    
    Route::get('/am-level-up/summary-data', [AmLevelUpController::class, 'summaryReportB2BData'])
        ->name('amlevelup.summary-data');

    Route::get('/am-level-up/clients', [AmLevelUpController::class, 'reportB2BClients'])
        ->name('amlevelup.clients');
});

Route::middleware(['auth', 'checkrole:Admin,PH,TCD,Maxim'])->group(function () {
    Route::get('report-agency-advertising', [ReportController::class, 'reportAgencyAdvertising'])->name('report-agency-advertising');
    Route::get('report-agency-advertising/data', [ReportController::class, 'reportAgencyAdvertisingData'])->name('report-agency-advertising.data');
    Route::get('report-agency-advertising/export', [ReportController::class, 'exportAgencyAdvertising'])->name('report-agency-advertising.export');
    Route::get('report-maxim', [ReportController::class, 'reportMaxim'])->name('report-maxim');
    Route::get('report-maxim/data', [ReportController::class, 'reportMaximData'])->name('report-maxim.data');
    Route::get('report-maxim/export', [ReportController::class, 'exportMaxim'])->name('report-maxim.export');
    Route::get('report-saldo-maxim', [ReportController::class, 'reportSaldoMaxim'])->name('report-saldo-maxim');
    Route::get('report-saldo-maxim/data', [ReportController::class, 'reportSaldoMaximData'])->name('report-saldo-maxim.data');
});


Route::middleware(['auth', 'checkrole:Admin,PH,Internal'])->group(function () {
    
    Route::get('mitra-sbp', [ReportController::class, 'reportMitraSBP'])->name('mitra-sbp');
    Route::get('report-campaign-sbp', [ReportController::class, 'reportCampaignSbp'])->name('report-campaign-sbp');
    Route::get('report-campaign-sbp/data', [ReportController::class, 'reportCampaignSbpData'])->name('report-campaign-sbp.data');
    Route::get('report-campaign-sbp/export', [ReportController::class, 'exportCampaignSbp'])->name('report-campaign-sbp.export');
    Route::get('report-saldo-sbp', [ReportController::class, 'reportSaldoSbp'])->name('report-saldo-sbp');
    Route::get('report-saldo-sbp/data', [ReportController::class, 'reportSaldoSbpData'])->name('report-saldo-sbp.data');
    Route::get('report-saldo-sbp/export', [ReportController::class, 'exportSaldoSbp'])->name('report-saldo-sbp.export');
    Route::get('report-saldo-advertising', [ReportController::class, 'reportSaldoAdvertising'])->name('report-saldo-advertising');
    Route::get('report-saldo-advertising/data', [ReportController::class, 'reportSaldoAdvertisingData'])->name('report-saldo-advertising.data');
    Route::get('report-saldo-advertising/export', [ReportController::class, 'exportSaldoAdvertising'])->name('report-saldo-advertising.export');
    Route::get('export-mitra-sbp', [ReportController::class, 'exportMitraSBP'])->name('export.mitra-sbp');
    Route::get('export-agency', [ReportController::class, 'exportAgency'])->name('export.agency');
    Route::get('export-internal', [ReportController::class, 'exportInternal'])->name('export.internal');
    Route::get('export-performance-all', [ReportController::class, 'exportPerformanceAll'])->name('export.performance-all');
});

Route::middleware(['auth', 'checkrole:Admin'])->group(function () {
    Route::get('report-balance-top-up', [ReportController::class, 'reportBalanceTopUp'])->name('report-balance-top-up');
    Route::get('report-balance-top-up/data', [ReportController::class, 'reportBalanceTopUpData'])->name('report-balance-top-up.data');
});

// Routes untuk Presensi CVSR
Route::middleware(['auth', 'checkrole:cvsr'])->prefix('presensi')->name('presensi.')->group(function () {
    Route::get('/', [PresensiController::class, 'index'])->name('index');
    Route::post('/clock-in', [PresensiController::class, 'clockIn'])->name('clock_in');
    Route::post('/clock-out', [PresensiController::class, 'clockOut'])->name('clock_out');
    Route::post('/izin', [PresensiController::class, 'izin'])->name('izin');
});

// Routes untuk Riwayat Presensi (Admin dan CVSR)
Route::middleware(['auth', 'checkrole:Admin,cvsr'])->prefix('presensi')->name('presensi.')->group(function () {
    Route::get('/riwayat', [PresensiController::class, 'riwayat'])->name('riwayat');
    Route::get('/foto/{filePath}', [PresensiController::class, 'getFoto'])->name('foto')->where('filePath', '.*');
});

// Routes untuk Kelola Lokasi Presensi CVSR (Hanya Admin)
Route::middleware(['auth', 'checkrole:Admin'])->prefix('location-presensi')->name('location-presensi.')->group(function () {
    Route::get('/', [LocationPresensiController::class, 'index'])->name('index');
    Route::post('/', [LocationPresensiController::class, 'store'])->name('store');
    Route::put('/{id}', [LocationPresensiController::class, 'update'])->name('update');
    Route::delete('/{id}', [LocationPresensiController::class, 'destroy'])->name('destroy');
    Route::patch('/{id}/toggle-location-needed', [LocationPresensiController::class, 'toggleLocationNeeded'])->name('toggle-location-needed');
});

Route::middleware(['auth', 'checkrole:Admin'])->prefix('configuration')->name('configuration.')->group(function () {
    Route::get('/mitra-sbp', [MitraSbpController::class, 'index'])->name('mitra-sbp.index');
    Route::get('/mitra-sbp/create', [MitraSbpController::class, 'create'])->name('mitra-sbp.create');
    Route::post('/mitra-sbp', [MitraSbpController::class, 'store'])->name('mitra-sbp.store');
    Route::get('/mitra-sbp/{id}/edit', [MitraSbpController::class, 'edit'])->name('mitra-sbp.edit');
    Route::put('/mitra-sbp/{id}', [MitraSbpController::class, 'update'])->name('mitra-sbp.update');
    Route::delete('/mitra-sbp/{id}', [MitraSbpController::class, 'destroy'])->name('mitra-sbp.destroy');
});
