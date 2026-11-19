@extends('master')
@section('title') {{ $judul }} Report @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<style>
    .bg-gradient-danger-p3 {
        background: linear-gradient(135deg, #dc3545 0%, #e4606d 100%) !important;
        color: #fff;
    }
    #tabelArea3 thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: #fff !important;
        text-align: center;
        text-transform: uppercase;
        font-size: 12px;
        vertical-align: middle;
    }
    #tabelArea3 thead tr.judul th { padding: 10px; font-size: 12px; }
    #tabelArea3 tbody td { text-align: center; vertical-align: middle; }
    #tabelArea3 tbody td:nth-child(1) { font-weight: 600; color: #667eea; background: #f0f2f9; width: 5%; }
    #tabelArea3 tbody td:nth-child(4) { font-weight: 600; letter-spacing: .5px; }
    #tabelArea3 tbody td:nth-child(5) { background: #e3f2fd; font-weight: 600; }
    #tabelArea3 tbody td:nth-child(6) { background: #f3e5f5; color: #6a1b9a; font-weight: 600; }
    #tabelArea3 tbody td:nth-child(8) {
    background: linear-gradient(135deg, #fff5e1 0%, #ffe0b2 100%);
    color: #e65100; font-weight: 600;
    }
    #tabel tbody td:nth-child(9) { color: #2e7d32; font-weight: 600; }
    #tabelArea3 tfoot td {
        background: linear-gradient(135deg, #fffb00 0%, #ffee00 100%);
        color: #333; font-weight: 700; text-align: center; padding: 12px;
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
            <div class="card-header bg-gradient-danger-p3">
                <h4 class="mb-0"><i class="fas fa-table"></i> {{ $judul }} Report</h4>
            </div>
            <div class="card-body">
                <div id="captureTabel" class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="tabelArea3" style="font-size:13px;">
                        <thead>
                            <tr class="judul">
                                <th colspan="9">
                                    {{ $judul }} Report |
                                    <span id="lblStart">{{ $startDate }}</span> s/d <span id="lblEnd">{{ $endDate }}</span>
                                </th>
                            </tr>
                            <tr>
                                <th style="width:5%;">No</th>
                                <th>Nama {{ $judul }}</th>
                                <th>Cluster</th>
                                <th>Referral Code</th>
                                <th>Jumlah Leads</th>
                                <th>Jumlah Akun</th>
                                <th>New Akun to Leads (%)</th>
                                <th>Top Up</th>
                                <th>Tgl Transaksi Terakhir</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:center;">TOTAL</td>
                                <td id="totLeads">0</td>
                                <td id="totAkun">0</td>
                                <td id="totPersen">0%</td>
                                <td id="totTopup">Rp 0</td>
                                <td>-</td>
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

    var table = $('#tabelArea3').DataTable({
        processing: true,
        serverSide: true,
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        ajax: {
            url: "{{ route('project-area3.data', $tipe) }}",
            data: function (d) {
                d.start_date = $('#startDate').val();
                d.end_date = $('#endDate').val();
            },
            error: function (xhr) {
                console.log(xhr.status, xhr.responseText.substring(0, 800));
                alert('Gagal memuat data (HTTP ' + xhr.status + '). Lihat Console (F12) untuk detail.');
            }
        },
        columns: [
            { data: 'DT_RowIndex' },
            { data: 'nama' },
            { data: 'branch' },
            { data: 'referral_code' },
            { data: 'jumlah_leads' },
            { data: 'jumlah_akun' },
            { data: 'percentage_new_akun_to_lead' },
            { data: 'total_topup' },
            { data: 'tgl_transaksi_terakhir' }
        ],
        rowCallback: function (row, data) {
            $('td', row).eq(6).css(persenStyle(parsePersen(data.percentage_new_akun_to_lead)));
        },
        drawCallback: function () {
            var leads = 0, akun = 0, topup = 0;
            this.api().rows().data().each(function (r) {
                leads += parseInt(r.jumlah_leads) || 0;
                akun += parseInt(r.jumlah_akun) || 0;
                var m = String(r.total_topup || '').match(/[\d.,]+/);
                if (m) topup += parseFloat(m[0].replace(/\./g, '').replace(/,/g, '.')) || 0;
            });
            var persen = leads > 0 ? (akun / leads) * 100 : 0;
            $('#totLeads').text(leads);
            $('#totAkun').text(akun);
            $('#totPersen').text(persen.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '%')
                           .css(persenStyle(persen));
            $('#totTopup').text('Rp ' + Math.floor(topup).toLocaleString('id-ID'));
        }
    });

    function updateExport() {
        var q = new URLSearchParams({ start_date: $('#startDate').val(), end_date: $('#endDate').val() });
        $('#btnExport').attr('href', "{{ route('project-area3.export', $tipe) }}?" + q.toString());
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
                a.download = 'report-{{ $tipe }}-area3-' + Date.now() + '.png';
                a.href = canvas.toDataURL();
                a.click();
            })
            .catch(function () { alert('Gagal menyimpan gambar.'); });
    });

    updateExport();
});
</script>
@endsection