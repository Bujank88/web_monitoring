@extends('master')
@section('title') Summary AM Level UP @endsection

@section('css')
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
        color: #ffffff !important;
        background-color: #dc3545;
    }

    .text-left-custom {
        text-align: left !important;
    }

    .badge-custom {
        padding: 8px 12px;
        font-size: 13px;
        border-radius: 4px;
    }
</style>
@endsection


@section('content')

<div class="row mb-3">
    <div class="col-md-4">
        <select id="filterUser" class="form-control">
            <option value="">-- Semua User --</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <h4 class="mb-0">
            <i class="fas fa-chart-bar mr-2 text-primary"></i>
            <strong>Summary Poin AM Level UP</strong>
        </h4>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title text-white">
                    <i class="fas fa-list mr-2"></i>Data Summary User
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="akunTable" class="table table-bordered table-hover" style="width:100%">
                        <thead class="bg-danger">
                            <tr>
                                <th style="width:5%">No</th>
                                <th style="width:20%">Nama User</th>
                                <th style="width:10%">Jumlah Klien</th>
                                <th style="width:15%">Total Topup</th>
                                <th style="width:10%">Total Poin</th>
                                <th style="width:10%">Redeem</th>
                                <th style="width:10%">Sisa Poin</th>
                                <th style="width:10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection


@section('js')

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
$(document).ready(function() {

    var table = $('#akunTable').DataTable({
        processing: true,
        serverSide: false,
        responsive: true,
        ajax: {
            url: "{{ route('amlevelup.summary-data') }}",
            data: function(d) {
                d.user_id = $('#filterUser').val();
            }
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
            { data: 'nama_user', className: 'text-left-custom' },
            { data: 'jumlah_klien' },
            { 
                data: 'total_topup',
                render: function(data) {
                    return 'Rp ' + data;
                }
            },
            { 
                data: 'total_poin',
                render: function(data) {
                    return '<span class="badge badge-primary badge-custom">' + data + '</span>';
                }
            },
            { 
                data: 'redeem_poin',
                render: function(data) {
                    return '<span class="badge badge-warning badge-custom">' + data + '</span>';
                }
            },
            { 
                data: 'sisa_poin',
                render: function(data) {
                    return '<span class="badge badge-danger badge-custom">' + data + '</span>';
                }
            },
            { data: 'action', orderable: false, searchable: false }
        ],
        order: [[4, 'desc']],
        language: {
            processing: 'Loading...',
            emptyTable: 'Tidak ada data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            lengthMenu: 'Tampilkan _MENU_ data',
            search: 'Cari:',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Selanjutnya',
                previous: 'Sebelumnya'
            }
        }
    });

    $('#filterUser').change(function() {
        table.ajax.reload();
    });

});
</script>

@endsection