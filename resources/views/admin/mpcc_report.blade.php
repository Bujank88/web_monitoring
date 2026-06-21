@extends('master')
@section('title') MPCC Report @endsection
@section('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet"/>

<style>
    #loading-overlay {

        position: fixed;

        top: 0;

        left: 0;

        width: 100%;

        height: 100%;

        background: rgba(0, 0, 0, 0.7);

        z-index: 9999;

        display: none;

    }

    /* Dashboard Cards */
    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2);
    }

    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }

    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }

    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }

    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }

    .border-left-danger {
        border-left: 0.25rem solid #e74a3b !important;
    }

    .border-left-secondary {
        border-left: 0.25rem solid #858796 !important;
    }

    .border-left-dark {
        border-left: 0.25rem solid #5a5c69 !important;
    }

    .bg-gradient-primary {
        background: linear-gradient(87deg, #4e73df 0, #224abe 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(87deg, #1cc88a 0, #169b6b 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(87deg, #36b9cc 0, #258391 100%);
    }

    .bg-gradient-danger {
        background: linear-gradient(87deg, #e74a3b 0, #be2617 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(87deg, #f6c23e 0, #dda20a 100%);
    }

    .text-xs {
        font-size: 0.75rem;
    }

    .text-gray-800 {
        color: #5a5c69 !important;
    }

    .text-gray-300 {
        color: #dddfeb !important;
    }

    .text-white-50 {
        color: rgba(255, 255, 255, 0.5) !important;
    }

    .bg-white-50 {
        background-color: rgba(255, 255, 255, 0.5) !important;
    }

    .badge-purple {
        background-color: #6f42c1;
        color: white;
    }

    .btn-group-vertical .btn {
        border-radius: 0.25rem;
        margin-bottom: 0.25rem;
    }

    /* Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translate3d(0, 40px, 0);
        }

        to {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }
    }

    .card {
        animation: fadeInUp 0.6s ease-out;
    }

    /* Responsive */
    @media (max-width: 768px) {

        .h3,
        .h4,
        .h5 {
            font-size: 1.2rem;
        }

        .fa-2x {
            font-size: 1.5em;
        }

        .btn-group-vertical .btn {
            font-size: 0.8rem;
            padding: 0.375rem 0.75rem;
        }
    }

    /* Enhanced Table Styling */
    .table {
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        margin-top: 15px;
        border-collapse: separate;
        border-spacing: 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px !important;
        font-size: 13px;
        font-weight: 600;
        text-align: center !important;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table td {
        padding: 12px !important;
        font-size: 13px;
        border-bottom: 1px solid #e9ecef;
        color: #495057;
        text-align: center !important;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9ff;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }

    .table tbody tr:nth-child(odd) {
        background-color: #f9f9fc;
    }

    .table tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    /* Kolom Nomor */
    #powerHouseTable tbody tr td:nth-child(1) {
        font-weight: 600;
        color: #667eea;
        background-color: #f0f2f9;
        width: 5%;
    }

    /* Kolom Referral Code */
    #powerHouseTable tbody tr td:nth-child(4) {
        font-weight: 600;
        color: #333;
        letter-spacing: 0.5px;
    }

    /* Kolom Nama MPCC */
    #powerHouseTable tbody tr td:nth-child(5) {
        font-weight: 500;
        color: #333;
    }

    /* Kolom Jumlah Leads */
    #powerHouseTable tbody tr td:nth-child(6) {
        background-color: #e3f2fd;
        /* color: #1976d2; */
        font-weight: 600;
    }

    /* Kolom Jumlah Akun */
    #powerHouseTable tbody tr td:nth-child(7) {
        background-color: #f3e5f5;
        color: #6a1b9a;
        font-weight: 600;
        font-size: 12px;
    }

    /* Kolom New Akun to Leads */
    #powerHouseTable tbody tr td:nth-child(8) {
        background-color: #e8f5e9;
        color: #1b5e20;
        font-weight: 600;
        font-size: 12px;
    }

    /* Kolom Top Up */
    #powerHouseTable tbody tr td:nth-child(9) {
        background: linear-gradient(135deg, #fff5e1 0%, #ffe0b2 100%);
        color: #e65100;
        font-weight: 600;
        font-size: 12px;
    }

    /* Kolom Tanggal Transaksi Terakhir */
    #powerHouseTable tbody tr td:nth-child(10) {
        /* background: linear-gradient(135deg, #c8e6c9 0%, #81c784 100%); */
        color: #2e7d32;
        font-weight: 600;
        font-size: 12px;
    }

    /* Highlight untuk persentase tanpa nilai */
    #powerHouseTable tbody tr td:nth-child(8):contains("-") {
        background: linear-gradient(135deg, #ffebee 0%, #ef9a9a 100%);
        color: #c62828;
    }

    /* DataTables wrapper styling */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }

    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label {
        font-weight: 500;
        color: #495057;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 6px 10px;
        font-size: 13px;
    }

    /* Custom Select2 Styling */
    .select2-container--bootstrap-5 .select2-selection {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        min-height: 38px;
        padding: 0.375rem 0.75rem;
        font-size: 0.9rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .select2-container--bootstrap-5 .select2-selection:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        padding-left: 0;
        color: #212529;
        line-height: 1.5;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 8px;
    }

    .select2-dropdown {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .select2-container--bootstrap-5 .select2-results__option--highlighted {
        background-color: #0d6efd;
        color: #fff;
    }

    .select2-container--bootstrap-5 .select2-results__option--selected {
        background-color: #e7f1ff;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-label i {
        margin-right: 5px;
        color: #6c757d;
    }

    /* Filter Card Styling */
    .filter-section {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
    }

    .quick-nav-section {
        background: #fff;
        padding: 1rem;
        border-radius: 0.375rem;
        border: 1px solid #dee2e6;
    }

    /* Button Styling */
    .btn-group-custom .btn {
        margin-bottom: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-group-custom .btn:hover {
        transform: translateX(5px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* Input Month Styling */
    input[type="month"] {
        cursor: pointer;
        padding: 0.5rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        transition: all 0.15s ease-in-out;
    }

    input[type="month"]:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    /* Divider */
    .divider {
        height: 1px;
        background: linear-gradient(to right, transparent, #dee2e6, transparent);
        margin: 1rem 0;
    }

    /* Quick Navigation in Header */
    .btn-light {
        transition: all 0.3s ease;
        padding: 0.5rem 0.25rem;
        font-weight: 500;
    }

    .btn-light:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .btn-light i {
        font-size: 1.2rem;
    }

    .btn-light small {
        font-size: 0.75rem;
        display: block;
    }

    @media (max-width: 768px) {
        .btn-light i {
            font-size: 1rem;
        }
        .btn-light small {
            font-size: 0.65rem;
        }

        .table th,
        .table td {
            padding: 8px !important;
            font-size: 11px;
        }
    }

    /* Header dengan flexbox untuk download button */
    .card-header.d-flex {
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
    }

    .card-header .btn-light {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .card-header .btn-light:hover {
        background-color: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        color: white;
        transform: translateY(-2px);
    }

    .card-header .btn-light i {
        margin-right: 5px;
    }

    /* Button Actions Styling */
    .card-header .btn-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .card-header .btn-success-custom {
        background-color: #28a745;
        color: white;
        border: 1px solid #28a745;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        white-space: nowrap;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
    }

    .card-header .btn-success-custom:hover {
        background-color: #218838;
        border-color: #1e7e34;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    }

    .card-header .btn-success-custom i {
        margin-right: 6px;
    }

    /* Filter Button Styling */
    .btn-outline-primary {
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
    }

    /* Gap utility class */
    .gap-2 {
        gap: 8px;
    }

    /* Match Report Canvasser grouped-table style */
    #powerHousePerformanceTable {
        border: 0.5px solid #ccc;
        box-shadow: none;
    }

    #powerHousePerformanceTable th,
    #powerHousePerformanceTable td {
        border: 0.5px solid #ccc !important;
    }

    #powerHousePerformanceTable tbody tr:nth-child(odd) {
        background-color: #f2f2f2;
    }

    #powerHousePerformanceTable tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    #powerHousePerformanceTable {
        table-layout: auto;
        min-width: 1500px;
    }

    #capturePowerHousePerformanceTable {
        border: 1px solid #d8dee9;
        border-radius: 8px;
        background: #fff;
    }

    #powerHousePerformanceTable thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: #ffffff !important;
        vertical-align: middle;
        white-space: nowrap;
        line-height: 1.25;
    }

    #powerHousePerformanceTable tbody td,
    #powerHousePerformanceTable tfoot td {
        vertical-align: middle;
        white-space: nowrap;
    }

    #powerHousePerformanceTable .target-col {
        background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
        color: #7a5d00;
        font-weight: 700;
    }

    #powerHousePerformanceTable .visit-col {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        color: #0d47a1;
        font-weight: 700;
    }

    #powerHousePerformanceTable tbody td:nth-child(7),
    #powerHousePerformanceTable tbody td:nth-child(8) {
        background-color: #d1e7dd;
        font-weight: 600;
    }

    #powerHousePerformanceTable tbody td:nth-child(9),
    #powerHousePerformanceTable tbody td:nth-child(10),
    #powerHousePerformanceTable tbody td:nth-child(11),
    #powerHousePerformanceTable tbody td:nth-child(12) {
        background-color: #f8d7da;
        font-weight: 600;
    }

    #powerHousePerformanceTable tbody td:nth-child(13),
    #powerHousePerformanceTable tbody td:nth-child(14),
    #powerHousePerformanceTable tbody td:nth-child(15),
    #powerHousePerformanceTable tbody td:nth-child(16) {
        background-color: #d3ffcd;
        font-weight: 600;
    }

    #powerHouseAreaSummaryTable {
        border: 0.5px solid #ccc;
        box-shadow: none;
    }

    #powerHouseAreaSummaryTable th,
    #powerHouseAreaSummaryTable td {
        border: 0.5px solid #ccc !important;
    }

    #powerHouseAreaSummaryTable tbody tr:nth-child(odd) {
        background-color: #f2f2f2;
    }

    #powerHouseAreaSummaryTable tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    #powerHouseAreaSummaryTable tbody td:nth-child(3) {
        background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
        color: #7a5d00;
        font-weight: 600;
    }

    #powerHouseAreaSummaryTable tbody td:nth-child(4) {
        background-color: #e3f2fd;
        /* color: #1976d2; */
        font-weight: 600;
    }

    #powerHouseAreaSummaryTable tbody td:nth-child(5),
    #powerHouseAreaSummaryTable tbody td:nth-child(6) {
        background-color: #d1e7dd;
        font-weight: 600;
    }

    #powerHouseAreaSummaryTable tbody td:nth-child(7),
    #powerHouseAreaSummaryTable tbody td:nth-child(8),
    #powerHouseAreaSummaryTable tbody td:nth-child(9),
    #powerHouseAreaSummaryTable tbody td:nth-child(10) {
        background-color: #f8d7da;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

