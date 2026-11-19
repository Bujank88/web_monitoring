@extends('master')
@section('title') Report Territory @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<style>
    #tabelTerritory {
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        margin-top: 15px;
        border-collapse: separate !important;
        border-spacing: 0;
        border: 0.5px solid #ccc;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    #tabelTerritory th,
    #tabelTerritory td {
        border: 0.5px solid #ccc !important;
        text-align: center !important;
        vertical-align: middle;
        padding: 12px !important;
        font-size: 13px;
    }

    /* Header: gradasi ungu, huruf putih kapital */
    #tabelTerritory thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: #ffffff !important;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        line-height: 1.25;
    }

    /* Baris belang */
    #tabelTerritory tbody tr:nth-child(odd)  { background-color: #f2f2f2; }
    #tabelTerritory tbody tr:nth-child(even) { background-color: #ffffff; }
    #tabelTerritory tbody td { color: #495057; }

    /* Target */
    #tabelTerritory tbody td:nth-child(3) {
        background: linear-gradient(135deg, #fff3cd 0%, #ffe8a1 100%);
        color: #7a5d00; font-weight: 600;
    }
    /* Jumlah Leads */
    #tabelTerritory tbody td:nth-child(4) {
        background: linear-gradient(135deg, #d6ecff 0%, #b8ddff 100%);
        color: #0b5394; font-weight: 600;
    }
    /* Jumlah Visit */
    #tabelTerritory tbody td:nth-child(5) {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        color: #0d47a1; font-weight: 600;
    }
    /* Deal Top Up */
    #tabelTerritory tbody td:nth-child(6),
    #tabelTerritory tbody td:nth-child(7),
    #tabelTerritory tbody td:nth-child(8) {
        background-color: #d1e7dd;
        color: #1b4332; font-weight: 600;
    }
    /* Top Up (Rp) dan ACV */
    #tabelTerritory tbody td:nth-child(9),
    #tabelTerritory tbody td:nth-child(10),
    #tabelTerritory tbody td:nth-child(11),
    #tabelTerritory tbody td:nth-child(12) {
        background-color: #f8d7da;
        color: #842029; font-weight: 600;
    }

    /* Baris TOTAL */
    #tabelTerritory tfoot td {
        background: linear-gradient(135deg, #fffb00 0%, #ffee00 100%);
        color: #495057;
        font-weight: 600;
        padding: 12px !important;
    }
</style>
@endsection

@section('content')
@php
    $ref = \Carbon\Carbon::yesterday();
    $startDate = request('start_date', $ref->copy()->startOfMonth()->format('Y-m-d'));
    $endDate   = request('end_date', $ref->copy()->format('Y-m-d'));
