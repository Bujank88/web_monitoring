@extends('master')
@section('title') Transaction Detail @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<style>
    .filter-card {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .filter-card h5 {
        background-color: #495057;
        color: #fff;
        padding: 12px 15px;
        margin: -20px -20px 15px -20px;
        border-radius: 7px 7px 0 0;
        font-weight: 600;
        font-size: 15px;
    }

    .table th {
        font-weight: 700;
        color: #fff !important;
        background: #17a2b8;
        text-align: center;
        white-space: nowrap;
    }

    .table td {
        text-align: center;
        white-space: nowrap;
    }
</style>
@endsection

@section('content')
<div class="filter-card">
    <h5><i class="fas fa-filter"></i> FILTER TRANSACTION DETAIL</h5>
    <div class="row">
        <div class="col-md-6">
            <label>Email</label>
            <input type="text" class="form-control" value="{{ $email }}" readonly>
        </div>
        <div class="col-md-3">
            <label for="filter_month">Bulan Transaksi</label>
            <input type="month" id="filter_month" class="form-control" value="{{ $month }}">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="w-100">
                <button type="button" id="btnFilter" class="btn btn-info btn-sm">
                    <i class="fas fa-search mr-1"></i> Tampilkan
                </button>
            </div>
        </div>
    </div>
    <div class="mt-3">
        <small class="text-muted">Menampilkan transaksi berdasarkan email yang dipassing dari detail lead atau detail akun.</small>
    </div>
</div>

<div class="card">
    <div class="card-header bg-info">
        <h5 class="mb-0">
            <i class="fas fa-receipt mr-2"></i>Transaction Detail
            <small class="ml-2">{{ $email }} | {{ $periodLabel }}</small>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="transactionDetailTable" class="table table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>No Invoice</th>
                        <th>Email</th>
                        <th>Pemilik</th>
                        <th>Company Name</th>
                        <th>Tanggal Transaksi</th>
                        <th>Total Settlement</th>
                        <th>Payment Method</th>
                        <th>Paid Date</th>
                        <th>Voucher Code</th>
                    </tr>
                </thead>
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
$(function () {
    const fixedEmail = @json($email);

    var table = $('#transactionDetailTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: false,
        ajax: {
            url: "{{ route('transaction-detail.data') }}",
            data: function (d) {
                d.email = fixedEmail;
                d.month = $('#filter_month').val();
            }
        },
        columns: [
            { data: 'no_invoice', name: 'rb.no_invoice' },
            { data: 'email_client', name: 'rb.email_client' },
            { data: 'owner_name', name: 'owner_name' },
            { data: 'company_name', name: 'rb.company_name' },
            { data: 'tgl_transaksi', name: 'rb.tgl_transaksi' },
            { data: 'total_settlement', name: 'total_settlement' },
            { data: 'payment_method_name', name: 'rb.payment_method_name' },
            { data: 'paid_date', name: 'rb.paid_date' },
            { data: 'voucher_code', name: 'dv.voucher_code' }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: 'Tidak ada transaksi untuk email dan bulan yang dipilih.',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            paginate: { next: 'Next', previous: 'Prev' }
        }
    });

    $('#btnFilter').on('click', function () {
        table.ajax.reload();
    });

    $('#filter_month').on('change', function () {
        table.ajax.reload();
    });
});
</script>
@endsection