<!-- Filter Section -->
{{-- <div class="row mb-3">
    <div class="col-12 d-flex justify-content-end align-items-center gap-2">
        <select id="filterMonthPH" name="filterMonthPH" class="form-control" style="background-color: #313131; color: white; min-width: 180px; max-width: 200px;">
            @foreach ($months as $month)
            <option value="{{ $month['value'] }}" {{ $month['selected'] ? 'selected' : '' }}>
                {{ $month['label'] }}
            </option>
            @endforeach
        </select>
        <button type="button" id="btnSavePowerHouseImage" class="btn btn-success" title="Save as Image" style="padding: 6px 12px; white-space: nowrap;">
            <i class="fas fa-image mr-2"></i> Save Image
        </button>
        <a href="{{ route('mpcc.report.export') }}" class="btn btn-success" title="Download Excel" style="padding: 6px 12px; white-space: nowrap;">
            <i class="fas fa-file-excel mr-2"></i> Download Excel
        </a>
    </div>
</div> --}}

@php
    use Carbon\Carbon;

    $startDate = request('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
    $endDate   = request('end_date', Carbon::now()->format('Y-m-d'));
    $momRefDate = Carbon::today();
    $prevSameDay = $momRefDate->copy()->subMonthNoOverflow();
    $prevMonthEndDay = $momRefDate->copy()->subMonthNoOverflow()->endOfMonth()->day;
    $prevRemainingStartDay = $prevSameDay->day + 1;
@endphp

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-end align-items-center gap-2">

        <!-- Start Date -->
        <input type="date" id="startDatePH" name="start_date" class="form-control" value="{{ $startDate }}"
            style="background-color: #313131; color: white; min-width: 160px; max-width: 180px;"
        >

        <!-- End Date -->
        <input type="date" id="endDatePH" name="end_date" class="form-control" value="{{ $endDate }}"
            style="background-color: #313131; color: white; min-width: 160px; max-width: 180px;"
        >

        <!-- Save Image -->
        <button type="button" id="btnSavePowerHouseImage" class="btn btn-success" title="Save as Image">
            <i class="fas fa-image mr-2"></i> Save Image
        </button>

        <!-- Download Excel -->
        <a
            {{-- href="{{ route('export.powerhouse_voucher', [
                'start_date' => $startDate,
                'end_date'   => $endDate
            ]) }}" --}}
            id="btnExportPowerHouse"
            class="btn btn-success"
            title="Download Excel"
        >
            <i class="fas fa-file-excel mr-2"></i> Download Excel
        </a>

    </div>