@endphp

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-end align-items-center" style="gap:8px;">
        <input type="date" id="startDate" class="form-control" value="{{ $startDate }}"
            style="background-color:#313131; color:white; min-width:160px; max-width:180px;">
        <input type="date" id="endDate" class="form-control" value="{{ $endDate }}"
            style="background-color:#313131; color:white; min-width:160px; max-width:180px;">
        <button type="button" id="btnSaveImage" class="btn btn-success">
            <i class="fas fa-image mr-2"></i> Save Image
        </button>
        <a id="btnExport" class="btn btn-success" href="#">
            <i class="fas fa-file-excel mr-2"></i> Download Excel
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-gradient-primary text-white">
                <h4 class="mb-0"><i class="fas fa-layer-group"></i> Summary Project Area 3 Per Territory</h4>
            </div>
            <div class="card-body">
                <div id="captureTabel" class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="tabelTerritory" style="font-size:13px;">
                        <thead class="table-light">
                            <tr style="background-color:#e8eaf6; font-weight:bold;">
                                <th colspan="12" style="padding:10px; border-bottom:2px solid #667eea;">
                                    Summary Topup Project Area 3 Per Territory |
                                    <span id="lblStart">{{ $startDate }}</span> s/d <span id="lblEnd">{{ $endDate }}</span>
                                </th>
                            </tr>
                            <tr>
                                <th rowspan="2" style="width:5%;">No</th>
                                <th rowspan="2">Territory</th>
                                <th rowspan="2" style="background-color:#ffe8a1;">Target (Rp.)</th>
                                <th rowspan="2" style="background-color:#e3f2fd;">Jumlah Leads</th>
                                <th rowspan="2" style="background-color:#d6ecff;">Jumlah Visit</th>
                                <th colspan="3" style="background-color:#d1e7dd;">Deal Top Up (New Akun &amp; Eksisting Akun)</th>
                                <th colspan="4" style="background-color:#f8d7da;">Top Up (Rp.)</th>
                            </tr>
                            <tr>
                                <th style="background-color:#d1e7dd;">New Akun</th>
                                <th style="background-color:#d1e7dd;">Eksisting Akun</th>
                                <th style="background-color:#d1e7dd;">Total Akun</th>
                                <th style="background-color:#f8d7da;">New Akun(Rp.)</th>
                                <th style="background-color:#f8d7da;">Eksisting Akun(Rp.)</th>
                                <th style="background-color:#f8d7da;">Total (Rp.)</th>
                                <th style="background-color:#f8d7da;">Acv (%)</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" style="text-align:right;">TOTAL</td>
                                <td id="tTarget">0</td>
                                <td id="tLeads">0</td>
                                <td id="tVisit">0</td>
                                <td id="tNew">0</td>
                                <td id="tExisting">0</td>
                                <td id="tTotalAkun">0</td>
                                <td id="tNewRp">0</td>
                                <td id="tExistingRp">0</td>
                                <td id="tTopup">0</td>
                                <td id="tAcv">0%</td>
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
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
$(function () {
    function persenStyle(p) {
        var bg = p >= 100 ? '#2e7d32' : (p >= 75 ? '#f9a825' : '#c62828');
        return {
            'background': 'linear-gradient(135deg,' + bg + ' 0%,' + bg + 'cc 100%)',
            'color': (p >= 75 && p < 100) ? '#1f2937' : '#ffffff',
            'font-weight': '700'
        };
    }
    function parsePersen(v) {
        return parseFloat(String(v || '0').replace('%', '').replace(/\./g, '').replace(',', '.')) || 0;
    }
    function num(v) {
        return parseInt(String(v || '0').replace(/[^\d]/g, '')) || 0;
    }
    function rp(n) { return 'Rp ' + Math.floor(n).toLocaleString('id-ID'); }

    var table = $('#tabelTerritory').DataTable({
        processing: true,
        serverSide: true,
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        ajax: {
            url: "{{ route('project-area3.territory.data') }}",
            data: function (d) {
                d.start_date = $('#startDate').val();
                d.end_date = $('#endDate').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex' },
            { data: 'territory' },
            { data: 'target' },
            { data: 'jumlah_leads' },
            { data: 'jumlah_visit' },
            { data: 'deal_topup_new_akun' },
            { data: 'deal_topup_existing_akun' },
            { data: 'deal_topup_total_akun' },
            { data: 'top_up_new_akun_rp' },
            { data: 'top_up_existing_akun_rp' },
            { data: 'total_topup' },
            { data: 'acv' }
        ],
        rowCallback: function (row, data) {
            $('td', row).eq(11).css(persenStyle(parsePersen(data.acv)));
        },
        drawCallback: function () {
            var t = { target: 0, leads: 0, visit: 0, nw: 0, ex: 0, tot: 0, nwRp: 0, exRp: 0, topup: 0 };
            this.api().rows().data().each(function (r) {
                t.target += num(r.target);
                t.leads += num(r.jumlah_leads);
                t.visit += num(r.jumlah_visit);
                t.nw += num(r.deal_topup_new_akun);
                t.ex += num(r.deal_topup_existing_akun);
                t.tot += num(r.deal_topup_total_akun);
                t.nwRp += num(r.top_up_new_akun_rp);
                t.exRp += num(r.top_up_existing_akun_rp);
                t.topup += num(r.total_topup);
            });
            var acv = t.target > 0 ? (t.topup / t.target) * 100 : 0;
            $('#tTarget').text(rp(t.target));
            $('#tLeads').text(t.leads);
            $('#tVisit').text(t.visit);
            $('#tNew').text(t.nw);
            $('#tExisting').text(t.ex);
            $('#tTotalAkun').text(t.tot);
            $('#tNewRp').text(t.nwRp.toLocaleString('id-ID'));
            $('#tExistingRp').text(t.exRp.toLocaleString('id-ID'));
            $('#tTopup').text(rp(t.topup));
            $('#tAcv').text(acv.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%')
                      .css(persenStyle(acv));
        }
    });

    function updateExport() {
        var q = new URLSearchParams({ start_date: $('#startDate').val(), end_date: $('#endDate').val() });
        $('#btnExport').attr('href', "{{ route('project-area3.territory.export') }}?" + q.toString());
    }

    $('#startDate, #endDate').on('change', function () {
        $('#lblStart').text($('#startDate').val());
        $('#lblEnd').text($('#endDate').val());
        updateExport();
        table.ajax.reload();
    });

    $('#btnSaveImage').on('click', function () {
        html2canvas(document.getElementById('captureTabel'), { scale: 2, allowTaint: true, useCORS: true })
            .then(function (canvas) {
                var a = document.createElement('a');
                a.download = 'summary-territory-area3-' + Date.now() + '.png';
                a.href = canvas.toDataURL();
                a.click();
            })
            .catch(function () { alert('Gagal menyimpan gambar.'); });
    });

    updateExport();
});
</script>
@endsection