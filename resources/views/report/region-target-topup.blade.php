@extends('master')

@section('title', 'Report Target vs Topup Region')
@section('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<!-- DataTables Responsive CSS -->
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet"/>

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
</style>
@endsection
@section('content')
<div class="container-fluid">

    {{-- ================= FILTER BULAN ================= --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Pilih Bulan</label>
                    <input type="month"
                           name="month"
                           class="form-control"
                           value="{{ $month }}"
                           onchange="this.form.submit()">
                </div>
            </form>
        </div>
    </div>

    {{-- ================= CHART ================= --}}
    {{-- <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                Target vs Topup per Region
                ({{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y') }})
            </h5>
        </div>
        <div class="card-body" style="height: 400px">
            <canvas id="regionBarChart" height="100"></canvas>
        </div>
    </div> --}}
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
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Target vs ACH (Juta Rp)</h6>
                </div>
                <div class="card-body">
                    <canvas id="chart3" style="height: 400px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= TABLE ================= --}}
    <div class="card">
        <div class="card-body">

            <div id="captureTable">

                <h5 class="text-center mb-3">
                    Report Powerhouse –
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}
                </h5>

                <p class="text-center text-muted mb-4">
                    Last Update :
                    <strong>
                        {{ $lastUpdate
                            ? \Carbon\Carbon::parse($lastUpdate)->format('d M Y')
                            : '-' }}
                    </strong>
                </p>

                <div class="table-responsive">
                    <table id="regionTable" class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th style="color: white">No</th>
                                <th style="color: white">Region</th>
                                <th style="color: white">Nama PIC</th>
                                <th style="color: white">Topup</th>
                                <th style="color: white">Target</th>
                                <th style="color: white">Achievement (%)</th>
                                <th style="color: white">Gap (Rp)</th>
                                <th style="color: white">Gap Daily (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $i => $row)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $row['region'] }}</td>
                                    <td>{{ $row['pic'] }}</td>
                                    <td class="text-end" style="font-weight: bold;">{{ number_format($row['topup'],0,',','.') }}</td>
                                    <td class="text-end"  style="text-align: center; color: #0d6efd;">{{ number_format($row['target'],0,',','.') }}</td>
                                    <td class="text-center">
                                        <span class="badge
                                            {{ $row['percentage'] >= 90 ? 'bg-success' : ($row['percentage'] >= 70 ? 'bg-warning' : 'bg-danger') }}">
                                            {{ $row['percentage'] }}%
                                        </span>
                                    </td>
                                    <td class="text-end" style="text-align: center; color: #dc3545;">{{ number_format($row['gap'],0,',','.') }}</td>
                                    <td class="text-end" style="text-align: center; color: #fd7e14; font-weight: bold;">{{ number_format($row['gap_daily'],0,',','.') }}</td>
                                    
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="fw-bold">
                            <tr>
                                <td colspan="3" class="text-center">TOTAL</td>
                                <td class="text-end" style="text-align: center;font-weight: bold;">
                                    {{ number_format(collect($data)->sum('topup'),0,',','.') }}
                                </td>
                                <td class="text-end" style="text-align: center; color: #0d6efd; font-weight: bold;">
                                    {{ number_format(collect($data)->sum('target'),0,',','.') }}
                                </td>                                
                                <td class="text-center">
                                    <span class="badge
                                            {{ 
                                                collect($data)->sum('target') > 0
                                                    ? (
                                                        round((collect($data)->sum('topup') / collect($data)->sum('target')) * 100, 2) >= 90
                                                            ? 'bg-success'
                                                            : (
                                                                round((collect($data)->sum('topup') / collect($data)->sum('target')) * 100, 2) >= 70
                                                                    ? 'bg-warning'
                                                                    : 'bg-danger'
                                                            )
                                                    )
                                                    : ''
                                            }}">
                                    {{
                                        collect($data)->sum('target') > 0
                                        ? round((collect($data)->sum('topup') / collect($data)->sum('target')) * 100, 2)
                                        : 0
                                    }}%
                                    </span>
                                </td>
                                <td class="text-end"  style="text-align: center; color: #dc3545; font-weight:bold">
                                    {{ number_format(collect($data)->sum('gap'),0,',','.') }}
                                </td>
                                <td class="text-end"  style="text-align: center; color: #fd7e14; font-weight:bold">
                                    {{ number_format(collect($data)->sum('gap_daily'),0,',','.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="text-center mt-4">
                <button id="btnSaveImage" class="btn btn-primary btn-lg">
                    📸 Save Table as Image
                </button>
            </div>

        </div>
    </div>

</div>

@endsection
{{-- ================= JS ================= --}}
{{-- <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script> --}}
@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<!-- DataTable JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- DataTables Responsive JS -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
{{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}
<script>
// document.addEventListener('DOMContentLoaded', function () {

//     // const rawData = @json($data);

//     // new Chart(document.getElementById('regionBarChart'), {
//     //     type: 'bar',
//     //     data: {
//     //         labels: rawData.map(r => r.region),
//     //         datasets: [
//     //             { label: 'Target', data: rawData.map(r => r.target) },
//     //             { label: 'Topup', data: rawData.map(r => r.topup) }
//     //         ]
//     //     },
//     //     options: {
//     //         indexAxis: 'y',
//     //         responsive: true,
//     //         scales: {
//     //             x: {
//     //                 ticks: {
//     //                     callback: v => new Intl.NumberFormat('id-ID').format(v)
//     //                 }
//     //             }
//     //         }
//     //     }
//     // });

//     document.getElementById('btnSaveImage').addEventListener('click', function () {
//         html2canvas(document.getElementById('captureTable'), { scale: 2 })
//             .then(canvas => {
//                 const link = document.createElement('a');
//                 link.download = 'target-vs-topup-region.png';
//                 link.href = canvas.toDataURL();
//                 link.click();
//             });
//     });

// });
    Chart.register(ChartDataLabels);

    $(document).ready(function() {
        loadRegionalChart();
        
        // Initialize DataTable dengan responsive
        $('#regionTable').DataTable({
            responsive: true,
            paging: false,
            searching: false,
            info: false,
            ordering: false
        });
        
        // Function untuk load Regional Chart
        function loadRegionalChart() {
            $.ajax({
                url: "{{ route('regional_chart_data_for_ph') }}",
                type: "GET",
                success: function(response) {
                    console.log("Chart Data:", response);
                    renderRegionalChart(response);
                },
                error: function(xhr, status, error) {
                    console.error("Error loading chart data:", error);
                }
            });
        }

        // Function untuk render Chart
        function renderRegionalChart(data) {
            const items = data.canvassers || [];

            // LABEL = NAMA PIC (fallback jika kosong)
            const labels = items.map(c => {
                return (c.pic && c.pic !== '-')
                    ? c.pic
                    : (c.region ?? 'Unknown Regional');
            });

            /* ===============================
            CHART 1
            Prospect New Leads vs Deal New Akun
            =============================== */
            const ctx1 = document.getElementById('chart1').getContext('2d');
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Prospect: New Leads',
                            data: items.map(c => c.new_leads ?? 0),
                            backgroundColor: '#ff3324',
                        },
                        {
                            label: 'Deal: New Akun',
                            data: items.map(c => c.new_akun ?? 0),
                            backgroundColor: '#0048a0',
                        }
                    ]
                },
                options: getChartOptions('count')
            });

            /* ===============================
            CHART 2
            Prospect Existing Akun vs Deal Top Up
            =============================== */
            const ctx2 = document.getElementById('chart2').getContext('2d');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Prospect: Existing Akun',
                            data: items.map(c => c.existing_akun_count ?? 0),
                            backgroundColor: '#C8102E',
                        },
                        {
                            label: 'Deal: Top Up Count',
                            data: items.map(c => c.top_up_existing_akun_count ?? 0),
                            backgroundColor: '#121ded',
                        }
                    ]
                },
                options: getChartOptions('count')
            });

            /* ===============================
            CHART 3
            Target vs ACV
            =============================== */
            const ctx3 = document.getElementById('chart3').getContext('2d');
            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Target (Juta Rp)',
                            data: items.map(c => (c.target ?? 0) / 1_000_000),
                            backgroundColor: '#fe2718',
                        },
                        {
                            label: 'ACH (Juta Rp)',
                            data: items.map(c => (c.acv ?? 0) / 1_000_000),
                            backgroundColor: '#1f54d9',
                        }
                    ]
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
</script>

@endsection