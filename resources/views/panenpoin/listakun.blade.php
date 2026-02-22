@extends('master')
@section('title') List Akun Panen Poin @endsection

@section('css')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
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

    .table {
        background-color: #f9f9f9;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        margin-top: 15px;
        border: 0.5px solid #ccc;
    }

    .table th,
    .table td {
        padding: 12px !important;
        font-size: 15px;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
    }

    .table th {
        font-weight: bold;
        text-align: center !important;
        color: #ffffff !important;
        background-color: #dc3545;
    }

    .table tbody tr {
        transition: background-color 0.3s;
    }

    .table tbody tr:hover {
        background-color: #e2e2e2;
    }

    .table tbody tr:nth-child(odd) {
        background-color: #f2f2f2;
    }

    .table tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    .text-left-custom {
        text-align: left !important;
    }

    .badge-custom {
        padding: 8px 12px;
        font-size: 13px;
        border-radius: 4px;
    }

    @media (max-width: 768px) {
        .table th,
        .table td {
            font-size: 12px;
            padding: 8px !important;
        }
    }
</style>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <h4 class="mb-0">
            <i class="fas fa-user-check mr-2 text-primary"></i>
            <strong>Daftar Akun Panen Poin yang Sudah Terdaftar</strong>
        </h4>
    </div>
</div>

<!-- Table Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title text-white">
                    <i class="fas fa-list mr-2"></i>Data Akun Terdaftar
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="akunTable" class="table table-bordered table-hover" style="width:100%">
                        <thead class="bg-danger">
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 20%;">Nama Akun</th>
                                <th style="width: 25%;">Email Client</th>
                                <th style="width: 15%;">Nomor HP</th>
                                <th style="width: 20%;">Nama Canvasser</th>
                                <th style="width: 15%;">Tanggal Terdaftar</th>
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
            <strong>Keterangan:</strong> Halaman ini menampilkan semua akun panen poin yang sudah terdaftar. Setiap akun yang terdaftar akan otomatis mendapatkan notifikasi email dan WhatsApp dengan kredensial akun.
        </div>
    </div>
</div>

@endsection

@section('js')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js"></script>

<script>
    $(document).ready(function() {
        
        // Initialize DataTable
        var table = $('#akunTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('panenpoin.akun-data') }}",
                type: 'GET'
            },
            columns: [
                {
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    },
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'nama_akun',
                    className: 'text-left-custom'
                },
                {
                    data: 'email_client',
                    className: 'text-left-custom'
                },
                {
                    data: 'nomor_hp',
                    render: function(data) {
                        if (data && data !== '-') {
                            return '<span class="badge badge-info badge-custom"><i class="fas fa-phone mr-1"></i>' + data + '</span>';
                        }
                        return '<span class="badge badge-secondary badge-custom">-</span>';
                    }
                },
                {
                    data: 'nama_canvasser',
                    className: 'text-left-custom',
                    render: function(data) {
                        return '<span class="badge badge-primary badge-custom"><i class="fas fa-user mr-1"></i>' + data + '</span>';
                    }
                },
                {
                    data: 'created_at',
                    render: function(data) {
                        return '<span class="badge badge-success badge-custom"><i class="fas fa-calendar-alt mr-1"></i>' + data + '</span>';
                    }
                }
            ],
            order: [
                [5, 'desc']
            ], // Order by tanggal terbaru
            language: {
                processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
                emptyTable: 'Tidak ada data akun untuk ditampilkan',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ akun',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 akun',
                infoFiltered: '(disaring dari _MAX_ total akun)',
                lengthMenu: 'Tampilkan _MENU_ akun',
                search: 'Cari:',
                zeroRecords: 'Akun tidak ditemukan',
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
    });
</script>
@endsection
