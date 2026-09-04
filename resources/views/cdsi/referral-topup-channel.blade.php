@extends('master')
@php($brandLabel = $brandLabel ?? 'CDSI')
@section('title') {{ $pageTitle ?? 'Daily Top Up Referral CDSI' }} @endsection

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
                <label for="filterMonthCdsi" class="font-weight-bold">Month</label>
                <select id="filterMonthCdsi" class="form-control">
                    @foreach ($months as $m)
                    <option value="{{ $m['value'] }}" {{ $m['selected'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h4 class="mb-0">
            <i class="fas fa-chart-bar mr-2"></i>{{ $pageTitle ?? 'Daily Top Up Referral CDSI' }}
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm w-100 table-bordered table-hover" id="cdsiReferralTopupTable">
                <thead>
                    <tr>
                        <th rowspan="3" style="background-color: #f8f9fa; text-align: center; vertical-align: middle;">Tanggal</th>
                        <th colspan="{{ (count($channels) * 2) + 2 }}" class="text-center" style="background-color: #f8d7da;">
                            Report Daily TopUp Referral {{ $brandLabel }} | Bulan: <span id="displayedMonthCdsi">{{ $months[array_search(true, array_column($months, 'selected'))]['label'] ?? now()->translatedFormat('F Y') }}</span>
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
            <table class="table table-sm w-100 table-bordered table-hover" id="cdsiReferralTopupDetailTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Paid Date</th>
                        <th>ID Transaksi</th>
                        <th>Email</th>
                        <th>Amount</th>
                        <th>Status Transfer Saldo</th>
                        @if(in_array(auth()->user()->role, ['Admin', 'KSS', 'kss'], true))
                        <th>Invoice Number</th>
                        <th>Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@if(in_array(auth()->user()->role, ['Admin', 'KSS', 'kss'], true))
<div class="modal fade" id="myadsInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="myadsInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="myadsInvoiceForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="myadsInvoiceModalLabel">Input No Invoice MyAds</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modalTransactionId" name="transaction_id">
                    <div class="form-group">
                        <label for="modalTransactionIdDisplay">ID Transaksi</label>
                        <input type="text" id="modalTransactionIdDisplay" class="form-control" readonly>
                    </div>
                    <div class="form-group mb-0">
                        <label for="modalMyadsInvoice">No Invoice MyAds</label>
                        <input type="text" id="modalMyadsInvoice" name="id_transaksi_myads" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitMyadsInvoice">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        const table = $('#cdsiReferralTopupTable').DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            paging: false,
            searching: false,
            autoWidth: false,
            ajax: {
                url: @json($topupDataUrl ?? route('cdsi.referral-topup-channel.data')),
                type: 'GET',
                data: function(d) {
                    d.month = $('#filterMonthCdsi').val();
                }
            },
            columns: columns,
            rowCallback: function(row, data) {
                if (data.date === 'Total Keseluruhan') {
                    $(row).addClass('table-info');
                }
            }
        });

        const detailTable = $('#cdsiReferralTopupDetailTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            ajax: {
                url: @json($topupDetailUrl ?? route('cdsi.referral-topup-channel.detail-data')),
                type: 'GET',
                data: function(d) {
                    d.month = $('#filterMonthCdsi').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'paid_date', name: 'paid_date', className: 'text-center' },
                { data: 'transaction_id', name: 'transaction_id', className: 'text-center' },
                { data: 'customer_email', name: 'customer_email' },
                { data: 'amount', name: 'amount', className: 'text-right' },
                { data: 'transfer_status', name: 'transfer_status', className: 'text-center' }
                @if(in_array(auth()->user()->role, ['Admin', 'KSS', 'kss'], true))
                ,{ data: 'id_transaksi_myads', name: 'id_transaksi_myads', className: 'text-center',
                    render: function(data) {
                        return data ? data : '-';
                    }
                }
                ,{ data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                @endif
            ],
            order: [[1, 'desc'], [2, 'desc']]
        });

        $('#filterMonthCdsi').on('change', function() {
            $('#displayedMonthCdsi').text($('#filterMonthCdsi option:selected').text());
            table.ajax.reload();
            detailTable.ajax.reload();
        });

        @if(in_array(auth()->user()->role, ['Admin', 'KSS', 'kss'], true))
        $(document).on('click', '.btnUpdateMyadsInvoice', function() {
            $('#modalTransactionId').val($(this).data('transaction-id'));
            $('#modalTransactionIdDisplay').val($(this).data('display-transaction-id'));
            $('#modalMyadsInvoice').val($(this).data('myads-invoice') || '');
            $('#myadsInvoiceModal').modal('show');
        });

        $('#myadsInvoiceForm').on('submit', function(e) {
            e.preventDefault();

            const $submitButton = $('#btnSubmitMyadsInvoice');
            $submitButton.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: @json($updateInvoiceUrl ?? route('cdsi.referral-topup-channel.update-myads-invoice')),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    $('#myadsInvoiceModal').modal('hide');
                    detailTable.ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message || 'No Invoice MyAds berhasil disimpan.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message
                        || xhr.responseJSON?.errors?.id_transaksi_myads?.[0]
                        || 'Gagal menyimpan No Invoice MyAds.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: message
                    });
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text('Simpan');
                }
            });
        });
        @endif
    });
</script>
@endsection
