@extends('master')
@section('title') {{ $pageTitle ?? 'Daily Top Up Referral SBP' }} @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<style>
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
        padding: 8px !important;
        font-size: 14px;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
        vertical-align: middle;
    }

    .table thead th {
        font-weight: 700;
        white-space: nowrap;
    }

    .filter-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        width: 100%;
    }
</style>
@endsection

@section('content')
<div class="card filter-card">
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="filterMonthSbp" class="font-weight-bold">Month</label>
                <select id="filterMonthSbp" class="form-control">
                    @foreach ($months as $m)
                    <option value="{{ $m['value'] }}" {{ $m['selected'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-info text-white">
        <h4 class="mb-0">
            <i class="fas fa-chart-bar mr-2"></i>{{ $pageTitle ?? 'Daily Top Up Referral SBP' }}
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm w-100 table-bordered table-hover" id="sbpReferralTopupTable">
                <thead>
                    <tr>
                        <th rowspan="3" style="background-color: #f8f9fa; text-align: center; vertical-align: middle;">Tanggal</th>
                        <th colspan="{{ (count($channels) * 2) + 2 }}" class="text-center" style="background-color: #d1ecf1;">
                            Report Daily TopUp Referral SBP | Bulan: <span id="displayedMonthSbp">{{ $months[array_search(true, array_column($months, 'selected'))]['label'] ?? now()->translatedFormat('F Y') }}</span>
                        </th>
                    </tr>
                    <tr>
                        @foreach ($channels as $channel)
                        <th colspan="2" class="text-center" style="background-color: {{ $channel['color'] }};">{{ $channel['label'] }}</th>
                        @endforeach
                        <th colspan="2" class="text-center" style="background-color: #f62b3c; color: #fff;">Total</th>
                    </tr>
                    <tr>
                        @foreach ($channels as $channel)
                        <th class="text-center" style="background-color: {{ $channel['color'] }};">Akun</th>
                        <th class="text-center" style="background-color: {{ $channel['color'] }};">Total Deposit</th>
                        @endforeach
                        <th class="text-center" style="background-color: #f62b3c; color: #fff;">Akun</th>
                        <th class="text-center" style="background-color: #f62b3c; color: #fff;">Total Deposit</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fas fa-list mr-2"></i>Detail Akun Top Up
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="sbpReferralTopupDetailTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Email</th>
                                <th>Channel</th>
                                <th>Amount</th>
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
        const channels = @json($channels);
        const columns = [{
            data: 'date',
            render: function(data, type, row) {
                const className = data === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                return '<div class="' + className + '" style="text-align:center;">' + (data || '-') + '</div>';
            }
        }];

        channels.forEach(function(channel) {
            columns.push({
                data: channel.key + '_user',
                render: function(data, type, row) {
                    const className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                    return '<div class="' + className + '">' + (data || 0) + '</div>';
                }
            });

            columns.push({
                data: channel.key + '_settle',
                render: function(data, type, row) {
                    const className = row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
                    return '<div style="text-align:right;" class="' + className + '">Rp ' + (data || '0') + '</div>';
                }
            });
        });

        columns.push({
            data: 'total_user',
            render: function(data) {
                return '<div class="font-weight-bold">' + (data || 0) + '</div>';
            }
        });

        columns.push({
            data: 'total',
            render: function(data) {
                return '<div style="text-align:right;" class="font-weight-bold">Rp ' + (data || '0') + '</div>';
            }
        });

        const table = $('#sbpReferralTopupTable').DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            paging: false,
            searching: false,
            autoWidth: false,
            ajax: {
                url: "{{ route('pilot-sbp-sme.topup-referral-sbp.data') }}",
                type: 'GET',
                data: function(d) {
                    d.month = $('#filterMonthSbp').val();
                }
            },
            columns: columns,
            rowCallback: function(row, data) {
                if (data.date === 'Total Keseluruhan') {
                    $(row).addClass('table-info');
                }
            }
        });

        const detailTable = $('#sbpReferralTopupDetailTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            ajax: {
                url: "{{ route('pilot-sbp-sme.topup-referral-sbp.detail-data') }}",
                type: 'GET',
                data: function(d) {
                    d.month = $('#filterMonthSbp').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'paid_date', name: 'paid_date', className: 'text-center' },
                { data: 'customer_email', name: 'customer_email' },
                { data: 'channel', name: 'channel', className: 'text-center' },
                { data: 'amount', name: 'amount', className: 'text-right' }
            ],
            order: [[1, 'desc']]
        });

        $('#filterMonthSbp').on('change', function() {
            $('#displayedMonthSbp').text($('#filterMonthSbp option:selected').text());
            table.ajax.reload();
            detailTable.ajax.reload();
        });
    });
</script>
@endsection