</div>


<!-- Report PowerHouse Referral -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="powerHouseTableCard">
            <div class="card-header bg-gradient-danger text-white">
                <h4 class="mb-0"><i class="fas fa-table"></i> MPCC Report</h4>
            </div>
            <div class="card-body">
                <div id="capturePowerHouseTable" class="table-responsive">
            <table class="table table-sm w-100 table-bordered table-hover" id="powerHouseTable" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr style="background-color: #e8eaf6; font-weight: bold;">
                                <th colspan="10" style="text-align: center; padding: 10px; border-bottom: 2px solid #667eea;">MPCC Report | <span class="displayedStartDatePH">{{ $startDate }}</span> s/d <span class="displayedEndDatePH">{{ $endDate }}</span></th>
                            </tr>
                            <tr>
                                <th style="text-align: center; width: 5%;">No</th>
                                <th style="text-align: center;">Area</th>
                                <th style="text-align: center;">Cluster</th>
                                <th style="text-align: center;">Referral Code</th>
                                <th style="text-align: center;">Nama MPCC</th>
                                <th style="text-align: center;">Jumlah Leads</th>
                                <th style="text-align: center;">Jumlah Akun</th>
                                <th style="text-align: center;">new akun to leads (%)</th>
                                <th style="text-align: center;">Top Up</th>
                                <th style="text-align: center;">Tgl Transaksi Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr style="background: linear-gradient(135deg, #fffb00 0%, #ffee00 100%); color: white; font-weight: 600;">
                                <td colspan="5" style="text-align: right; padding: 12px;">TOTAL</td>
                                <td id="totalJumlahLeads" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalJumlahAkun" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalPercentageNewAkunToLead" style="text-align: center; padding: 12px;">0%</td>
                                <td id="totalTopUp" style="text-align: center; padding: 12px;">0</td>
                                <td style="text-align: center; padding: 12px;">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report PowerHouse Deal Top Up & MOM -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="powerHousePerformanceCard">
            <div class="card-header bg-gradient-info text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-chart-line"></i> Report MPCC Deal Top Up & MOM</h4>
                <button type="button" id="btnSaveMpccPerformanceImage" class="btn btn-success btn-sm" title="Save as Image">
                    <i class="fas fa-image mr-2"></i> Save Image
                </button>
            </div>
            <div class="card-body">
                <div id="capturePowerHousePerformanceTable" class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="powerHousePerformanceTable" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr style="background-color: #e8eaf6; font-weight: bold;">
                                <th colspan="16" style="text-align: center; padding: 10px; border-bottom: 2px solid #667eea;">Data Topup & MOM MPCC | <span class="displayedStartDatePH">{{ $startDate }}</span> s/d <span class="displayedEndDatePH">{{ $endDate }}</span></th>
                            </tr>
                            <tr>
                                <th rowspan="2" style="text-align: center; width: 5%;">No</th>
                                <th rowspan="2" style="text-align: center;">Area</th>
                                <th rowspan="2" style="text-align: center;">Cluster</th>
                                <th rowspan="2" style="text-align: center;">Nama MPCC</th>
                                <th rowspan="2" class="target-col" style="text-align: center; background-color: #ffe8a1;">Target (Rp.)</th>
                                <th rowspan="2" class="visit-col" style="text-align: center; background-color: #e3f2fd;">Total Visit</th>
                                <th colspan="2" style="text-align: center; background-color: #d1e7dd;">Deal Top Up (New Akun & Eksisting Akun)</th>
                                <th colspan="4" style="text-align: center; background-color: #f8d7da;">Top Up (Rp.)</th>
                                <th colspan="4" style="text-align: center; background-color: #d3ffcd;">MOM</th>
                            </tr>
                            <tr>
                                <th style="text-align: center; background-color: #d1e7dd;">New Akun</th>
                                <th style="text-align: center; background-color: #d1e7dd;">Eksisting Akun</th>
                                <th style="text-align: center; background-color: #f8d7da;">New Akun(Rp.)</th>
                                <th style="text-align: center; background-color: #f8d7da;">Eksisting Akun(Rp.)</th>
                                <th style="text-align: center; background-color: #f8d7da;">Total (Rp.)</th>
                                <th style="text-align: center; background-color: #f8d7da;">Acv (%)</th>
                                <th id="momHeaderPrevPartial" style="text-align: center; background-color: #d3ffcd;">1 &ndash; {{ $prevSameDay->day }} {{ $prevSameDay->translatedFormat('M') }}</th>
                                <th id="momHeaderCurrentPartial" style="text-align: center; background-color: #d3ffcd;">1 &ndash; {{ $momRefDate->day }} {{ $momRefDate->translatedFormat('M') }}</th>
                                <th id="momHeaderPrevRemaining" style="text-align: center; background-color: #d3ffcd;">{{ $prevRemainingStartDay }} &ndash; {{ $prevMonthEndDay }} {{ $prevSameDay->translatedFormat('M') }}</th>
                                <th style="text-align: center; background-color: #d3ffcd;">Gap (Rp)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr style="background: #fff200; color: #1f2937; font-weight: 700;">
                                <td colspan="4" style="text-align: right; padding: 12px;">TOTAL</td>
                                <td id="totalTargetPerformance" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalVisitPerformance" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalDealTopupNewAkun" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalDealTopupExistingAkun" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalTopUpNewAkunRp" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalTopUpExistingAkunRp" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalTopUpPerformance" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalAcvPerformance" style="text-align: center; padding: 12px;">0%</td>
                                <td id="totalMomPrevPartial" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalMomCurrentPartial" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalMomPrevRemaining" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalMomGap" style="text-align: center; padding: 12px;">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="powerHouseAreaSummaryCard">
            <div class="card-header bg-gradient-primary text-white">
                <h4 class="mb-0"><i class="fas fa-layer-group"></i> Summary MPCC Per Area</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="powerHouseAreaSummaryTable" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr style="background-color: #e8eaf6; font-weight: bold;">
                                <th colspan="10" style="text-align: center; padding: 10px; border-bottom: 2px solid #667eea;">Summary Topup MPCC Per Area | <span class="displayedStartDatePH">{{ $startDate }}</span> s/d <span class="displayedEndDatePH">{{ $endDate }}</span></th>
                            </tr>
                            <tr>
                                <th rowspan="2" style="text-align: center; width: 5%;">No</th>
                                <th rowspan="2" style="text-align: center;">Area</th>
                                <th rowspan="2" style="text-align: center; background-color: #ffe8a1;">Target (Rp.)</th>
                                <th rowspan="2" style="text-align: center; background-color: #e3f2fd; ">Jumlah Leads</th>
                                <th colspan="2" style="text-align: center; background-color: #d1e7dd;">Deal Top Up (New Akun &amp; Eksisting Akun)</th>
                                <th colspan="4" style="text-align: center; background-color: #f8d7da;">Top Up (Rp.)</th>
                            </tr>
                            <tr>
                                <th style="text-align: center; background-color: #d1e7dd;">New Akun</th>
                                <th style="text-align: center; background-color: #d1e7dd;">Eksisting Akun</th>
                                <th style="text-align: center; background-color: #f8d7da;">New Akun(Rp.)</th>
                                <th style="text-align: center; background-color: #f8d7da;">Eksisting Akun(Rp.)</th>
                                <th style="text-align: center; background-color: #f8d7da;">Total (Rp.)</th>
                                <th style="text-align: center; background-color: #f8d7da;">Acv (%)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr style="background: linear-gradient(135deg, #fffb00 0%, #ffee00 100%); color: white; font-weight: 600;">
                                <td colspan="2" style="text-align: right; padding: 12px;">TOTAL</td>
                                <td id="totalAreaSummaryTarget" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalAreaSummaryLeads" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalAreaSummaryNewAkun" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalAreaSummaryExistingAkun" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalAreaSummaryNewAkunRp" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalAreaSummaryExistingAkunRp" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalAreaSummaryTopup" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalAreaSummaryAcv" style="text-align: center; padding: 12px;">0%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Last Updated Info -->
