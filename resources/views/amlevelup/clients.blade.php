@extends('master')
@section('title') Data Klien AM Level UP @endsection

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
    <div class="col-12">
        <h4 class="mb-0">
            <i class="fas fa-users mr-2 text-primary"></i>
            <strong>Daftar Klien AM Level UP</strong>
        </h4>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title text-white">
                    <i class="fas fa-list mr-2"></i>Data Klien & Total Topup
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="clientTable" class="table table-bordered table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:5%">No</th>
                                <th style="width:20%">Nama Klien</th>
                                <th style="width:15%">User Pemilik</th>
                                <th style="width:20%">Email MyAds</th>
                                <th style="width:15%">Total Topup</th>
                                <th style="width:10%">Total Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clients as $index => $client)
                                @php
                                    $totalTopup = \DB::table('report_balance_top_up')
                                        ->whereRaw('LOWER(email_client) = ?', [strtolower($client->myads_account)])
                                        ->sum('total_settlement_klien');

                                    $totalPoint = floor($totalTopup / 1000000);
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="text-left-custom">
                                        <strong>{{ $client->company_name }}</strong>
                                    </td>
                                    <td>     
                                        {{ $client->user->name ?? '-' }}
                                    </td>
                                    <td class="text-left-custom">
                                        {{ $client->myads_account }}
                                    </td>
                                    <td>
                                        <span class="badge badge-success badge-custom">
                                            Rp {{ number_format($totalTopup,0,',','.') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-danger badge-custom">
                                            {{ $totalPoint }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
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

    $('#clientTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[4, 'desc']],
        language: {
            emptyTable: 'Tidak ada data klien',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ klien',
            lengthMenu: 'Tampilkan _MENU_ klien',
            search: 'Cari:',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Selanjutnya',
                previous: 'Sebelumnya'
            }
        }
    });

});
</script>

@endsection