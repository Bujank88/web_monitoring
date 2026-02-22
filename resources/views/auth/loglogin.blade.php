@extends('master')

@section('title', 'Log Login')
@section('css')
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
</style>
@endsection
@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Log Login</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Log Login</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h3 class="card-title">Data User Login</h3>
                    </div>
                    <div class="card-body">
                        <!-- Filter Section -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="start_date">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                            <div class="col-md-3">
                                <label for="role_filter">Role</label>
                                <select class="form-control" id="role_filter" name="role_filter">
                                    <option value="all">Semua Role</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Tsel">Tsel</option>
                                    <option value="cvsr">Canvasser</option>
                                    <option value="Treg">Treg</option>
                                    <option value="b2b">B2B</option>
                                    <option value="TL">TL</option>
                                    <option value="User">User</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-primary btn-block" id="filter_btn">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- DataTable -->
                        <div class="table-responsive">
                            <table id="loglogin-table" class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Tanggal</th>
                                        <th class="text-center">Nama</th>
                                        <th class="text-center">Email</th>
                                        <th class="text-center">Role</th>
                                        <th class="text-center">Last Access</th>
                                        <th class="text-center">Status</th>
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
    </div>
</section>

@endsection

@section('js')
<script>
    $(document).ready(function() {
        var table = $('#loglogin-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('loglogin.data') }}",
                data: function(d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.role = $('#role_filter').val();
                }
            },
            columns: [
                {
                    data: null,
                    name: 'no',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'tgl',
                    name: 'tgl'
                },
                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'role',
                    name: 'role',
                    render: function(data) {
                        var badge = 'badge-secondary';
                        var displayText = data;
                        
                        if (data === 'Admin') badge = 'badge-warning';
                        else if (data === 'Tsel') badge = 'badge-success';
                        else if (data === 'cvsr') {
                            badge = 'badge-primary';
                            displayText = 'Canvasser';
                        }
                        else if (data === 'Treg') badge = 'badge-info';
                        else if (data === 'b2b') {
                            badge = 'badge-primary';
                            displayText = 'B2B';
                        }
                        
                        return '<span class="badge ' + badge + '">' + displayText + '</span>';
                    }
                },
                {
                    data: 'updated_at',
                    name: 'updated_at'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [1, 'desc'],
                [5, 'desc']
            ],
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            responsive: true,
            language: {
                processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                zeroRecords: "Data tidak ditemukan",
                info: "Menampilkan halaman _PAGE_ dari _PAGES_",
                infoEmpty: "Tidak ada data yang tersedia",
                infoFiltered: "(difilter dari _MAX_ total data)",
                search: "Cari:",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            }
        });

        // Filter button click
        $('#filter_btn').on('click', function() {
            table.ajax.reload();
        });

        // Enter key on filter inputs
        $('#start_date, #end_date, #role_filter').on('keypress', function(e) {
            if (e.which === 13) {
                table.ajax.reload();
            }
        });

        // Role filter change
        $('#role_filter').on('change', function() {
            table.ajax.reload();
        });
    });
</script>
@endsection
