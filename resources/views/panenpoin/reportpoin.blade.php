@extends('master')
@section('title') Report Panen Poin @endsection

@section('css')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">
<!-- DataTables Responsive CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

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
        text-align: center !important;
        color: #ffffff !important;
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

    .filter-section {
        background: #f8f9fc;
        padding: 15px;
        border-radius: 0.35rem;
        margin-bottom: 20px;
    }

    /* Filter Card Styling */
    .filter-card {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .filter-card h5 {
        background-color: #495057;
        color: white;
        padding: 12px 15px;
        margin: -20px -20px 15px -20px;
        border-radius: 7px 7px 0 0;
        font-weight: 600;
        font-size: 15px;
    }

    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 6px;
        color: #333;
    }

    .filter-group small {
        font-size: 11px;
        color: #6c757d;
        margin-top: 2px;
        font-weight: normal;
    }

    .filter-group input,
    .filter-group select {
        width: 100%;
    }

    .loading-spinner {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        text-align: center;
    }

    .loading-spinner.active {
        display: block;
    }

    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
    }

    .loading-overlay.active {
        display: block;
    }

    .spinner-border-sm {
        width: 2rem;
        height: 2rem;
    }

    /* Month Display */
    .month-display {
        background: linear-gradient(87deg, #e74a3b 0, #be2617 100%);
        color: white;
        padding: 12px 15px;
        border-radius: 6px;
        font-weight: 600;
        margin-bottom: 20px;
        display: inline-block;
    }
</style>
@endsection

@section('content')
<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay"></div>
<div class="loading-spinner" id="loadingSpinner">
    <div class="spinner-border spinner-border-sm text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
    <p class="mt-2" style="color: #333; font-weight: 500;">Loading data...</p>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h5 style="margin: 0;"><i class="fas fa-filter"></i> FILTER DATA POIN CANVASSER</h5>
        <div style="background: linear-gradient(87deg, #e74a3b 0, #be2617 100%); color: white; padding: 8px 15px; border-radius: 6px; font-weight: 600; font-size: 14px;">
            <i class="fas fa-calendar-alt mr-2"></i>
            <strong>Bulan:</strong> 
            <span id="selectedMonth">{{ $months[array_search(true, array_column($months, 'selected'))]['label'] ?? 'Januari 2026' }}</span>
        </div>
    </div>
    
    <div class="filter-row">
        <div class="filter-group">
            <label for="tanggal">Bulan</label>
            <select id="tanggal" name="tanggal" class="form-control">
                @foreach ($months as $month)
                <option value="{{ $month['value'] }}" {{ $month['selected'] ? 'selected' : '' }}>
                    {{ $month['label'] }}
                </option>
                @endforeach
            </select>
            <small>Pilih bulan untuk melihat data poin canvasser</small>
        </div>

        <div class="filter-group">
            <label for="source">Source</label>
            <select id="source" name="source" class="form-control">
                <option value="">Semua Source</option>
                <option value="user_panen_poin">User Panen Poin</option>
                <option value="leads_master">Leads Master</option>
            </select>
            <small>Pilih sumber data untuk memfilter tipe akun</small>
        </div>

        <div class="filter-group">
            <label for="remark">Kategori Remark</label>
            <select id="remark" name="remark" class="form-control">
                <option value="">Semua Remark</option>
                <option value="Rookie">🥉 Rookie (0-100)</option>
                <option value="Rising Star">🥈 Rising Star (101-200)</option>
                <option value="Champion">🥇 Champion (201+)</option>
            </select>
            <small>Pilih kategori untuk melihat performa canvasser</small>
        </div>

        <div class="filter-group">
            <button id="btn-export" class="btn btn-success w-100" style="height: 38px;">
                <i class="fa fa-file-excel"></i> Export Excel
            </button>
            <small style="color: #28a745; margin-top: 6px;">Download data sesuai filter</small>
        </div>
    </div>
</div>

<!-- Table Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title text-white">
                    <i class="fas fa-chart-line mr-2"></i>Data Poin Canvasser
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="reportTable" class="table table-bordered table-hover" style="width:100%">
                        <thead class="bg-danger">
                            <tr>
                                <th>No</th>
                                <th>Nama Canvasser</th>
                                <th>Email Client</th>
                                <th>Nomor HP Client</th>
                                <th>Source</th>
                                <th>Total Settlement</th>
                                <th>Poin Paket</th>
                                <th>Total Poin</th>
                                <th>Poin Redeem</th>
                                <th>Poin Sisa</th>
                                <th>Remark</th>
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

<!-- Info Box -->
<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Keterangan:</strong> Setiap Rp 250.000 settlement = 1 poin. Data menampilkan bulan berjalan dengan akumulasi poin dari bulan sebelumnya di tahun yang sama.
        </div>
    </div>
</div>

@endsection

@section('js')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>
<!-- DataTables Responsive JS -->
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
    $(document).ready(function() {
        
        // Function untuk show loading
        function showLoading() {
            $('#loadingOverlay').addClass('active');
            $('#loadingSpinner').addClass('active');
        }

        // Function untuk hide loading
        function hideLoading() {
            $('#loadingOverlay').removeClass('active');
            $('#loadingSpinner').removeClass('active');
        }
        
        // Initialize DataTable
        var table = $('#reportTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('panenpoin.report-data') }}",
                data: function(d) {
                    d.tanggal = $('#tanggal').val();
                    d.source = $('#source').val();
                    d.remark = $('#remark').val();
                },
                beforeSend: function() {
                    showLoading();
                },
                complete: function() {
                    hideLoading();
                }
            },
            columns: [{
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    },
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'nama_canvasser'
                },
                {
                    data: 'email_client'
                },
                {
                    data: 'nomor_hp_client'
                },
                {
                    data: 'source',
                    render: function(data) {
                        if (data === 'user_panen_poin') {
                            return '<span class="badge badge-primary"><i class="fas fa-user-edit"></i> Panen Poin</span>';
                        } else {
                            return '<span class="badge badge-secondary"><i class="fas fa-database"></i> Leads Master</span>';
                        }
                    }
                },
                {
                    data: 'total_settlement',
                    render: function(data) {
                        return 'Rp ' + data;
                    }
                },
                {
                    data: 'poin_package',
                    render: function(data) {
                        return '<span class="badge badge-primary">' + data + ' Poin</span>';
                    }
                },
                {
                    data: 'poin',
                    render: function(data) {
                        return '<span class="badge badge-info">' + data + ' Poin</span>';
                    }
                },
                {
                    data: 'poin_redeem',
                    render: function(data) {
                        return '<span class="badge badge-warning">' + data + ' Poin</span>';
                    }
                },
                {
                    data: 'poin_sisa',
                    render: function(data) {
                        return '<span class="badge badge-success badge-poin">' + data + ' Poin</span>';
                    }
                },
                {
                    data: 'remark',
                    render: function(data) {
                        if (data === 'Rookie') {
                            return '<span class="badge badge-pill" style="background: linear-gradient(135deg, #CD7F32 0%, #996515 100%); color: white; font-size: 14px; padding: 8px 15px;"><i class="fas fa-medal"></i> 🥉 Rookie</span>';
                        } else if (data === 'Rising Star') {
                            return '<span class="badge badge-pill" style="background: linear-gradient(135deg, #C0C0C0 0%, #808080 100%); color: white; font-size: 14px; padding: 8px 15px;"><i class="fas fa-medal"></i> 🥈 Rising Star</span>';
                        } else if (data === 'Champion') {
                            return '<span class="badge badge-pill" style="background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: white; font-size: 14px; padding: 8px 15px;"><i class="fas fa-medal"></i> 🥇 Champion</span>';
                        }
                        return data;
                    }
                }
            ],
            order: [
                [8, 'desc']
            ], // Order by poin_sisa descending
            language: {
                processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
                emptyTable: 'Tidak ada data untuk ditampilkan',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(disaring dari _MAX_ total data)',
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                zeroRecords: 'Data tidak ditemukan',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya'
                }
            },
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Semua"]
            ]
        });
        
        // Event onchange untuk filter tanggal
        $('#tanggal').on('change', function() {
            // Update label bulan yang ditampilkan
            var selectedText = $('#tanggal option:selected').text();
            $('#selectedMonth').text(selectedText);
            
            // Reload data table
            table.ajax.reload();
        });
        
        // Event onchange untuk filter source
        $('#source').on('change', function() {
            table.ajax.reload();
        });
        
        // Event onchange untuk filter remark
        $('#remark').on('change', function() {
            table.ajax.reload();
        });

        // Export button click
        $('#btn-export').click(function() {
            var tanggal = $('#tanggal').val();
            var source = $('#source').val();
            var remark = $('#remark').val();
            var exportUrl = "{{ route('panenpoin.export') }}" + "?tanggal=" + tanggal + "&source=" + source + "&remark=" + remark;
            window.location.href = exportUrl;
        });
    });
</script>
@endsection