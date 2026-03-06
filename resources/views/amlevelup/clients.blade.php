@extends('master')

@section('title') Data Klien AM Level UP @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
.card {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
}

.table th {
    background-color: #dc3545;
    color: #fff !important;
    text-align: center;
}

.table td {
    text-align: center;
}

.text-left-custom {
    text-align: left !important;
}

.badge-custom {
    padding: 6px 10px;
    font-size: 13px;
}
</style>
@endsection

@section('content')

<div class="row mb-3">
    <div class="col-12">
        <h4>
            <i class="fas fa-users text-danger"></i>
            <strong>Daftar Klien AM Level UP</strong>
        </h4>
    </div>
</div>

{{-- FILTER SECTION --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET">
            <div class="row">

                {{-- Filter AM --}}
                <div class="col-md-3">
                    <label>User AM</label>
                    <select name="user_id" class="form-control">
                        <option value="">-- Semua AM --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ $userId == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Bulan --}}
                <div class="col-md-3">
                    <label>Bulan Transaksi</label>
                    <input type="month" name="month"
                           value="{{ $month ?? '' }}"
                           class="form-control">
                </div>

                <div class="col-md-2 align-self-end">
                    <button class="btn btn-danger btn-block">
                        Filter
                    </button>
                </div>

                <div class="col-md-2 align-self-end">
                    <a href="{{ url()->current() }}" class="btn btn-secondary btn-block">
                        Reset
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>


{{-- TABLE SECTION --}}
<div class="card">
    <div class="card-header bg-danger text-white">
        <strong>Data Klien & Total Topup</strong>
    </div>
    <div class="card-body">

        <div class="table-responsive">
            <table id="clientTable" class="table table-bordered table-hover" width="100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>User AM</th>
                        <th>Nama PIC</th>
                        <th>Email MyAds</th>
                        <th>Tgl Transaksi Terakhir</th>
                        <th>Total Topup</th>
                        <th>Total Poin</th>
                    </tr>
                </thead>
                <tbody>

                @foreach($clients as $index => $client)

                @php
                    $email = strtolower($client->myads_account);

                    $clientTopups = $topups[$email] ?? collect();

                    // Total topup dari semua bulan
                    $totalTopup = $clientTopups->sum('total_topup');

                    // Total poin sudah dihitung per bulan di query (tidak carry)
                    $totalPoint = $clientTopups->sum('total_point');

                    // Ambil tanggal transaksi terakhir dari semua bulan
                    $tglTransaksi = $clientTopups->max('last_transaction_date');
                @endphp

                <tr>
                    <td>{{ $index + 1 }}</td>


                    <td>
                        {{ $client->user->name ?? '-' }}
                    </td>

                    
                    <td class="text-left-custom">
                        <strong>{{ $client->company_name }}</strong>
                    </td>

                    <td class="text-left-custom">
                        {{ $client->myads_account }}
                    </td>

                    <td>
                        @if($tglTransaksi)
                            {{ \Carbon\Carbon::parse($tglTransaksi)->format('d M Y') }}
                        @else
                            -
                        @endif
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
        order: [[5, 'desc']], // sort by total topup
        language: {
            emptyTable: "Tidak ada data klien",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

});
</script>

@endsection