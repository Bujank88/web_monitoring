@extends('master')
@section('title') {{ $pageTitle ?? 'Top Up Active' }} @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<style>
    .filter-card {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .table th,
    .table td {
        vertical-align: middle;
        font-size: 13px;
    }

    .table thead th {
        white-space: nowrap;
    }
</style>
@endsection

@section('content')
<div class="filter-card">
    <div class="row align-items-end">
        <div class="col-md-4">
            <label for="monthFilter">Month</label>
            <select id="monthFilter" class="form-control">
                @foreach ($months as $m)
                <option value="{{ $m['value'] }}" {{ $m['selected'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-8 text-md-right mt-3 mt-md-0">
            <a class="btn btn-success" id="btnExportProvince" href="#">
                <i class="fas fa-file-excel mr-1"></i> Download Excel
            </a>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-6 col-md-6 mb-3">
        <div class="card border-left-primary h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total User ID</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalUserIdValue">0</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-6 mb-3">
        <div class="card border-left-success h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Settlement</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalSettlementValue">Rp 0</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="fas fa-map-marker-alt mr-2"></i>{{ $pageTitle ?? 'Top Up Active' }}
        </h3>
        <span class="font-weight-bold" id="displayedMonthLabel">{{ $months[array_search(true, array_column($months, 'selected'))]['label'] ?? now()->translatedFormat('F Y') }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="cdsiProvinceTable" class="table table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Province</th>
                        <th>User ID</th>
                        <th>Email</th>
                        <th>Bulan</th>
                        <th>Total Settlement</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        function updateExportLink() {
            var month = $('#monthFilter').val();
            $('#btnExportProvince').attr('href', "{{ route('report-cdsi-province.export') }}?month=" + encodeURIComponent(month));
        }

        var table = $('#cdsiProvinceTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            ajax: {
                url: "{{ route('report-cdsi-province.data') }}",
                data: function(d) {
                    d.month = $('#monthFilter').val();
                },
                dataSrc: function(json) {
                    if (json.totals) {
                        $('#totalUserIdValue').text(json.totals.total_user_ids || 0);
                        $('#totalSettlementValue').text(json.totals.total_settlement_format || 'Rp 0');
                    }
                    return json.data || [];
                }
            },
            columns: [
                { data: 'data_province_name', name: 'rp.data_province_name' },
                { data: 'user_id', name: 'rp.user_id' },
                { data: 'email_client', name: 'rp.email_client' },
                { data: 'tanggal_format', name: 'tgl_transaksi', orderable: false, searchable: false },
                { data: 'total_settlement_format', name: 'total_settlement_klien', searchable: false }
            ]
        });

        $('#monthFilter').on('change', function() {
            $('#displayedMonthLabel').text($('#monthFilter option:selected').text());
            updateExportLink();
            table.ajax.reload();
        });

        updateExportLink();
    });
</script>
@endsection

