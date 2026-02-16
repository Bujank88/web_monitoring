@extends('master')
@section('title') {{ $pageTitle ?? 'Report Saldo Agency Advertising' }} @endsection

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

<!-- <div class="filter-card">
    <h5><i class="fas fa-filter"></i> FILTER REPORT SALDO AGENCY ADVERTISING</h5>
    <div class="row">
        <div class="col-md-3">
            <div class="filter-group">
            <label for="remark">Remark</label>
            <select id="remark" name="remark" class="form-control">
                <option value="">Semua Remark</option>
                <option value="Mitra SBP">Mitra SBP</option>
                <option value="Agency">Agency</option>
                <option value="Internal">Internal</option>
            </select>
        </div>
        </div>
    </div>
</div> -->

<div class="card">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="fas fa-wallet mr-2"></i>
            {{ $pageTitle ?? 'Report Saldo Agency Advertising' }}
        </h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="saldoTable" class="table table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Remark</th>
                        <th>Saldo Utama</th>
                        <th>Saldo Monet</th>
                        <th>Saldo Exp Utama</th>
                        <th>Saldo Exp Monet</th>
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

    function formatRupiah(value) {
        var num = Number(value || 0);
        return 'Rp ' + num.toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    var table = $('#saldoTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('report-saldo-advertising.data') }}",
            data: function(d) {
                d.month = $('#month').val();
                    d.remark = $('#remark').val();
            }
        },
        columns: [
            { data: 'email_myads', name: 'a.email_myads' },
            { data: 'remark', name: 'a.remark' },
            { 
                data: 'saldo_utama',
                name: 'b.saldo_utama',
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'saldo_monet',
                name: 'b.saldo_monet',
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'saldo_exp_utama',
                name: 'b.saldo_exp_utama',
                render: function(data) {
                    return data;
                }
            },
            { 
                data: 'saldo_exp_monet',
                name: 'b.saldo_exp_monet',
                render: function(data) {
                    return data;
                }
            }
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: 'Tidak ada data',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            paginate: {
                next: 'Next',
                previous: 'Prev'
            }
        }
    });
    $('#remark').on('change', function() {
        // updateExportLink();
        table.ajax.reload();
    });

});
</script>
@endsection
