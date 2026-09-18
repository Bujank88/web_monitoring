@extends('master')
@section('title') PowerHouse Semester @endsection

@section('css')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet"/>
<style>
    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
    }

    .table {
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }

    .table th {
        background: linear-gradient(135deg, #0f6c7a 0%, #1b9aaa 100%);
        color: #fff;
        padding: 12px !important;
        font-size: 13px;
        font-weight: 600;
        text-align: center !important;
        border: none;
    }

    .table td {
        padding: 12px !important;
        font-size: 13px;
        text-align: center !important;
        border-bottom: 1px solid #e9ecef;
    }

    .table tbody tr:nth-child(odd) {
        background-color: #f8fbfc;
    }

    .table tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    #powerHouseSemesterTable,
    #powerHouseSemesterTargetTable {
        border: 0.5px solid #ccc;
        box-shadow: none;
    }

    #powerHouseSemesterTable th,
    #powerHouseSemesterTable td,
    #powerHouseSemesterTargetTable th,
    #powerHouseSemesterTargetTable td {
        border: 0.5px solid #ccc !important;
    }

    #powerHouseSemesterTable tbody td:nth-child(3),
    #powerHouseSemesterTargetTable tbody td:nth-child(5) {
        background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
        font-weight: 600;
        color: #7a5d00;
    }

    .summary-pill {
        background: linear-gradient(135deg, #0f6c7a 0%, #1b9aaa 100%);
        color: #fff;
        border-radius: 999px;
        padding: 0.45rem 0.9rem;
        display: inline-block;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .filter-bar {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-select {
        background-color: #313131;
        color: #fff;
        min-width: 280px;
        max-width: 360px;
    }

    @media (max-width: 768px) {
        .table th,
        .table td {
            padding: 8px !important;
            font-size: 11px;
        }

        .filter-select {
            min-width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:12px;">
            <div class="summary-pill">
                Periode: <span id="selectedSemesterLabel">-</span>
            </div>
            <div class="filter-bar">
                <select id="semesterFilterPH" class="form-control filter-select">
                    @foreach ($semesters as $semester)
                    <option
                        value="{{ $semester['value'] }}"
                        data-label="{{ $semester['label'] }}"
                        data-start="{{ $semester['start_date'] }}"
                        data-end="{{ $semester['end_date'] }}"
                        {{ $semester['selected'] ? 'selected' : '' }}>
                        {{ $semester['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="fas fa-chart-line"></i> Report PowerHouse Deal Top Up & MOM</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="powerHouseSemesterTable">
                        <thead>
                            <tr>
                                <th colspan="10">Data Topup & MOM PowerHouse | <span class="displayedSemesterPeriod">-</span></th>
                            </tr>
                            <tr>
                                <th rowspan="2" style="width:5%;">No</th>
                                <th rowspan="2">Team PowerHouse</th>
                                <th rowspan="2">Target (Rp.)</th>
                                <th rowspan="2">Total (Rp.)</th>
                                <th rowspan="2">Gap to Target</th>
                                <th rowspan="2">Acv (%)</th>
                                <th colspan="4">MOM</th>
                            </tr>
                            <tr>
                                <th id="semesterMomPrevPartial">-</th>
                                <th id="semesterMomCurrentPartial">-</th>
                                <th id="semesterMomPrevRemaining">-</th>
                                <th>Gap (Rp)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr style="background: linear-gradient(135deg, #fffb00 0%, #ffee00 100%); font-weight: 600;">
                                <td colspan="2">TOTAL</td>
                                <td id="totalSemesterTarget">Rp 0</td>
                                <td id="totalSemesterTopup">Rp 0</td>
                                <td id="totalSemesterGapToTarget">Rp 0</td>
                                <td id="totalSemesterAcv">0%</td>
                                <td id="totalSemesterMomPrev">0</td>
                                <td id="totalSemesterMomCurrent">0</td>
                                <td id="totalSemesterMomRemaining">0</td>
                                <td id="totalSemesterMomGap">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body text-center">
                <small class="text-muted">
                    <i class="fas fa-clock"></i> Last updated: {{ now()->format('d F Y, H:i:s') }} WIB
                </small>
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
    $(document).ready(function () {
        function selectedSemesterOption() {
            return $('#semesterFilterPH option:selected');
        }

        function parseNumber(text) {
            if (!text) return 0;
            const normalized = String(text).replace(/\./g, '').replace(/,/g, '.').replace(/[^\d.-]/g, '');
            return parseFloat(normalized) || 0;
        }

        function getPercentageStyle(percent) {
            let backgroundColor = percent >= 100 ? '#2e7d32' : (percent >= 75 ? '#f9a825' : '#c62828');
            let textColor = percent >= 75 && percent < 100 ? '#1f2937' : '#ffffff';

            return {
                'background': `linear-gradient(135deg, ${backgroundColor} 0%, ${backgroundColor}cc 100%)`,
                'color': textColor,
                'font-weight': '700'
            };
        }

        function applyPercentageCellStyle(cell, rawValue) {
            const percent = typeof rawValue === 'number'
                ? rawValue
                : parseFloat(String(rawValue || '0').replace('%', '').replace(/\./g, '').replace(',', '.')) || 0;

            cell.css(getPercentageStyle(percent));
        }

        function formatPeriodLabel() {
            const option = selectedSemesterOption();
            $('.displayedSemesterPeriod').text(option.data('start') + ' s/d ' + option.data('end'));
            $('#selectedSemesterLabel').text(option.data('label'));
        }

        function monthShortId(dateObj) {
            return dateObj.toLocaleString('id-ID', { month: 'short' }).replace('.', '');
        }

        function updateMomHeaders() {
            const endDate = new Date(selectedSemesterOption().data('end'));
            const prevSame = new Date(endDate);
            prevSame.setMonth(prevSame.getMonth() - 1);

            const prevEnd = new Date(prevSame.getFullYear(), prevSame.getMonth() + 1, 0);
            const prevRemainingStart = prevSame.getDate() + 1;

            $('#semesterMomPrevPartial').text(`1 - ${prevSame.getDate()} ${monthShortId(prevSame)}`);
            $('#semesterMomCurrentPartial').text(`1 - ${endDate.getDate()} ${monthShortId(endDate)}`);
            $('#semesterMomPrevRemaining').text(prevRemainingStart <= prevEnd.getDate()
                ? `${prevRemainingStart} - ${prevEnd.getDate()} ${monthShortId(prevSame)}`
                : '-');
        }

        const performanceTable = $('#powerHouseSemesterTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            ajax: {
                url: "{{ route('powerhouse_semester_deal_topup_mom_data') }}",
                type: 'GET',
                data: function (d) {
                    d.semester = $('#semesterFilterPH').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', className: 'text-center' },
                { data: 'team_powerhouse', className: 'text-center' },
                { data: 'target', className: 'text-center' },
                { data: 'total_topup', className: 'text-center' },
                { data: 'gap_to_target', className: 'text-center' },
                { data: 'acv', className: 'text-center' },
                { data: 'mom_prev_partial', className: 'text-center' },
                { data: 'mom_current_partial', className: 'text-center' },
                { data: 'mom_prev_remaining', className: 'text-center' },
                { data: 'mom_gap', className: 'text-center' }
            ],
            rowCallback: function (row, data) {
                applyPercentageCellStyle($('td', row).eq(5), data.acv);
            },
            drawCallback: function () {
                let totalTopup = 0;
                let totalTarget = 0;
                let totalMomPrev = 0;
                let totalMomCurrent = 0;
                let totalMomRemaining = 0;
                let totalMomGap = 0;

                $('#powerHouseSemesterTable tbody tr').each(function () {
                    const cells = $(this).find('td');
                    totalTarget += parseNumber(cells.eq(2).text().trim());
                    totalTopup += parseNumber(cells.eq(3).text().trim());
                    totalMomPrev += parseNumber(cells.eq(6).text().trim());
                    totalMomCurrent += parseNumber(cells.eq(7).text().trim());
                    totalMomRemaining += parseNumber(cells.eq(8).text().trim());
                    totalMomGap += parseNumber(cells.eq(9).text().trim());
                });

                const totalAcv = totalTarget > 0 ? (totalTopup / totalTarget) * 100 : 0;
                const totalGapToTarget = totalTarget - totalTopup;

                $('#totalSemesterTarget').text('Rp ' + Math.floor(totalTarget).toLocaleString('id-ID'));
                $('#totalSemesterTopup').text('Rp ' + Math.floor(totalTopup).toLocaleString('id-ID'));
                $('#totalSemesterAcv').text(totalAcv.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%');
                applyPercentageCellStyle($('#totalSemesterAcv'), totalAcv);
                $('#totalSemesterGapToTarget').text('Rp ' + Math.floor(totalGapToTarget).toLocaleString('id-ID'));
                $('#totalSemesterMomPrev').text(Math.floor(totalMomPrev).toLocaleString('id-ID'));
                $('#totalSemesterMomCurrent').text(Math.floor(totalMomCurrent).toLocaleString('id-ID'));
                $('#totalSemesterMomRemaining').text(Math.floor(totalMomRemaining).toLocaleString('id-ID'));
                $('#totalSemesterMomGap').text(Math.floor(totalMomGap).toLocaleString('id-ID'));
            }
        });

        $('#semesterFilterPH').on('change', function () {
            formatPeriodLabel();
            updateMomHeaders();
            performanceTable.ajax.reload();
        });

        formatPeriodLabel();
        updateMomHeaders();
    });
</script>
@endsection
