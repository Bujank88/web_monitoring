@extends('master')

@section('title', 'Topup Client Canvasser')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet"/>

<style>
table.dataTable th,
table.dataTable td {
    white-space: nowrap;
    text-align: center;
    vertical-align: middle;
}
.filter-box label { font-weight: 600; font-size: 14px; }
.overlay {
    position: fixed;
    inset: 0;
    background: rgba(255,255,255,.7);
    z-index: 9999;
    display:none;
    align-items:center;
    justify-content:center;
}
#chartContainer { margin-bottom: 1.5rem; }
</style>
@endsection

@section('content')
<div class="overlay" id="loading">
    <div class="spinner-border text-primary"></div>
</div>

<div class="container-fluid">

<h3 class="mb-3">Topup & Client Canvasser</h3>

{{-- FILTER --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 filter-box">

            <div class="col-md-2">
                <label>Bulan</label>
                <input type="month" id="month" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Canvasser</label>
                <select id="canvassers" class="form-control select2" multiple>
                    @foreach($canvassers ?? [] as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label>Start Date</label>
                <input type="date" id="start" class="form-control">
            </div>

            <div class="col-md-2">
                <label>End Date</label>
                <input type="date" id="end" class="form-control">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button id="btnApply" class="btn btn-primary w-100">🔍 Apply Filter</button>
            </div>

        </div>
    </div>
</div>

{{-- CHART --}}
<div id="chartContainer" class="card mb-3">
    <div class="card-body">
        <canvas id="barChart" height="100"></canvas>
    </div>
</div>

{{-- TABLE --}}
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="pivotTable" class="table table-bordered table-striped w-100">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

</div>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(function () {

    $('.select2').select2({ width: '100%' });

    let table, barChart;

    const formatNumber = n => new Intl.NumberFormat('id-ID').format(n);

    const showLoading = show => $('#loading').toggle(show);

    /* ===== MONTH HANDLING ===== */
    function syncMonth() {
        const m = $('#month').val();
        if (!m) {
            $('#start,#end').prop('disabled', false);
            return;
        }
        const [y, mm] = m.split('-');
        $('#start').val(`${y}-${mm}-01`).prop('disabled', true);
        $('#end').val(new Date(y, mm, 0).toISOString().slice(0,10)).prop('disabled', true);
    }
    $('#month').on('change', syncMonth);
    syncMonth();

    /* ===== CHART ===== */
    function renderChart(labels, totals) {
        const ctx = document.getElementById('barChart').getContext('2d');
        if (barChart) barChart.destroy();

        barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data: totals,
                    backgroundColor: 'rgba(54,162,235,.7)'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display:false } },
                scales: {
                    y: {
                        beginAtZero:true,
                        ticks:{ callback:v => formatNumber(v) }
                    }
                }
            }
        });
    }

    /* ===== LOAD DATA ===== */
    function loadData() {
        showLoading(true);

        $.get("{{ url('/report/topup-canvasser/data') }}", {
            month: $('#month').val(),
            start: $('#start').val(),
            end: $('#end').val(),
            canvassers: $('#canvassers').val()
        }).done(res => {

            const thead = $('#pivotTable thead').empty();
            const tbody = $('#pivotTable tbody').empty();

            if (!res.canvassers.length) {
                tbody.append(`<tr><td class="text-muted text-center">No data</td></tr>`);
                if (table) table.clear().draw();
                renderChart([], []);
                return;
            }

            /* ===== HEADER (SYNC SOURCE) ===== */
            let h1 = `<tr><th rowspan="2">Date</th>`;
            res.canvassers.forEach(c => h1 += `<th colspan="2">${c}</th>`);
            h1 += `</tr>`;

            let h2 = `<tr>`;
            res.canvassers.forEach(() => h2 += `<th>Amount</th><th>Email</th>`);
            h2 += `</tr>`;

            thead.append(h1 + h2);

            /* ===== BODY (100% SYNC) ===== */
            const rowsData = [];
            const totals = Object.fromEntries(res.canvassers.map(c => [c, 0]));

            Object.keys(res.rows).sort().forEach(date => {
                const row = [date];

                res.canvassers.forEach(c => {
                    const cell = res.rows[date][c];
                    if (cell) {
                        row.push(formatNumber(cell.amount));
                        row.push(cell.emails.length);
                        totals[c] += cell.amount;
                    } else {
                        row.push('-','-');
                    }
                });

                rowsData.push(row);
            });

            /* ===== DATATABLE ===== */
            if (!table) {
                table = $('#pivotTable').DataTable({
                    data: rowsData,
                    scrollX: true,
                    paging: false,
                    searching: false,
                    ordering: false,
                    info: false
                });
            } else {
                table.clear().rows.add(rowsData).draw();
            }

            /* ===== CHART ===== */
            renderChart(
                Object.keys(totals),
                Object.values(totals)
            );

        }).always(() => showLoading(false));
    }

    $('#btnApply').on('click', loadData);
    loadData();
});
</script>
@endsection