<div class="row">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body text-center">
                <small class="text-muted">
                    <i class="fas fa-clock"></i> Last updated: {{ now()->format('d F Y, H:i:s') }} WIB
                </small>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables
        var table = $('#powerHouseTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            ajax: {
                url: "{{ route('mpcc.report.data') }}",
                type: 'GET',
                data: function(d) {
                    // d.month = $('#filterMonthPH').val();
                    d.start_date = $('#startDatePH').val();
                    d.end_date = $('#endDatePH').val();
                }
            },

            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'area', name: 'area', className: 'text-center' },
                { data: 'branch', name: 'branch', className: 'text-center' },
                { data: 'referral_code', name: 'referral_code', className: 'text-center' },
                { data: 'team_powerhouse', name: 'team_powerhouse', className: 'text-center' },
                { data: 'jumlah_leads', name: 'jumlah_leads', className: 'text-center' },
                { data: 'jumlah_akun', name: 'jumlah_akun', className: 'text-center' },
                { data: 'percentage_new_akun_to_lead', name: 'percentage_new_akun_to_lead', className: 'text-center' },
                { data: 'total_topup', name: 'total_topup', className: 'text-center' },
                { data: 'tgl_transaksi_terakhir', name: 'tgl_transaksi_terakhir', className: 'text-center' }
            ],
            rowCallback: function(row, data, index) {
                applyPercentageCellStyle($('td', row).eq(7), data.percentage_new_akun_to_lead);

                // Calculate totals after each row is rendered
                calculateTotals();
            },
            drawCallback: function() {
                // Recalculate totals when table is fully drawn
                calculateTotals();
            }
        });

        var performanceTable = $('#powerHousePerformanceTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            ajax: {
                url: "{{ route('mpcc.report.performance-data') }}",
                type: 'GET',
                data: function(d) {
                    d.start_date = $('#startDatePH').val();
                    d.end_date = $('#endDatePH').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'area', name: 'area', className: 'text-center' },
                { data: 'branch', name: 'branch', className: 'text-center' },
                { data: 'team_powerhouse', name: 'team_powerhouse', className: 'text-center' },
                { data: 'target', name: 'target', className: 'text-center' },
                { data: 'jumlah_visit', name: 'jumlah_visit', className: 'text-center' },
                { data: 'deal_topup_new_akun', name: 'deal_topup_new_akun', className: 'text-center' },
                { data: 'deal_topup_existing_akun', name: 'deal_topup_existing_akun', className: 'text-center' },
                { data: 'top_up_new_akun_rp', name: 'top_up_new_akun_rp', className: 'text-center' },
                { data: 'top_up_existing_akun_rp', name: 'top_up_existing_akun_rp', className: 'text-center' },
                { data: 'total_topup', name: 'total_topup', className: 'text-center' },
                { data: 'acv', name: 'acv', className: 'text-center' },
                { data: 'mom_prev_partial', name: 'mom_prev_partial', className: 'text-center' },
                { data: 'mom_current_partial', name: 'mom_current_partial', className: 'text-center' },
                { data: 'mom_prev_remaining', name: 'mom_prev_remaining', className: 'text-center' },
                { data: 'mom_gap', name: 'mom_gap', className: 'text-center' }
            ],
            createdRow: function(row) {
                $('td', row).eq(4).css({
                    'background': 'linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%)',
                    'color': '#7a5d00',
                    'font-weight': '600'
                });
                $('td', row).eq(5).css({
                    'background': 'linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%)',
                    'color': '#0d47a1',
                    'font-weight': '600'
                });
            },
            rowCallback: function(row, data) {
                applyPercentageCellStyle($('td', row).eq(11), data.acv);
            },
            drawCallback: function() {
                calculatePerformanceTotals();
            }
        });

        var areaSummaryTable = $('#powerHouseAreaSummaryTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            ajax: {
                url: "{{ route('mpcc.report.area-summary-data') }}",
                type: 'GET',
                data: function(d) {
                    d.start_date = $('#startDatePH').val();
                    d.end_date = $('#endDatePH').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'area', name: 'area', className: 'text-center' },
                { data: 'target', name: 'target', className: 'text-center' },
                { data: 'jumlah_leads', name: 'jumlah_leads', className: 'text-center' },
                { data: 'deal_topup_new_akun', name: 'deal_topup_new_akun', className: 'text-center' },
                { data: 'deal_topup_existing_akun', name: 'deal_topup_existing_akun', className: 'text-center' },
                { data: 'top_up_new_akun_rp', name: 'top_up_new_akun_rp', className: 'text-center' },
                { data: 'top_up_existing_akun_rp', name: 'top_up_existing_akun_rp', className: 'text-center' },
                { data: 'total_topup', name: 'total_topup', className: 'text-center' },
                { data: 'acv', name: 'acv', className: 'text-center' }
            ],
            rowCallback: function(row, data) {
                applyPercentageCellStyle($('td', row).eq(9), data.acv);
            },
            drawCallback: function() {
                calculateAreaSummaryTotals();
            }
        });

        // Handle month filter change
        // $('#filterMonthPH').on('change', function() {
        //     // Update label bulan yang ditampilkan dengan text dari selected option
        //     var selectedText = $('#filterMonthPH option:selected').text();
        //     $('#displayedMonthPH').text(selectedText);
        //     // Reload data table
        //     table.ajax.reload();
        // });
        $('#startDatePH').on('change', function () {
            let startDate = $(this).val();

            if (startDate) {
                $('#endDatePH').attr('min', startDate);

                if ($('#endDatePH').val() && $('#endDatePH').val() < startDate) {
                    $('#endDatePH').val('');
                }
            }

            table.ajax.reload();
            performanceTable.ajax.reload();
            areaSummaryTable.ajax.reload();
            updateMomHeaders();
            updateFilterPeriodHeader();
        });

        $('#endDatePH').on('change', function () {
            let endDate = $(this).val();

            if (endDate) {
                $('#startDatePH').attr('max', endDate);

                if ($('#startDatePH').val() && $('#startDatePH').val() > endDate) {
                    $('#startDatePH').val('');
                }
            }

            table.ajax.reload();
            performanceTable.ajax.reload();
            areaSummaryTable.ajax.reload();
            updateMomHeaders();
            updateFilterPeriodHeader();
        });

        $('#btnExportPowerHouse').on('click', function (e) {
            e.preventDefault();

            const baseUrl = "{{ route('mpcc.report.export') }}";
            const startDate = $('#startDatePH').val();
            const endDate = $('#endDatePH').val();
            const query = new URLSearchParams();

            if (startDate) {
                query.append('start_date', startDate);
            }
            if (endDate) {
                query.append('end_date', endDate);
            }
            alert(query)

            window.location.href = query.toString() ? `${baseUrl}?${query.toString()}` : baseUrl;
        });

        function getPercentageStyle(percent) {
            let backgroundColor = percent >= 100 ? '#2e7d32' : (percent >= 75 ? '#f9a825' : '#c62828');
            let textColor = percent >= 75 && percent < 100 ? '#1f2937' : '#ffffff';

            return {
                'background': `linear-gradient(135deg, ${backgroundColor} 0%, ${backgroundColor}cc 100%)`,
                'color': textColor,
                'font-weight': '700'
            };
        }

        function applyPercentageCellStyle(cell, rawValue) {
            const percent = typeof rawValue === 'number'
                ? rawValue
                : parseFloat(String(rawValue || '0').replace('%', '').replace(/\./g, '').replace(',', '.')) || 0;

            cell.css(getPercentageStyle(percent));
        }

        // Function to calculate totals
        function calculateTotals() {
            let totalAkun = 0;
            let totalLeads = 0;
            let totalPercentageNewAkunToLead = 0;
            let totalTopup = 0;

            table.rows().data().each(function(row) {
                totalAkun += parseInt(row.jumlah_akun) || 0;
                totalLeads += parseInt(row.jumlah_leads) || 0;

                const topupText = String(row.total_topup || '').trim();
                const topupMatch = topupText.match(/[\d.,]+/);
                if (topupMatch) {
                    let topupValue = topupMatch[0].replace(/\./g, '').replace(/,/g, '.');
                    totalTopup += parseFloat(topupValue) || 0;
                }

            });

            totalPercentageNewAkunToLead = totalLeads > 0 ? (totalAkun / totalLeads) * 100 : 0;

            $('#totalJumlahAkun').text(totalAkun);
            $('#totalJumlahLeads').text(totalLeads);
            $('#totalPercentageNewAkunToLead').text(totalPercentageNewAkunToLead.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + '%');
            applyPercentageCellStyle($('#totalPercentageNewAkunToLead'), totalPercentageNewAkunToLead);
            
            // Format total topup as currency
            if (totalTopup > 0) {
                const topupFormatted = 'Rp ' + Math.floor(totalTopup).toLocaleString('id-ID');
                $('#totalTopUp').text(topupFormatted);
            } else {
                $('#totalTopUp').text('Rp 0');
            }
        }

        function calculatePerformanceTotals() {
            let totalDealTopupNewAkun = 0;
            let totalDealTopupExistingAkun = 0;
            let totalTopUpNewAkunRp = 0;
            let totalTopUpExistingAkunRp = 0;
            let totalTopup = 0;
            let totalTarget = 0;
            let totalVisit = 0;
            let totalAcv = 0;
            let totalMomPrevPartial = 0;
            let totalMomCurrentPartial = 0;
            let totalMomPrevRemaining = 0;
            let totalMomGap = 0;

            const parseNumber = (text) => {
                if (!text) return 0;
                const normalized = text.replace(/\./g, '').replace(/,/g, '.').replace(/[^\d.-]/g, '');
                return parseFloat(normalized) || 0;
            };

            $('#powerHousePerformanceTable tbody tr').each(function() {
                const cells = $(this).find('td');

                totalTarget += parseNumber(cells.eq(4).text().trim());
                totalVisit += parseInt(cells.eq(5).text().trim()) || 0;
                totalDealTopupNewAkun += parseInt(cells.eq(6).text().trim()) || 0;
                totalDealTopupExistingAkun += parseInt(cells.eq(7).text().trim()) || 0;

                totalTopUpNewAkunRp += parseNumber(cells.eq(8).text().trim());
                totalTopUpExistingAkunRp += parseNumber(cells.eq(9).text().trim());
                totalTopup += parseNumber(cells.eq(10).text().trim());

                totalMomPrevPartial += parseNumber(cells.eq(12).text().trim());
                totalMomCurrentPartial += parseNumber(cells.eq(13).text().trim());
                totalMomPrevRemaining += parseNumber(cells.eq(14).text().trim());
                totalMomGap += parseNumber(cells.eq(15).text().trim());
            });

            $('#totalDealTopupNewAkun').text(totalDealTopupNewAkun);
            $('#totalDealTopupExistingAkun').text(totalDealTopupExistingAkun);
            $('#totalTargetPerformance').text('Rp ' + Math.floor(totalTarget).toLocaleString('id-ID'));
            $('#totalVisitPerformance').text(totalVisit.toLocaleString('id-ID'));
            $('#totalTopUpNewAkunRp').text(Math.floor(totalTopUpNewAkunRp).toLocaleString('id-ID'));
            $('#totalTopUpExistingAkunRp').text(Math.floor(totalTopUpExistingAkunRp).toLocaleString('id-ID'));
            $('#totalTopUpPerformance').text('Rp ' + Math.floor(totalTopup).toLocaleString('id-ID'));
            totalAcv = totalTarget > 0 ? (totalTopup / totalTarget) * 100 : 0;
            $('#totalAcvPerformance').text(totalAcv.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%');
            applyPercentageCellStyle($('#totalAcvPerformance'), totalAcv);
            $('#totalMomPrevPartial').text(Math.floor(totalMomPrevPartial).toLocaleString('id-ID'));
            $('#totalMomCurrentPartial').text(Math.floor(totalMomCurrentPartial).toLocaleString('id-ID'));
            $('#totalMomPrevRemaining').text(Math.floor(totalMomPrevRemaining).toLocaleString('id-ID'));
            $('#totalMomGap').text(Math.floor(totalMomGap).toLocaleString('id-ID'));
        }

        function calculateAreaSummaryTotals() {
            let totalLeads = 0;
            let totalTarget = 0;
            let totalNewAkun = 0;
            let totalExistingAkun = 0;
            let totalNewAkunRp = 0;
            let totalExistingAkunRp = 0;
            let totalTopup = 0;

            const parseNumber = (text) => {
                if (!text) return 0;
                const normalized = text.replace(/\./g, '').replace(/,/g, '.').replace(/[^\d.-]/g, '');
                return parseFloat(normalized) || 0;
            };

            $('#powerHouseAreaSummaryTable tbody tr').each(function() {
                const cells = $(this).find('td');

                totalTarget += parseNumber(cells.eq(2).text().trim());
                totalLeads += parseInt(cells.eq(3).text().trim()) || 0;
                totalNewAkun += parseInt(cells.eq(4).text().trim()) || 0;
                totalExistingAkun += parseInt(cells.eq(5).text().trim()) || 0;
                totalNewAkunRp += parseNumber(cells.eq(6).text().trim());
                totalExistingAkunRp += parseNumber(cells.eq(7).text().trim());
                totalTopup += parseNumber(cells.eq(8).text().trim());
            });

            const totalAcv = totalTarget > 0 ? (totalTopup / totalTarget) * 100 : 0;

            $('#totalAreaSummaryTarget').text('Rp ' + Math.floor(totalTarget).toLocaleString('id-ID'));
            $('#totalAreaSummaryLeads').text(totalLeads);
            $('#totalAreaSummaryNewAkun').text(totalNewAkun);
            $('#totalAreaSummaryExistingAkun').text(totalExistingAkun);
            $('#totalAreaSummaryNewAkunRp').text(Math.floor(totalNewAkunRp).toLocaleString('id-ID'));
            $('#totalAreaSummaryExistingAkunRp').text(Math.floor(totalExistingAkunRp).toLocaleString('id-ID'));
            $('#totalAreaSummaryTopup').text('Rp ' + Math.floor(totalTopup).toLocaleString('id-ID'));
            $('#totalAreaSummaryAcv').text(totalAcv.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%');
            applyPercentageCellStyle($('#totalAreaSummaryAcv'), totalAcv);
        }

        function getSubMonthNoOverflow(dateObj) {
            const year = dateObj.getFullYear();
            const month = dateObj.getMonth();
            const day = dateObj.getDate();
            const targetMonthDate = new Date(year, month - 1, 1);
            const lastDayTargetMonth = new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth() + 1, 0).getDate();
            const safeDay = Math.min(day, lastDayTargetMonth);
            return new Date(targetMonthDate.getFullYear(), targetMonthDate.getMonth(), safeDay);
        }

        function monthShortId(dateObj) {
            return dateObj.toLocaleString('id-ID', { month: 'short' }).replace('.', '');
        }

        function updateMomHeaders() {
            const momRef = new Date();
            const prevSame = getSubMonthNoOverflow(momRef);
            const prevEnd = new Date(prevSame.getFullYear(), prevSame.getMonth() + 1, 0);
            const prevRemainingStart = prevSame.getDate() + 1;

            $('#momHeaderPrevPartial').text(`1 – ${prevSame.getDate()} ${monthShortId(prevSame)}`);
            $('#momHeaderCurrentPartial').text(`1 – ${momRef.getDate()} ${monthShortId(momRef)}`);

            if (prevRemainingStart <= prevEnd.getDate()) {
                $('#momHeaderPrevRemaining').text(`${prevRemainingStart} – ${prevEnd.getDate()} ${monthShortId(prevSame)}`);
            } else {
                $('#momHeaderPrevRemaining').text(`-`);
            }
        }

        updateMomHeaders();

        function updateFilterPeriodHeader() {
            const startVal = $('#startDatePH').val() || '-';
            const endVal = $('#endDatePH').val() || '-';
            $('.displayedStartDatePH').text(startVal);
            $('.displayedEndDatePH').text(endVal);
        }

        updateFilterPeriodHeader();

        function saveElementAsImage(elementId, filePrefix) {
            html2canvas(document.getElementById(elementId), {
                scale: 2,
                allowTaint: true,
                useCORS: true
            })
                .then(canvas => {
                    const link = document.createElement('a');
                    link.download = filePrefix + '-' + new Date().getTime() + '.png';
                    link.href = canvas.toDataURL();
                    link.click();
                })
                .catch(err => {
                    console.error('Error capturing image:', err);
                    alert('Gagal menyimpan gambar. Silakan coba lagi.');
                });
        }

        function saveMpccPerformanceWithoutMom() {
            const sourceWrap = document.getElementById('capturePowerHousePerformanceTable');
            const sourceTable = document.getElementById('powerHousePerformanceTable');

            if (!sourceWrap || !sourceTable) {
                alert('Tabel performance tidak ditemukan.');
                return;
            }

            const clonedWrap = sourceWrap.cloneNode(true);
            const clonedTable = clonedWrap.querySelector('#powerHousePerformanceTable');

            if (!clonedTable) {
                alert('Gagal menyiapkan tabel untuk disimpan.');
                return;
            }

            clonedTable.removeAttribute('id');

            const allRows = clonedTable.querySelectorAll('tr');

            if (allRows[0] && allRows[0].children[0]) {
                allRows[0].children[0].setAttribute('colspan', '12');
                allRows[0].children[0].innerHTML = allRows[0].children[0].innerHTML.replace('&amp; MOM ', '').replace('& MOM ', '');
            }

            if (allRows[1]) {
                const momGroupCell = allRows[1].children[8];
                if (momGroupCell) {
                    momGroupCell.remove();
                }
            }

            if (allRows[2]) {
                const thirdHeaderCells = Array.from(allRows[2].children);
                thirdHeaderCells.slice(-4).forEach(function(cell) {
                    cell.remove();
                });
            }

            clonedTable.querySelectorAll('tbody tr, tfoot tr').forEach(function(row) {
                const cells = Array.from(row.children);
                cells.slice(-4).forEach(function(cell) {
                    cell.remove();
                });
            });

            clonedWrap.style.maxWidth = 'fit-content';
            clonedWrap.style.overflow = 'visible';
            clonedWrap.style.backgroundColor = '#ffffff';
            clonedWrap.style.padding = '0';
            clonedWrap.style.position = 'fixed';
            clonedWrap.style.left = '-99999px';
            clonedWrap.style.top = '0';

            document.body.appendChild(clonedWrap);

            html2canvas(clonedWrap, {
                scale: 2,
                allowTaint: true,
                useCORS: true
            })
                .then(canvas => {
                    const link = document.createElement('a');
                    link.download = 'mpcc-deal-topup-without-mom-' + new Date().getTime() + '.png';
                    link.href = canvas.toDataURL();
                    link.click();
                })
                .catch(err => {
                    console.error('Error capturing image:', err);
                    alert('Gagal menyimpan gambar. Silakan coba lagi.');
                })
                .finally(() => {
                    clonedWrap.remove();
                });
        }

        // Save image for MPCC summary table
        document.getElementById('btnSavePowerHouseImage').addEventListener('click', function () {
            saveElementAsImage('capturePowerHouseTable', 'mpcc-report');
        });

        // Save image for MPCC Deal Top Up & MOM table only
        document.getElementById('btnSaveMpccPerformanceImage').addEventListener('click', function () {
            saveMpccPerformanceWithoutMom();
        });
    });
</script>
@endsection
