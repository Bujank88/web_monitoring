@extends('master')
@section('title', $pageTitle)

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<style>
    .summary-table { width: 100%; background: #f9f9f9; border: .5px solid #ccc; }
    .summary-table th, .summary-table td { padding: 8px !important; border: .5px solid #ccc; text-align: center; vertical-align: middle; white-space: nowrap; }
    .summary-filter { background: #fff; border: 1px solid #dee2e6; border-radius: 10px; }
    .table-responsive { overflow-x: auto; width: 100%; }
</style>
@endsection

@section('content')
<div class="card summary-filter mb-3">
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="merchantSummaryMonth" class="font-weight-bold">Month</label>
                <select id="merchantSummaryMonth" class="form-control">
                    @foreach($months as $month)
                    <option value="{{ $month['value'] }}" {{ $month['selected'] ? 'selected' : '' }}>{{ $month['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h4 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>{{ $pageTitle }}</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="merchantSummaryTable" class="table table-sm table-bordered table-hover summary-table">
                <thead>
                    <tr>
                        <th rowspan="3" style="text-align: center;">Tanggal</th>
                        <th colspan="{{ (count($merchants) * 2) + 2 }}" style="background:#f8d7da; text-align: center;">
                            Summary Merchant 1Synergy | Bulan: <span id="merchantSummaryMonthLabel">{{ collect($months)->firstWhere('selected', true)['label'] ?? now()->translatedFormat('F Y') }}</span>
                        </th>
                    </tr>
                    <tr>
                        @foreach($merchants as $merchant)
                        <th colspan="2" style="background:#dbeafe; text-align: center;">{{ $merchant['label'] }}</th>
                        @endforeach
                        <th colspan="2" style="background:#f62b3c;color:#fff; text-align: center;">Total</th>
                    </tr>
                    <tr>
                        @foreach($merchants as $merchant)
                        <th style="background:#dbeafe; text-align: center;">Jumlah Campaign</th>
                        <th style="background:#dbeafe; text-align: center;">Total Balance Terpakai</th>
                        @endforeach
                        <th style="background:#f62b3c;color:#fff; text-align: center;">Jumlah Campaign</th>
                        <th style="background:#f62b3c;color:#fff; text-align: center;">Total Balance Terpakai</th>
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
<script>
$(function () {
    const merchants = @json($merchants);
    const columns = [{ data: 'date' }];

    merchants.forEach(function (merchant) {
        columns.push({ data: merchant.key + '_campaign', defaultContent: 0 });
        columns.push({
            data: merchant.key + '_balance',
            defaultContent: '0',
            render: function (value) { return '<div class="text-right">Rp ' + (value || '0') + '</div>'; }
        });
    });

    columns.push({ data: 'total_campaign', defaultContent: 0, className: 'font-weight-bold' });
    columns.push({
        data: 'total_balance',
        defaultContent: '0',
        render: function (value) { return '<div class="text-right font-weight-bold">Rp ' + (value || '0') + '</div>'; }
    });

    const table = $('#merchantSummaryTable').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        paging: false,
        searching: false,
        autoWidth: false,
        ajax: {
            url: @json($dataUrl),
            data: function (data) { data.month = $('#merchantSummaryMonth').val(); }
        },
        columns: columns,
        rowCallback: function (row, data) {
            if (data.date === 'Total Keseluruhan') $(row).addClass('table-info font-weight-bold');
        }
    });

    $('#merchantSummaryMonth').on('change', function () {
        $('#merchantSummaryMonthLabel').text($('#merchantSummaryMonth option:selected').text());
        table.ajax.reload();
    });
});
</script>
@endsection
