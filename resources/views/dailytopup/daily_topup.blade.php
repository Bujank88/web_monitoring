@extends('master')
@section('title') Daily TopUp Channel @endsection
@section('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
    .btn-ref {
        position: fixed;
        top: 50px;
        left: 1000px;
    }

    #chart1,
    #chart2,
    #chart3 {
        min-height: 400px;
        max-height: 500px;
    }

    #loading-overlay {

        position: fixed;

        top: 0;

        left: 0;

        width: 100%;

        height: 100%;

        background: rgba(0, 0, 0, 0.7);

        z-index: 9999;

        display: none;
        
        justify-content: center;
        
        align-items: center;

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

    .table {
        background-color: #f9f9f9;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        margin-top: 15px;
        border: 0.5px solid #ccc;
        table-layout: auto;

        /* Allow dynamic column sizing */
    }

    .table th,
    .table td {
        padding: 8px !important;
        font-size: 16px;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
    }

    .table th {
        font-weight: bold;
        text-align: center;
        /* Align text to the left */
    }


    .table tbody tr {
        transition: background-color 0.3s;
    }

    .table tbody tr:hover {
        background-color: #e2e2e2;
    }

    .table tbody tr:nth-child(odd) {
        background-color: #f2f2f2;
        /* Odd row background color */
    }

    .table tbody tr:nth-child(even) {
        background-color: #ffffff;
        /* Even row background color */
    }

    @media (max-width: 768px) {

        .table th,
        .table td {
            font-size: 12px;
        }
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
    }

    .gap-2 {
        gap: 8px;
    }

    /* Horizontal Scroll for Tables on Mobile */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        width: 100%;
    }

    .table-responsive table {
        margin-bottom: 0 !important;
    }

    @media (max-width: 768px) {
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100% !important;
            min-width: 1200px;
            white-space: nowrap;
        }
    }
</style>
@endsection

@section('content')
<!-- Loading Overlay -->
<div id="loading-overlay" style="display: none;">
    <div id="loading-spinner" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; z-index: 10000;">
        <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: white; margin-bottom: 10px;"></i>
        <p style="color: white; font-size: 16px;">Loading data...</p>
    </div>
</div>

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

