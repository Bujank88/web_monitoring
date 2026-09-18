@extends('master')
@section('title') Daily Topup Regional @endsection
@section('css')
<style>
    #loading-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
    }

    .table {
        background-color: #f9f9f9;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        margin-top: 15px;
        border: 0.5px solid #ccc;
        table-layout: auto;
    }

    .table th,
    .table td {
        padding: 8px !important;
        font-size: 16px;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
    }

    .table tbody tr:hover {
        background-color: #e2e2e2;
    }

    .table tbody tr:nth-child(odd) {
        background-color: #f2f2f2;
    }

    .table tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    @media (max-width: 768px) {
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100% !important;
            min-width: 1100px;
            white-space: nowrap;
        }
    }
</style>
@endsection

@section('content')
<div id="loading-overlay">
    <div style="text-align: center;">
        <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: white; margin-bottom: 10px;"></i>
        <p style="color: white; font-size: 16px;">Loading data...</p>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1">Daily Topup Regional</h4>
            <small class="text-muted">Regional: {{ $regionalName }}</small>
        </div>
        <div class="d-flex align-items-center" style="gap: 8px;">
            <select id="filterMonthRegional" class="form-control" style="background-color: #313131; color: white; min-width: 180px; max-width: 200px;">
                @foreach ($months as $month)
                <option value="{{ $month['value'] }}" {{ $month['selected'] ? 'selected' : '' }}>
                    {{ $month['label'] }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-gradient-danger text-white d-flex justify-content-between align-items-center" style="padding: 1rem; border-radius: 0.35rem 0.35rem 0 0;">
                <h4 class="mb-0"><i class="fas fa-chart-bar"></i> Daily Topup Regional</h4>
                <div class="d-flex gap-2">
                    <button type="button" id="btnSaveRegionalImage" class="btn btn-light btn-sm" title="Save as Image" style="padding: 6px 12px; white-space: nowrap;">
                        <i class="fas fa-image mr-2"></i> Save Image
                    </button>
                    <a href="{{ route('export.daily_topup_regional') }}?month={{ $months[array_search(true, array_column($months, 'selected'))]['value'] ?? now()->format('Y-m-01') }}" id="btnExportRegionalExcel" class="btn btn-light btn-sm" title="Download Excel" style="padding: 6px 12px; white-space: nowrap;">
                        <i class="fas fa-file-excel mr-2"></i> Download Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="captureDailyTopupRegionalTable" class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="dailyTopupRegionalTable" style="font-size: 12px;">
                        <thead class="thead-light">
                            <tr>
                                <th rowspan="3" style="vertical-align: middle; text-align: center; background-color: #f8f9fa;">Tanggal</th>
                                <th colspan="12" class="text-center" style="background-color: #f8d7da;">
                                    Report Daily Topup Regional {{ strtoupper($regionalName) }} | Bulan:
                                    <span id="displayedMonthRegional">{{ $months[array_search(true, array_column($months, 'selected'))]['label'] ?? now()->format('F Y') }}</span>
                                </th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-center" style="background-color: #fff3cd;">Mitra SBP</th>
                                <th colspan="2" class="text-center" style="background-color: #e2e3e5;">Agency Indihome</th>
                                <th colspan="2" class="text-center" style="background-color: #fcc271;">Internal</th>
                                <th colspan="2" class="text-center" style="background-color: #d8e2ff;">Powerhouse</th>
                                <th colspan="2" class="text-center" style="background-color: #d7ecff;">MPCC</th>
                                <th colspan="2" class="text-center" style="background-color: #f62b3c; color: white;">Total</th>
                            </tr>
                            <tr>
                                <th style="background-color: #fff3cd;">user_id</th>
                                <th style="background-color: #fff3cd;">Total Settlement</th>
                                <th style="background-color: #e2e3e5;">user_id</th>
                                <th style="background-color: #e2e3e5;">Total Settlement</th>
                                <th style="background-color: #fcc271;">user_id</th>
                                <th style="background-color: #fcc271;">Total Settlement</th>
                                <th style="background-color: #d8e2ff;">user_id</th>
                                <th style="background-color: #d8e2ff;">Total Settlement</th>
                                <th style="background-color: #d7ecff;">user_id</th>
                                <th style="background-color: #d7ecff;">Total Settlement</th>
                                <th style="background-color: #f62b3c; color: white;">User Id</th>
                                <th style="background-color: #f62b3c; color: white;">Settlement</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="dailyTopupRegionalByProvinceTableCard">
            <div class="card-header bg-gradient-danger text-white d-flex justify-content-between align-items-center" style="padding: 1rem; border-radius: 0.35rem 0.35rem 0 0;">
                <h4 class="mb-0" style="font-weight: 700; letter-spacing: 0.5px;">
                    <i class="fas fa-map-marker-alt"></i> Daily Topup Regional Per Province
                </h4>
                <div class="d-flex gap-2">
                    <button type="button" id="btnSaveRegionalProvinceImage" class="btn btn-light btn-sm" title="Save as Image" style="padding: 6px 12px; white-space: nowrap;">
                        <i class="fas fa-image mr-2"></i> Save Image
                    </button>
                    <button type="button" id="btnExportRegionalProvinceExcel" class="btn btn-light btn-sm" title="Download Excel" style="padding: 6px 12px; white-space: nowrap;">
                        <i class="fas fa-file-excel mr-2"></i> Download Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" id="captureRegionalProvinceTable">
                    <table class="table table-sm w-100 table-bordered table-hover" id="dailyTopupRegionalByProvinceTable" style="font-size: 12px;">
                        <thead class="thead-light">
                            <tr>
                                <th colspan="6" class="text-center" style="background-color: #d1ecf1; font-weight: 700; padding: 12px;">
                                    Report Daily Topup Regional Per Province | Bulan: <span id="displayedMonthRegionalProvince">{{ $months[array_search(true, array_column($months, 'selected'))]['label'] ?? now()->format('F Y') }}</span>
                                </th>
                            </tr>
                            <tr>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">Channel</th>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">Province</th>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">User ID</th>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">Email</th>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">Bulan</th>
                                <th style="text-align: center; background-color: #ff2626; font-weight: 700; color: white;">Total Settlement</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr style="background-color: #ff2626; font-weight: 700; color: white;">
                                <td style="text-align: center; border: 1px solid #cc0000; padding: 12px; color: white; font-size: 13px;"><strong>Channel</strong><br><span style="font-size: 16px;">5</span></td>
                                <td style="text-align: center; border: 1px solid #cc0000; padding: 12px; color: white; font-size: 13px;"><strong>Total Province</strong><br><span id="regionalProvinceCount" style="font-size: 16px;">0</span></td>
                                <td style="text-align: center; border: 1px solid #cc0000; padding: 12px; color: white; font-size: 13px;"><strong>Total User ID</strong><br><span id="regionalUserIdCount" style="font-size: 16px;">0</span></td>
                                <td style="text-align: center; border: 1px solid #cc0000; padding: 12px; color: white; font-size: 13px;"><strong>Total Email</strong><br><span id="regionalEmailCount" style="font-size: 16px;">0</span></td>
                                <td style="text-align: center; border: 1px solid #cc0000; padding: 12px; font-size: 14px; color: white;"><strong>TOTAL</strong></td>
                                <td id="regionalSettlementTotal" style="text-align: right; border: 1px solid #cc0000; padding: 12px; font-weight: 700; color: white; font-size: 16px;">Rp 0</td>
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
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    $(document).ready(function() {
        function syncRegionalExportLink() {
            const monthValue = $('#filterMonthRegional').val();
            $('#btnExportRegionalExcel').attr('href', "{{ route('export.daily_topup_regional') }}" + '?month=' + monthValue);
            $('#btnExportRegionalProvinceExcel').attr('onclick', "window.location='" + "{{ route('export.daily_topup_regional_by_province') }}" + "?month=" + monthValue + "'");
        }

        const table = $('#dailyTopupRegionalTable').DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            paging: false,
            searching: false,
            autoWidth: false,
            ajax: {
                url: "{{ route('daily_topup_regional_data') }}",
                type: "GET",
                data: function(d) {
                    d.month = $('#filterMonthRegional').val();
                    return d;
                },
                dataSrc: function(json) {
                    return json.data || [];
                }
            },
            preDrawCallback: function() {
                $('#loading-overlay').css('display', 'flex');
            },
            drawCallback: function() {
                $('#loading-overlay').hide();
            },
            rowCallback: function(row, data) {
                if (data.date === 'Total Keseluruhan') {
                    $(row).addClass('table-info');
                }
            },
            columns: [
                { data: 'date', render: renderCenter },
                { data: 'mitra_sbp_user', render: renderCenter },
                { data: 'mitra_sbp_settle', render: renderRight },
                { data: 'agency_user', render: renderCenter },
                { data: 'agency_settle', render: renderRight },
                { data: 'internal_user', render: renderCenter },
                { data: 'internal_settle', render: renderRight },
                { data: 'powerhouse_user', render: renderCenter },
                { data: 'powerhouse_settle', render: renderRight },
                { data: 'mpcc_user', render: renderCenter },
                { data: 'mpcc_settle', render: renderRight },
                { data: 'total_user', render: renderCenterBold },
                { data: 'total', render: renderRightBold }
            ]
        });

        const tableByProvince = $('#dailyTopupRegionalByProvinceTable').DataTable({
            processing: true,
            serverSide: false,
            ordering: false,
            paging: false,
            searching: false,
            autoWidth: false,
            ajax: {
                url: "{{ route('daily_topup_regional_by_province_data') }}",
                type: "GET",
                data: function(d) {
                    d.month = $('#filterMonthRegional').val();
                    return d;
                },
                dataSrc: function(json) {
                    $('#regionalProvinceCount').text(json.totals?.total_provinces ?? 0);
                    $('#regionalUserIdCount').text(json.totals?.total_user_ids ?? 0);
                    $('#regionalEmailCount').text(json.totals?.total_emails ?? 0);
                    $('#regionalSettlementTotal').text('Rp ' + (json.totals?.total_settlement ?? 0));
                    return json.data || [];
                }
            },
            preDrawCallback: function() {
                $('#loading-overlay').css('display', 'flex');
            },
            drawCallback: function() {
                $('#loading-overlay').hide();
            },
            columns: [
                { data: 'channel', render: function(data) { return `<div style="text-align: center;">${data || '-'}</div>`; } },
                { data: 'province', render: function(data) { return `<div style="text-align: center;">${data || '-'}</div>`; } },
                { data: 'user_id', render: function(data) { return `<div style="text-align: center;">${data || '-'}</div>`; } },
                { data: 'email_client', render: function(data) { return `<div style="text-align: left;">${data || '-'}</div>`; } },
                { data: 'month_display', render: function(data) { return `<div style="text-align: center;">${data || '-'}</div>`; } },
                { data: 'total_settlement', render: function(data) { return `<div style="text-align: right;">${data || '-'}</div>`; } }
            ]
        });

        function rowClass(row) {
            return row.date === 'Total Keseluruhan' ? 'font-weight-bold' : '';
        }

        function renderCenter(data, type, row) {
            return `<div style="text-align: center;" class="${rowClass(row)}">${data || '-'}</div>`;
        }

        function renderRight(data, type, row) {
            return `<div style="text-align: right;" class="${rowClass(row)}">${data || '-'}</div>`;
        }

        function renderCenterBold(data) {
            return `<div style="text-align: center; font-weight: bold;">${data || '-'}</div>`;
        }

        function renderRightBold(data) {
            return `<div style="text-align: right; font-weight: bold;">${data || '-'}</div>`;
        }

        $('#filterMonthRegional').on('change', function() {
            $('#displayedMonthRegional').text($('#filterMonthRegional option:selected').text());
            $('#displayedMonthRegionalProvince').text($('#filterMonthRegional option:selected').text());
            syncRegionalExportLink();
            table.ajax.reload();
            tableByProvince.ajax.reload();
        });

        syncRegionalExportLink();

        $('#btnSaveRegionalImage').on('click', function() {
            html2canvas(document.getElementById('captureDailyTopupRegionalTable'), {
                scale: 2,
                allowTaint: true,
                useCORS: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = 'daily_topup_regional_' + new Date().getTime() + '.png';
                link.click();
            }).catch(() => {
                alert('Gagal menyimpan gambar. Silakan coba lagi.');
            });
        });

        $('#btnSaveRegionalProvinceImage').on('click', function() {
            html2canvas(document.getElementById('captureRegionalProvinceTable'), {
                scale: 2,
                allowTaint: true,
                useCORS: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = 'daily_topup_regional_per_province_' + new Date().getTime() + '.png';
                link.click();
            }).catch(() => {
                alert('Gagal menyimpan gambar. Silakan coba lagi.');
            });
        });
    });
</script>
@endsection
