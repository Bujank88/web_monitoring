@extends('master')
@section('title') {{ $pageTitle ?? 'Automatech Balance Report' }} @endsection

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
        color: white;
        padding: 12px 15px;
        margin: -20px -20px 15px -20px;
        border-radius: 7px 7px 0 0;
        font-weight: 600;
        font-size: 15px;
    }

    .table th {
        font-weight: bold;
        color: #ffffff !important;
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

<div class="card">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="fas fa-wallet mr-2"></i>
            {{ $pageTitle ?? 'Automatech Balance Report' }}
        </h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="saldoTable" class="table table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Remark</th>
                        <th>Main Balance</th>
                        <th>Monet Balance</th>
                        <th>Main Expiry Balance</th>
                        <th>Monet Expiry Balance</th>
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
$(function() {
    var table = $('#saldoTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route($dataRoute ?? 'report-saldo-automatech.data') }}",
            data: function(d) {
                d.month = $('#month').val();
                d.remark = $('#remark').val();
            }
        },
        columns: [
            { data: 'email_myads', name: 'a.email_myads' },
            { data: 'remark', name: 'a.remark' },
            { data: 'saldo_utama', name: 'b.saldo_utama' },
            { data: 'saldo_monet', name: 'b.saldo_monet' },
            { data: 'saldo_exp_utama', name: 'b.saldo_exp_utama' },
            { data: 'saldo_exp_monet', name: 'b.saldo_exp_monet' }
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: 'No data available',
            search: 'Search:',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            paginate: {
                next: 'Next',
                previous: 'Previous'
            }
        }
    });

    $('#remark').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endsection

