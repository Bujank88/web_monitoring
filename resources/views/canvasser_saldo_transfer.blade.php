@extends('master')
@section('title') Detail Transfer Saldo Canvasser @endsection
@section('css')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet"/>
<style>
    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
    }
    .bg-gradient-primary {
        background: linear-gradient(87deg, #4e73df 0, #224abe 100%);
    }
    .gap-2 { gap: 8px; }
    #canvasserSaldoTransferTable {
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        margin-top: 15px;
        border-collapse: separate;
        border-spacing: 0;
        border: 0.5px solid #ccc;
    }
    #canvasserSaldoTransferTable th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px !important;
        font-size: 13px;
        font-weight: 600;
        text-align: center !important;
        border: 0.5px solid #ccc !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    #canvasserSaldoTransferTable td {
        padding: 12px !important;
        font-size: 13px;
        border: 0.5px solid #ccc !important;
        color: #495057;
        text-align: center !important;
    }
    #canvasserSaldoTransferTable tbody tr:nth-child(odd) { background-color: #f2f2f2; }
    #canvasserSaldoTransferTable tbody tr:nth-child(even) { background-color: #ffffff; }
    #canvasserSaldoTransferTable tbody tr td:nth-child(1) {
        font-weight: 600;
        color: #667eea;
        background-color: #f0f2f9;
        width: 5%;
    }
    #canvasserSaldoTransferTable tbody td:nth-child(2) {
        background-color: #e8f5e9;
        color: #1b5e20;
        font-weight: 600;
    }
    #canvasserSaldoTransferTable tbody td:nth-child(5) {
        background-color: #fff3cd;
        color: #7a5d00;
        font-weight: 600;
    }
    #canvasserSaldoTransferTable tbody td:nth-child(6) {
        background-color: #d1e7dd;
        color: #0f5132;
        font-weight: 700;
    }
</style>
@endsection

@section('content')
@php
    use Carbon\Carbon;
    $defaultReferenceDate = Carbon::yesterday();
    $startDate = request('start_date', $defaultReferenceDate->copy()->startOfMonth()->format('Y-m-d'));
    $endDate   = request('end_date', $defaultReferenceDate->copy()->format('Y-m-d'));
@endphp

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-end align-items-center gap-2">
        <input type="date" id="startDateCvsr" name="start_date" class="form-control" value="{{ $startDate }}"
            style="background-color: #313131; color: white; min-width: 160px; max-width: 180px;">
        <input type="date" id="endDateCvsr" name="end_date" class="form-control" value="{{ $endDate }}"
            style="background-color: #313131; color: white; min-width: 160px; max-width: 180px;">
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="canvasserSaldoTransferCard">
            <div class="card-header bg-gradient-primary text-white">
                <h4 class="mb-0"><i class="fas fa-exchange-alt"></i> Detail Transfer Saldo</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="canvasserSaldoTransferTable" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr style="background-color: #e8eaf6; font-weight: bold;">
                                <th colspan="7" style="text-align: center; padding: 10px; border-bottom: 2px solid #667eea;">
                                    Detail Transfer Saldo Canvasser | <span class="displayedStartDateCvsr">{{ $startDate }}</span> s/d <span class="displayedEndDateCvsr">{{ $endDate }}</span>
                                </th>
                            </tr>
                            <tr>
                                <th style="text-align: center; width: 5%;">No</th>
                                <th style="text-align: center;">Canvasser</th>
                                <th style="text-align: center;">Company Name</th>
                                <th style="text-align: center;">Email Client</th>
                                <th style="text-align: center;">Parent Email</th>
                                <th style="text-align: center;">Amount</th>
                                <th style="text-align: center;">Tgl Transaksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr style="background: linear-gradient(135deg, #fffb00 0%, #ffee00 100%); color: #1f2937; font-weight: 700;">
                                <td colspan="5" style="text-align: right; padding: 12px;">TOTAL</td>
                                <td id="totalCvsrSaldoTransferAmount" style="text-align: center; padding: 12px;">Rp 0</td>
                                <td id="totalCvsrSaldoTransferCount" style="text-align: center; padding: 12px;">0 Transfer</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
    $(document).ready(function() {
        var saldoTransferTable = $('#canvasserSaldoTransferTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            ajax: {
                url: "{{ route('canvasser_saldo_transfer_detail_data') }}",
                type: 'GET',
                data: function(d) {
                    d.start_date = $('#startDateCvsr').val();
                    d.end_date = $('#endDateCvsr').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'nama_canvasser', name: 'nama_canvasser', className: 'text-center' },
                { data: 'company_name', name: 'company_name', className: 'text-center' },
                { data: 'email_client', name: 'email_client', className: 'text-center' },
                { data: 'parent_email', name: 'parent_email', className: 'text-center' },
                { data: 'amount', name: 'amount', className: 'text-center' },
                { data: 'tgl_transaksi', name: 'tgl_transaksi', className: 'text-center' }
            ],
            drawCallback: function() {
                calculateSaldoTransferTotals();
            }
        });

        $('#startDateCvsr').on('change', function () {
            let startDate = $(this).val();
            if (startDate) {
                $('#endDateCvsr').attr('min', startDate);
                if ($('#endDateCvsr').val() && $('#endDateCvsr').val() < startDate) {
                    $('#endDateCvsr').val('');
                }
            }
            saldoTransferTable.ajax.reload();
            updateFilterPeriodHeader();
        });

        $('#endDateCvsr').on('change', function () {
            let endDate = $(this).val();
            if (endDate) {
                $('#startDateCvsr').attr('max', endDate);
                if ($('#startDateCvsr').val() && $('#startDateCvsr').val() > endDate) {
                    $('#startDateCvsr').val('');
                }
            }
            saldoTransferTable.ajax.reload();
            updateFilterPeriodHeader();
        });

        function calculateSaldoTransferTotals() {
            let totalAmount = 0;
            let totalCount = 0;

            const parseNumber = (text) => {
                if (!text) return 0;
                const normalized = text.replace(/\./g, '').replace(/,/g, '.').replace(/[^\d.-]/g, '');
                return parseFloat(normalized) || 0;
            };

            $('#canvasserSaldoTransferTable tbody tr').each(function() {
                const cells = $(this).find('td');
                if (cells.length >= 6) {
                    totalAmount += parseNumber(cells.eq(5).text().trim());
                    totalCount += 1;
                }
            });

            $('#totalCvsrSaldoTransferAmount').text('Rp ' + Math.floor(totalAmount).toLocaleString('id-ID'));
            $('#totalCvsrSaldoTransferCount').text(totalCount.toLocaleString('id-ID') + ' Transfer');
        }

        function updateFilterPeriodHeader() {
            $('.displayedStartDateCvsr').text($('#startDateCvsr').val() || '-');
            $('.displayedEndDateCvsr').text($('#endDateCvsr').val() || '-');
        }
    });
</script>
@endsection