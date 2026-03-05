@extends('master')
@section('title') Report Balance Top Up @endsection

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
        background: #dc3545;
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
    <h5><i class="fas fa-filter"></i> FILTER REPORT BALANCE TOP UP</h5>
    <div class="row">
        <div class="col-md-3">
            <label for="filter_email">Email</label>
            <input type="text" id="filter_email" class="form-control" placeholder="Masukkan email">
        </div>
        <div class="col-md-3">
            <label for="filter_start_date">Tanggal Transaksi Start</label>
            <input type="date" id="filter_start_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="filter_end_date">Tanggal Transaksi End</label>
            <input type="date" id="filter_end_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="filter_name">Name/Owner (LIKE)</label>
            <input type="text" id="filter_name" class="form-control" placeholder="Masukkan name/owner/company">
        </div>
    </div>
    <div class="mt-3">
        <button type="button" id="btnFilter" class="btn btn-danger btn-sm">
            <i class="fas fa-search mr-1"></i> Tampilkan
        </button>
        <button type="button" id="btnReset" class="btn btn-secondary btn-sm">
            <i class="fas fa-undo mr-1"></i> Reset
        </button>
        <small class="text-muted ml-2">Jika filter kosong semua, data tidak ditampilkan.</small>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger">
        <h5 class="mb-0"><i class="fas fa-wallet mr-2"></i>Report Balance Top Up</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="balanceTopupTable" class="table table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>No Invoice</th>
                        <th>Email</th>
                        <th>Pemilik</th>
                        <th>Company Name</th>
                        <th>Tanggal Transaksi</th>
                        <th>Amount</th>
                        <th>Discount Voucher</th>
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
    var table = $('#balanceTopupTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: false,
        ajax: {
            url: "{{ route('report-balance-top-up.data') }}",
            data: function (d) {
                d.email = $('#filter_email').val();
                d.start_date = $('#filter_start_date').val();
                d.end_date = $('#filter_end_date').val();
                d.name = $('#filter_name').val();
            }
        },
        columns: [
            { data: 'no_invoice', name: 'rb.no_invoice' },
            { data: 'email_client', name: 'rb.email_client' },
            { data: 'owner_name', name: 'owner_name' },
            { data: 'company_name', name: 'rb.company_name' },
            { data: 'tgl_transaksi', name: 'rb.tgl_transaksi' },
            { data: 'amount', name: 'amount' },
            { data: 'discount_voucher', name: 'discount_voucher' },
            { data: 'total_settlement', name: 'total_settlement' },
            { data: 'payment_method_name', name: 'rb.payment_method_name' },
            { data: 'paid_date', name: 'rb.paid_date' },
            { data: 'voucher_code', name: 'dv.voucher_code' }
        ],
        order: [[9, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: 'Silakan isi minimal 1 filter untuk menampilkan data.',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            paginate: { next: 'Next', previous: 'Prev' }
        }
    });

    $('#btnFilter').on('click', function () {
        table.ajax.reload();
    });

    $('#btnReset').on('click', function () {
        $('#filter_email').val('');
        $('#filter_start_date').val('');
        $('#filter_end_date').val('');
        $('#filter_name').val('');
        table.ajax.reload();
    });
});
</script>
@endsection
