@extends('master')
@section('title') Dashboard @endsection
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

    #chart1, #chart2, #chart3 {
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

    #loading-overlay.show {
        display: flex;
    }

    #loading-spinner {
        font-size: 24px;
        color: white;
        text-align: center;
    }

    #loading-spinner i {
        display: block;
        font-size: 48px;
        margin-bottom: 10px;
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

    /* Filter and Chart Controls */
    .chart-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .chart-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .chart-buttons .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
        white-space: nowrap;
    }

    /* DataTable Pagination Styling */
    .dataTables_wrapper .dataTables_paginate {
        margin-top: 1rem;
        text-align: center;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.5rem 0.75rem;
        margin: 0 2px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
        color: #495057;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled) {
        background-color: #e2e6ea;
        border-color: #dee2e6;
        transform: translateY(-2px);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
</style>
@endsection

@section('content')
<!-- Loading Overlay -->
<div id="loading-overlay">
    <div id="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        Loading, please wait...
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

<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-gradient-primary">
            <div class="card-body text-white">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        <h2 class="mb-2"><i class="fas fa-tachometer-alt"></i> Dashboard MyAds Monitoring</h2>
                        <p class="mb-0">Selamat datang di sistem monitoring MyAds Telkomsel</p>
                    </div>
                    <div class="col-md-6">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4 col-4">
                                <button type="button" class="btn btn-light btn-sm w-100" onclick="scrollToSection('chart1Card')">
                                    <i class="fas fa-chart-bar d-block mb-1"></i>
                                    <small>Charts</small>
                                </button>
                            </div>
                            <div class="col-md-4 col-4">
                                <button type="button" class="btn btn-light btn-sm w-100" onclick="scrollToSection('canvaserTableCard')">
                                    <i class="fas fa-table d-block mb-1"></i>
                                    <small>Canvasser</small>
                                </button>
                            </div>
                            <div class="col-md-4 col-4">
                                <select id="filterMonthDashboard" name="filterMonthDashboard" class="form-control form-control-sm" style="background-color: rgba(255,255,255,0.9); color: #313131; font-size: 0.85rem;">
                                    @foreach($months as $month)
                                    <option value="{{ $month['value'] }}" {{ $month['selected'] ? 'selected' : '' }}>
                                        {{ $month['label'] }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bar Chart Section -->
<div class="row mb-4">
    <!-- Card 1: Prospect Leads vs Deal New Akun -->
    <div class="col-md-4">
        <div class="card" id="chart1Card">
            <div class="card-header bg-gradient-info text-white">
                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Prospect Leads vs Deal New Akun</h6>
            </div>
            <div class="card-body">
                <canvas id="chart1" style="height: 400px; width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <!-- Card 2: Prospect Existing Akun vs Deal Top Up -->
    <div class="col-md-4">
        <div class="card" id="chart2Card">
            <div class="card-header bg-gradient-success text-white">
                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Prospect Existing Akun vs Deal Top Up</h6>
            </div>
            <div class="card-body">
                <canvas id="chart2" style="height: 400px; width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <!-- Card 3: Target vs ACV -->
     <div class="col-md-4">
        <div class="card" id="chart3Card">
            <div class="card-header bg-gradient-danger text-white">
                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Target vs ACV (Juta Rp)</h6>
            </div>
            <div class="card-body">
                <canvas id="chart3" style="height: 400px; width: 100%;"></canvas>
            </div>
        </div>
    </div>
</div>


<!-- Report Canvaser All Region -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="canvaserTableCard">
           <div class="card-header bg-gradient-success text-white">
                <h4 class="mb-0"><i class="fas fa-table"></i> Report Canvaser All Region</h4>
            </div>
            <div class="card-body">
                @php
                    $date = request('month')
                        ? \Carbon\Carbon::createFromFormat('Y-m', request('month'))
                        : now();
                    $currentDate = $date->day;

                    // Bulan sebelumnya
                    $prevMonth = $date->copy()->subMonthNoOverflow();
                    $lastDay = $prevMonth->endOfMonth()->day;
                @endphp
                <div id="captureRegionalTable" class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="regionalTable" style="font-size: 11px;">
                        <thead class="thead-light">
                            <tr>
                                <th colspan="17" class="text-center" style="background-color: #d1ecf1;">Data Bulan Berjalan: <span id="displayedMonth">{{ $date->format('Y-m') }}</span></th>
                            </tr>
                            <tr>
                                <th rowspan="3" style="vertical-align: middle; text-align: center; background-color: #f8f9fa;">No</th>
                                <th rowspan="3" style="vertical-align: middle; text-align: center; background-color: #f8f9fa;">Canvaser Name</th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-center" style="background-color: #cfe2ff;">Data Prospect (Leads & Eksisting Akun)</th>
                                <th colspan="2" class="text-center" style="background-color: #d1e7dd;">Deal Top Up (New Akun & Eksisting Akun)</th>
                                <th colspan="3" style="vertical-align: middle; text-align: center; background-color: #f8d7da;">Top Up (Rp.)</th>
                                <th colspan="4" style="vertical-align: middle; text-align: center; background-color: #fff3cd;">Target & Achievement</th>
                                <th colspan="4" style="vertical-align: middle; text-align: center; background-color: #d3ffcd;">MOM</th>
                            </tr>
                            <tr>
                                <th class="text-center" style="background-color: #cfe2ff;">Leads</th>
                                <th class="text-center" style="background-color: #cfe2ff;">Eksisting Akun</th>
                                <th class="text-center" style="background-color: #d1e7dd;">New Akun</th>
                                <th class="text-center" style="background-color: #d1e7dd;">Eksisting Akun</th>
                                <th class="text-center" style="background-color: #f8d7da;">New Akun(Rp.)</th>
                                <th class="text-center" style="background-color: #f8d7da;">Eksisting Akun(Rp.)</th>
                                <th class="text-center" style="background-color: #f8d7da;">Total (Rp.)</th>
                                <th class="text-center" style="background-color: #fff3cd;">Target (Rp)</th>
                                <th class="text-center" style="background-color: #fff3cd;">Achievement (%)</th>
                                <th class="text-center" style="background-color: #fff3cd;">Gap (Rp)</th>
                                <th class="text-center" style="background-color: #fff3cd;">Gap Daily (Rp)</th>
                                <th class="text-center" style="background-color: #d3ffcd;">1 – {{ $currentDate }} {{ $prevMonth->translatedFormat('M') }}</th>
                                <th class="text-center" style="background-color: #d3ffcd;">1 – {{ $currentDate }} {{ $date->translatedFormat('M') }}</th>
                                <th class="text-center" style="background-color: #d3ffcd;">{{ $currentDate + 1 }} – {{$lastDay}} {{ $prevMonth->translatedFormat('M') }}</th>
                                <th class="text-center" style="background-color: #d3ffcd;">Gap (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="15" class="text-center">Loading data...</td>
                            </tr>
                        </tbody>
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
<!-- jQuery HARUS di-load terlebih dahulu -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables CSS dan JS -->
<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Chart.js dan plugin -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- html2canvas untuk screenshot -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
    // Register plugin datalabels
    Chart.register(ChartDataLabels);

    // Store chart instances for screenshot functionality
    let chart1Instance, chart2Instance, chart3Instance;

    $(document).ready(function() {
        // Initialize Select2 for dropdowns
        $('.select2-custom').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('.card-body'),
            allowClear: true,
            placeholder: function(){
                return $(this).data('placeholder');
            }
        });
        // Load Chart Data untuk Regional
        loadRegionalChart();

        // Month filter change event
        $('#filterMonthDashboard').on('change', function() {
            const selectedMonth = $(this).val();
            const selectedText = $('#filterMonthDashboard option:selected').text();
            console.log('Selected month:', selectedMonth);
            
            // Show loading overlay
            $('#loading-overlay').addClass('show');
            
            // Update displayed month text
            // Extract just Y-m from selectedMonth (format: YYYY-MM-DD)
            const monthOnly = selectedMonth.substring(0, 7);
            $('#displayedMonth').text(monthOnly);
            
            // Reload chart and table data
            loadRegionalChart(selectedMonth);
            if (typeof regionalTable !== 'undefined') {
                regionalTable.ajax.reload();
            }
        });

        // Save Chart 1 Image
        $('#btnSaveChart1').on('click', function() {
            saveChartImage(chart1Instance, 'chart1');
        });

        // Save Chart 2 Image
        $('#btnSaveChart2').on('click', function() {
            saveChartImage(chart2Instance, 'chart2');
        });

        // Save Chart 3 Image
        $('#btnSaveChart3').on('click', function() {
            saveChartImage(chart3Instance, 'chart3');
        });

        // Save Regional Table Image
        $('#btnSaveRegionalTableImage').on('click', function() {
            html2canvas(document.getElementById('captureRegionalTable'), {
                scale: 2,
                allowTaint: true,
                useCORS: true
            })
            .then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = 'regional_table_' + new Date().getTime() + '.png';
                link.click();
            })
            .catch(err => {
                console.error('Error capturing table:', err);
                alert('Gagal menyimpan gambar. Silakan coba lagi.');
            });
        });

        // Filter functionality
        $('#applyFilter').on('click', function() {
            const area = $('#filterArea').val();
            const regional = $('#filterRegional').val();
            const periode = $('#filterPeriode').val();
            
            console.log('Filter applied:', { area, regional, periode });
            
            // Reload tables dengan filter
            regionalTable.ajax.reload();
            $('#dailyTopupTable').DataTable().ajax.reload();
            
            // Show notification
            alert('Filter diterapkan: ' + 
                (area ? 'Area: ' + area : '') + 
                (regional ? ', Regional: ' + regional : '') + 
                (periode ? ', Periode: ' + periode : '')
            );
        });

        $('#resetFilter').on('click', function() {
            $('#filterArea').val('AREA 3').trigger('change');
            $('#filterRegional').val('').trigger('change');
            $('#filterPeriode').val('{{ now()->format('Y-m') }}').trigger('change');
            
            // Reload tables
            regionalTable.ajax.reload();
            $('#dailyTopupTable').DataTable().ajax.reload();
            
            console.log('Filter reset');
        });

        // DataTable untuk Regional Data
        var regionalTable = $('#regionalTable').DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            paging: false,
            searching: false,
            ajax: {
                url: "{{ route('regional_data') }}",
                type: "GET",
                data: function(d) {
                    // Tambahkan month parameter dari filter
                    let month = $('#filterMonthDashboard').val();
                    // Convert Y-m-d ke Y-m format untuk backend
                    if (month && month.includes('-')) {
                        month = month.substring(0, 7);
                    }
                    d.month = month;
                    return d;
                },
                dataSrc: function(json) {
                    console.log("Regional Data:", json);
                    return json.data || [];
                }
            },
            preDrawCallback: function() {
                $('#loading-overlay').addClass('show');
            },
            drawCallback: function() {
                $('#loading-overlay').removeClass('show');
            },
            columns: [{
                    data: 'no',
                    className: 'text-center',
                    render: function(data, type, row, meta) {
                        return `<div style="text-align: center;">${meta.row + 1}</div>`;
                    }
                },
                {
                    data: 'canvaser_name',
                    className: 'text-center',
                    render: function(data, type, row) {
                        // Jika baris total, tampilkan HTML
                        if (row.is_total) {
                            return `<div style="text-align: center; font-weight: bold; font-size: 14px;">${data}</div>`;
                        }
                        return `<div style="text-align: center;">${data}</div>`;
                    }
                },
                {
                    data: 'leads',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;">${parseInt(data).toLocaleString('id-ID')}</div>`;
                    }
                },
                {
                    data: 'existing_akun',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;">${parseInt(data).toLocaleString('id-ID')}</div>`;
                    }
                },
                {
                    data: 'new_akun',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;">${parseInt(data).toLocaleString('id-ID')}</div>`;
                    }
                },
                {
                    data: 'top_up_existing_akun_count',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;">${parseInt(data).toLocaleString('id-ID')}</div>`;
                    }
                },
                {
                    data: 'top_up_new_akun_rp',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;">${data}</div>`;
                    }
                },
                {
                    data: 'top_up_existing_akun_rp',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;">${data}</div>`;
                    }
                },
                {
                    data: 'total_top_up_rp',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;"><strong>${data}</strong></div>`;
                    }
                },
                {
                    data: 'target',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center; font-weight: bold; color: #0d6efd;">${data}</div>`;
                    }
                },
                {
                    data: 'achievement_percent',
                    className: 'text-center',
                    render: function(data) {
                        let percent = parseFloat(data.replace(',', '.').replace('%', ''));
                        let color = percent >= 100 ? '#28a745' : (percent >= 75 ? '#ffc107' : '#dc3545');
                        return `<div style="text-align: center; font-weight: bold; color: ${color};">${data}</div>`;
                    }
                },
                {
                    data: 'gap',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center; color: #dc3545;">${data}</div>`;
                    }
                },
                {
                    data: 'gap_daily',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center; color: #fd7e14; font-weight: bold;">${data}</div>`;
                    }
                },
                {
                    data: 'mom_prev_partial',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;">${data}</div>`;
                    }
                },
                {
                    data: 'mom_current_partial',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;">${data}</div>`;
                    }
                },
                {
                    data: 'mom_prev_remaining',
                    className: 'text-center',
                    render: function(data) {
                        return `<div style="text-align: center;">${data}</div>`;
                    }
                },
                {
                    data: 'mom_gap',
                    className: 'text-center',
                    render: function (data, type, row) {
                        // hilangkan pemisah ribuan & convert ke number
                        let value = parseFloat(
                            String(data)
                                .replace(/\./g, '')
                                .replace(',', '.')
                        ) || 0;

                        let color = 'black';
                        let fontWeight = 'normal';

                        if (value < 0) {
                            color = 'red';
                            fontWeight = 'bold';
                        } else if (value > 0) {
                            color = 'green';
                            fontWeight = 'bold';
                        }

                        return `<div style="text-align:center; color:${color}; font-weight:${fontWeight};">
                                    ${data}
                                </div>`;
                    }
                }
            ],
            rowCallback: function(row, data) {
                // Styling untuk baris total
                if (data.is_total) {
                    $(row).css({
                        'background-color': '#fff3cd',
                        'font-weight': 'bold',
                        'border-top': '2px solid #ffc107'
                    });
                }
            }
        });
        // Function untuk load Regional Chart
        function loadRegionalChart(month = null) {
            // Gunakan selected month dari dropdown jika tidak diberikan parameter
            if (!month) {
                month = $('#filterMonthDashboard').val();
            }
            
            // Convert Y-m-d to Y-m format untuk backend
            if (month && month.includes('-')) {
                month = month.substring(0, 7); // Ambil hanya Y-m-d menjadi Y-m
            }
            
            $.ajax({
                url: "{{ route('regional_chart_data') }}",
                type: "GET",
                data: {
                    month: month
                },
                success: function(response) {
                    console.log("Chart Data:", response);
                    renderRegionalChart(response);
                    // Hide loading after chart is rendered
                    $('#loading-overlay').removeClass('show');
                },
                error: function(xhr, status, error) {
                    console.error("Error loading chart data:", error);
                    console.error("XHR Status:", xhr.status);
                    console.error("Response:", xhr.responseText);
                    // Hide loading on error
                    $('#loading-overlay').removeClass('show');
                }
            });
        }

        // Function untuk render Chart
        function renderRegionalChart(data) {
            const canvassers = data.canvassers || [];
            const labels = canvassers.map(c => c.name);

            // Destroy existing charts if they exist
            if (chart1Instance) {
                chart1Instance.destroy();
            }
            if (chart2Instance) {
                chart2Instance.destroy();
            }
            if (chart3Instance) {
                chart3Instance.destroy();
            }

            // Chart 1: Prospect New Leads vs Deal New Akun
            const ctx1 = document.getElementById('chart1').getContext('2d');
            chart1Instance = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Prospect: New Leads',
                        data: canvassers.map(c => c.new_leads),
                        backgroundColor: '#ff3324', // Merah Telkomsel
                    }, {
                        label: 'Deal: New Akun',
                        data: canvassers.map(c => c.new_akun),
                        backgroundColor: '#0048a0', // Orange
                    }]
                },
                options: getChartOptions('count')
            });

            // Chart 2: Prospect Existing Akun vs Deal Top Up Existing Akun
            const ctx2 = document.getElementById('chart2').getContext('2d');
            chart2Instance = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Prospect: Existing Akun',
                        data: canvassers.map(c => c.existing_akun_count),
                        backgroundColor: '#C8102E', // Merah Tua
                    }, {
                        label: 'Deal: Top Up Count',
                        data: canvassers.map(c => c.top_up_existing_akun_count),
                        backgroundColor: '#121ded', // Orange Terang
                    }]
                },
                options: getChartOptions('count')
            });

            // Chart 3: Target vs ACV (dalam jutaan)
            const ctx3 = document.getElementById('chart3').getContext('2d');
            chart3Instance = new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Target (Juta Rp)',
                        data: canvassers.map(c => c.target / 1000000),
                        backgroundColor: '#fe2718', // Merah Telkomsel
                    }, {
                        label: 'ACV (Juta Rp)',
                        data: canvassers.map(c => c.acv / 1000000),
                        backgroundColor: '#1f54d9', // Orange Gelap
                    }]
                },
                options: getChartOptions('currency')
            });
        }

        // Helper function untuk chart options
        function getChartOptions(type) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: true
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID');
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                weight: 'bold',
                                size: 9
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (type === 'currency') {
                                    label += context.parsed.x.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' Jt';
                                } else {
                                    label += context.parsed.x.toLocaleString('id-ID');
                                }
                                return label;
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        offset: 2,
                        font: {
                            weight: 'bold',
                            size: 8
                        },
                        color: '#000',
                        formatter: function(value) {
                            if (value === 0) return '';
                            if (type === 'currency') {
                                return value.toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 1});
                            }
                            return value.toLocaleString('id-ID');
                        }
                    }
                }
            };
        }
    });

    // Scroll to section function
    function scrollToSection(sectionId) {
        const element = document.getElementById(sectionId);
        if (element) {
            element.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
            
            // Add highlight effect
            const card = element.closest('.card');
            if (card) {
                card.style.transition = 'all 0.3s';
                card.style.boxShadow = '0 0 20px rgba(0,123,255,0.5)';
                setTimeout(() => {
                    card.style.boxShadow = '';
                }, 2000);
            }
        }
    }

    // Function untuk save chart sebagai image
    function saveChartImage(chartInstance, chartName) {
        if (!chartInstance) {
            alert('Chart belum di-load. Silakan coba lagi.');
            return;
        }

        const link = document.createElement('a');
        link.href = chartInstance.toBase64Image();
        link.download = chartName + '_' + new Date().getTime() + '.png';
        link.click();
    }
</script>
@endsection