<!-- Filter Section with Quick Navigation -->
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <!-- Quick Navigation Buttons (Left) -->
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('dailyTopupByProvinceTableCard').scrollIntoView({behavior: 'smooth'});">
                <i class="fas fa-arrow-down mr-2"></i> Per Province
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.href='{{ route('home') }}';">
                <i class="fas fa-home mr-2"></i> Dashboard
            </button>
        </div>

        <!-- Filter and Action Buttons (Right) -->
        <div class="d-flex align-items-center gap-2">
            <select id="filterMonthPH" name="filterMonthPH" class="form-control" style="background-color: #313131; color: white; min-width: 180px; max-width: 200px;">
                @foreach ($months as $month)
                <option value="{{ $month['value'] }}" {{ $month['selected'] ? 'selected' : '' }}>
                    {{ $month['label'] }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
<!-- Daily Topup Table -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="dailyTopupTableCard">
            <div class="card-header bg-gradient-danger text-white d-flex justify-content-between align-items-center" style="padding: 1rem; border-radius: 0.35rem 0.35rem 0 0;">
                <h4 class="mb-0"><i class="fas fa-chart-bar"></i> Daily Topup / Channel</h4>
                <div class="d-flex gap-2">
                    <button type="button" id="btnSaveDailyTopupImage" class="btn btn-light btn-sm" title="Save as Image" style="padding: 6px 12px; white-space: nowrap;">
                        <i class="fas fa-image mr-2"></i> Save Image
                    </button>
                    <a href="{{ route('export.daily_topup') }}" class="btn btn-light btn-sm" title="Download Excel" style="padding: 6px 12px; white-space: nowrap;">
                        <i class="fas fa-file-excel mr-2"></i> Download Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="captureDailyTopupTable" class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="dailyTopupTable" style="font-size: 12px;">
                        <thead class="thead-light">
                            <tr>
                                <th rowspan="3" style="vertical-align: middle; text-align: center; background-color: #f8f9fa;">Tanggal</th>
                                <th colspan="18" class="text-center" style="background-color: #f8d7da;">Report Daily TopUp All Channel | Bulan: <span id="displayedMonthPH">{{ $months[array_search(true, array_column($months, 'selected'))]['label'] ?? now()->format('F Y') }}</span></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-center" style="background-color: #d1ecf1;">Canvasser</th>
                                <th colspan="2" class="text-center" style="background-color: #fff3cd;">Mitra SBP</th>
                                <th colspan="2" class="text-center" style="background-color: #e2e3e5;">Agency Indihome</th>
                                <th colspan="2" class="text-center" style="background-color: #fcc271;">Internal</th>
                                <th colspan="2" class="text-center" style="background-color: #eef7d9;">Agency Advertising</th>
                                <th colspan="2" class="text-center" style="background-color: #d4edea;">B2B</th>
                                <th colspan="2" class="text-center" style="background-color: #d8e2ff;">Powerhouse</th>
                                <th colspan="2" class="text-center" style="background-color: #d4edda;">Self Service</th>
                                <th colspan="2" class="text-center" style="background-color: #f62b3c; color: white;">Total</th>
                            </tr>
                            <tr>
                                <th class="text-center" style="background-color: #d1ecf1;">user_id</th>
                                <th class="text-center" style="background-color: #d1ecf1;">Total Settlement</th>
                                <th class="text-center" style="background-color: #fff3cd;">user_id</th>
                                <th class="text-center" style="background-color: #fff3cd;">Total Settlement</th>
                                <th class="text-center" style="background-color: #e2e3e5;">user_id</th>
                                <th class="text-center" style="background-color: #e2e3e5;">Total Settlement</th>
                                <th class="text-center" style="background-color: #fcc271;">user_id</th>
                                <th class="text-center" style="background-color: #fcc271;">Total Settlement</th>
                                <th class="text-center" style="background-color: #eef7d9;">user_id</th>
                                <th class="text-center" style="background-color: #eef7d9;">Total Settlement</th>
                                <th class="text-center" style="background-color: #d4edea;">user_id</th>
                                <th class="text-center" style="background-color: #d4edea;">Total Settlement</th>
                                <th class="text-center" style="background-color: #d8e2ff;">user_id</th>
                                <th class="text-center" style="background-color: #d8e2ff;">Total Settlement</th>
                                <th class="text-center" style="background-color: #d4edda;">user_id</th>
                                <th class="text-center" style="background-color: #d4edda;">Total Settlement</th>
                                <th class="text-center" style="background-color: #f62b3c; color: white;">User Id</th>
                                <th class="text-center" style="background-color: #f62b3c; color: white;">Settlement</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Topup Per Province Table -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="dailyTopupByProvinceTableCard">
            <div class="card-header bg-gradient-danger text-white d-flex justify-content-between align-items-center" style="padding: 1rem; border-radius: 0.35rem 0.35rem 0 0;">
                <h4 class="mb-0" style="font-weight: 700; letter-spacing: 0.5px;">
                    <i class="fas fa-map-marker-alt"></i> Daily TopUp Channel Per Province
                </h4>
                <div class="d-flex gap-2">
                    <button type="button" id="btnSaveProvinceImage" class="btn btn-light btn-sm" title="Save as Image" style="padding: 6px 12px; white-space: nowrap;">
                        <i class="fas fa-image mr-2"></i> Save Image
                    </button>
                    <button type="button" id="btnExportProvinceExcel" class="btn btn-light btn-sm" title="Download Excel" style="padding: 6px 12px; white-space: nowrap;">
                        <i class="fas fa-file-excel mr-2"></i> Download Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" id="captureProvinceTable">
                    <table class="table table-sm w-100 table-bordered table-hover" id="dailyTopupByProvinceTable" style="font-size: 12px;">
                        <thead class="thead-light">
                            <tr>
                                <th colspan="5" class="text-center" style="background-color: #d1ecf1; font-weight: 700; padding: 12px;">
                                    Report Daily TopUp Per Province | Bulan: <span id="displayedMonthByProvince">{{ $months[array_search(true, array_column($months, 'selected'))]['label'] ?? now()->format('F Y') }}</span>
                                </th>
                            </tr>
                            <tr>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">Province</th>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">User ID</th>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">Email</th>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">Bulan</th>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">Total Settlement</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #ff2626; font-weight: 700; color: white;">
                                <td id="footerProvince" style="text-align: center; border: 1px solid #cc0000; padding: 12px; color: white; font-size: 13px;"><strong>Total Province</strong><br><span id="totalProvinceValue" style="font-size: 16px;">0</span></td>
                                <td id="footerUserId" style="text-align: center; border: 1px solid #cc0000; padding: 12px; color: white; font-size: 13px;"><strong>Total User ID</strong><br><span id="totalUserIdValue" style="font-size: 16px;">0</span></td>
                                <td id="footerEmail" style="text-align: center; border: 1px solid #cc0000; padding: 12px; color: white; font-size: 13px;"><strong>Total Email</strong><br><span id="totalEmailValue" style="font-size: 16px;">0</span></td>
                                <td id="footerBulan" style="text-align: center; border: 1px solid #cc0000; padding: 12px; font-size: 14px; color: white;"><strong>TOTAL</strong></td>
                                <td id="footerSettlement" style="text-align: right; border: 1px solid #cc0000; padding: 12px; font-weight: 700; color: white; font-size: 16px;">Rp 0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Navigation -->
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('dailyTopupTableCard').scrollIntoView({behavior: 'smooth'});">
            <i class="fas fa-arrow-up mr-2"></i> Back to Daily TopUp
        </button>
        <button type="button" class="btn btn-outline-primary" onclick="window.location.href='{{ route('home') }}';">
            <i class="fas fa-home mr-2"></i> Dashboard
        </button>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    $(document).ready(function() {

        // DataTable untuk Daily Topup
        var table = $('#dailyTopupTable').DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            paging: false,
            searching: false,
            autoWidth: false,
            ajax: {
                url: "{{ route('daily_topup_data') }}",
                type: "GET",
                data: function(d) {
                    d.month = $('#filterMonthPH').val();
                    return d;
                },
                dataSrc: function(json) {
                    console.log("Response dari server:", json);
                    return json.data || [];
                }
            },
            preDrawCallback: function() {
                $('#loading-overlay').show();
            },
            drawCallback: function() {
                $('#loading-overlay').hide();
            },
            columns: [{
                    data: 'date',
                    render: function(data, type, row) {
                        if (data === 'Total Keseluruhan') {
                            return `<div style="text-align: center; font-weight: bold;">${data}</div>`;
                        }
                        return `<div style="text-align: center;">${data}</div>`;
                    }
                },
                {
                    data: 'canvasser_user',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: center;" class="${className}">${data || '-'}</div>`;
                    }
                },

                {
                    data: 'canvasser_settle',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: right;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'mitra_sbp_user',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: center;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'mitra_sbp_settle',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: right;" class="${className}">${data || '-'}</div>`;
                    }
                },
                

                {
                    data: 'agency_user',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: center;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'agency_settle',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: right;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'internal_user',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: center;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'internal_settle',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: right;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'advertising_user',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: center;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'advertising_settle',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: right;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'b2b_user',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: center;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'b2b_settle',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: right;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'powerhouse_user',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: center;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'powerhouse_settle',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: right;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'self_service_user',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: center;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'self_service_settle',
                    render: function(data, type, row) {
                        let className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                        return `<div style="text-align: right;" class="${className}">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'total_user',
                    render: function(data, type, row) {
                        return `<div style="text-align: center; font-weight: bold;">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'total',
                    render: function(data, type, row) {
                        return `<div style="text-align: right; font-weight: bold;">${data || '-'}</div>`;
                    }
                }

            ],
            rowCallback: function(row, data) {
                if (data.date === 'Total Keseluruhan') {
                    $(row).addClass('table-info');
                }
            }
        });

        // Event listener untuk filter bulan
        $('#filterMonthPH').on('change', function() {
            // Show loading overlay
            $('#loading-overlay').show();
            
            // Update label bulan yang ditampilkan dengan text dari selected option
            var selectedText = $('#filterMonthPH option:selected').text();
            $('#displayedMonthPH').text(selectedText);
            $('#displayedMonthByProvince').text(selectedText);
            
            // Reload data table
            table.ajax.reload();
            tableByProvince.ajax.reload();
        });

        $('#dailyTopupTable').on('error.dt', function(e, settings, techNote, message) {
            console.log("DataTables Error:", message);
        });

        // Handle Save Image Button
        document.getElementById('btnSaveDailyTopupImage').addEventListener('click', function() {
            html2canvas(document.getElementById('captureDailyTopupTable'), {
                    scale: 2,
                    allowTaint: true,
                    useCORS: true
                })
                .then(canvas => {
                    const link = document.createElement('a');
                    link.href = canvas.toDataURL('image/png');
                    link.download = 'daily_topup_' + new Date().getTime() + '.png';
                    link.click();
                })
                .catch(err => {
                    console.error('Error capturing table:', err);
                    alert('Gagal menyimpan gambar. Silakan coba lagi.');
                });
        });

        // DataTable untuk Daily Topup Per Province
        var tableByProvince = $('#dailyTopupByProvinceTable').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            pageLength: 25,
            lengthChange: true,
            autoWidth: false,
            ajax: {
                url: "{{ route('daily_topup_by_province_data') }}",
                type: "GET",
                data: function(d) {
                    d.month = $('#filterMonthPH').val();
                    return d;
                },
                dataSrc: function(json) {
                    console.log("Response dari server (by province):", json);
                    // Consume totals dari backend
                    if (json.totals) {
                        $('#totalProvinceValue').text(json.totals.total_provinces);
                        $('#totalUserIdValue').text(json.totals.total_user_ids);
                        $('#totalEmailValue').text(json.totals.total_emails);
                        $('#footerSettlement').text(json.totals.total_settlement_format);
                    }
                    return json.data || [];
                }
            },
            preDrawCallback: function() {
                $('#loading-overlay').show();
            },
            drawCallback: function(settings) {
                $('#loading-overlay').hide();
            },
            columns: [
                {
                    data: 'data_province_name',
                    searchable: true,
                    render: function(data, type, row) {
                        return `<div style="text-align: center;">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'user_id',
                    searchable: true,
                    render: function(data, type, row) {
                        return `<div style="text-align: center;">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'email_client',
                    searchable: true,
                    render: function(data, type, row) {
                        return `<div style="text-align: left; word-break: break-word;">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'tanggal_format',
                    searchable: false,
                    render: function(data, type, row) {
                        return `<div style="text-align: center;">${data || '-'}</div>`;
                    }
                },
                {
                    data: 'total_settlement_format',
                    searchable: false,
                    render: function(data, type, row) {
                        return `<div style="text-align: right; font-weight: 500;">${data || '-'}</div>`;
                    }
                }
            ]
        });

        // Event listener untuk filter bulan - reload datatable per province juga
        $('#filterMonthPH').on('change', function() {
            tableByProvince.ajax.reload();
        });

        $('#dailyTopupByProvinceTable').on('error.dt', function(e, settings, techNote, message) {
            console.log("DataTables Error (Province):", message);
        });

        // Handle Save Province Image Button
        document.getElementById('btnSaveProvinceImage').addEventListener('click', function() {
            html2canvas(document.getElementById('captureProvinceTable'), {
                    scale: 2,
                    allowTaint: true,
                    useCORS: true
                })
                .then(canvas => {
                    const link = document.createElement('a');
                    link.href = canvas.toDataURL('image/png');
                    link.download = 'daily_topup_per_province_' + new Date().getTime() + '.png';
                    link.click();
                })
                .catch(err => {
                    console.error('Error capturing table:', err);
                    alert('Gagal menyimpan gambar. Silakan coba lagi.');
                });
        });

        // Handle Export Province Excel Button
        document.getElementById('btnExportProvinceExcel').addEventListener('click', function() {
            let monthValue = $('#filterMonthPH').val();
            window.location = "{{ route('export.daily_topup_by_province') }}?month=" + monthValue;
        });

    });
</script>
@endsection
