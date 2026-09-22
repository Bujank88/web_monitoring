@extends('master')
@section('title') {{ $pageTitle ?? 'Data Dormant CDSI' }} @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
    .metric-card {
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }

    .metric-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 6px;
    }

    .metric-value {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
    }

    .table {
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        margin-top: 15px;
        border: 0.5px solid #ccc;
    }

    .table th,
    .table td {
        padding: 8px !important;
        font-size: 13px;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
        white-space: nowrap;
    }

    .table th {
        font-weight: bold;
        color: #ffffff !important;
        background: #6f42c1;
    }
</style>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card metric-card border-left-primary h-100">
            <div class="card-body">
                <div class="metric-label text-primary">Total Data Dormant</div>
                <div class="metric-value" id="countDormant">0</div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card metric-card border-left-success h-100">
            <div class="card-body">
                <div class="metric-label text-success">Total Settlement</div>
                <div class="metric-value" id="countSettlement">Rp 0</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header" style="background:#6f42c1; color:#fff;">
                <h3 class="card-title mb-0">
                    <i class="fas fa-bed mr-2"></i>{{ $pageTitle ?? 'Data Dormant CDSI' }}
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="cdsiDormantTable" class="table table-bordered table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Nomor</th>
                                <th>Nama Instansi</th>
                                <th>Tanggal Terakhir Transaksi</th>
                                <th>Total Settlement</th>
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
        function formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
        }

        $('#cdsiDormantTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('report-cdsi-dormant.data') }}"
            },
            columns: [
                { data: 'email', name: 'cd.email' },
                { data: 'nomor', name: 'cd.nomor' },
                { data: 'nama_instansi', name: 'cd.nama_instansi' },
                { data: 'last_tgl_transaksi', name: 'cd.last_tgl_transaksi' },
                { data: 'total_settlement', name: 'cd.total_settlement' }
            ],
            order: [[3, 'asc']],
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, 'Semua']
            ],
            language: {
                emptyTable: 'No data available',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                lengthMenu: 'Show _MENU_ entries',
                search: 'Search:',
                zeroRecords: 'No matching records found',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            },
            drawCallback: function(settings) {
                var summary = (settings.json && settings.json.summary) ? settings.json.summary : {};
                $('#countDormant').text(new Intl.NumberFormat('id-ID').format(summary.total_data || 0));
                $('#countSettlement').text(formatRupiah(summary.total_settlement || 0));
            }
        });
    });
</script>
@endsection



