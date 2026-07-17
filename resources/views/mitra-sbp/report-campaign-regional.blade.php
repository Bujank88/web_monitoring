@extends('master')
@section('title') Report Campaign Regional @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
    .filter-card {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .filter-card h5 {
        background-color: #495057;
        color: white;
        padding: 12px 15px;
        margin: -20px -20px 15px -20px;
        border-radius: 7px 7px 0 0;
        font-weight: 600;
        font-size: 15px;
    }

    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: 600;
        font-size: 13px;
        margin-bottom: 6px;
        color: #333;
    }

    .table {
        background-color: #fff;
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
        font-size: 13px;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
        white-space: nowrap;
    }

    .table th {
        font-weight: bold;
        color: #ffffff !important;
        background: #dc3545;
    }
</style>
@endsection

@section('content')
<div class="filter-card">
    <h5><i class="fas fa-filter"></i> FILTER REPORT CAMPAIGN REGIONAL</h5>
    <div class="filter-row">
        <div class="filter-group">
            <label for="month">Bulan</label>
            <select id="month" name="month" class="form-control">
                @foreach ($months as $m)
                <option value="{{ $m['value'] }}" {{ $m['selected'] ? 'selected' : '' }}>
                    {{ $m['label'] }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label for="source">Source</label>
            <select id="source" name="source" class="form-control">
                <option value="">Semua Source</option>
                <option value="Mitra SBP">Mitra SBP</option>
                <option value="Powerhouse">Powerhouse</option>
                <option value="MPCC">MPCC</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Regional</label>
            <input type="text" class="form-control" value="KALIMANTAN" readonly>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <div class="row">
            <div class="col-md-4 mb-2">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Campaign Mitra SBP</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="countMitraSbp">0</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card border-left-success">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Campaign Powerhouse</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="countPowerhouse">0</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card border-left-warning">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Campaign MPCC</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="countMpcc">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-danger d-flex justify-content-between align-items-center">
                <h3 class="card-title text-white mb-0">
                    <i class="fas fa-bullhorn mr-2"></i>Report Campaign Regional Kalimantan
                </h3>
                <div class="btn-actions">
                    <button type="button" class="btn btn-success btn-sm" id="btnSaveCampaignRegionalImage">
                        <i class="fas fa-image mr-1"></i> Save Image
                    </button>
                    <a class="btn btn-success btn-sm" id="btnExportCampaignRegional" href="#">
                        <i class="fas fa-file-excel mr-1"></i> Download Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" id="campaignRegionalTableWrap">
                    <table id="campaignRegionalTable" class="table table-bordered table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Tanggal Iklan</th>
                                <th>Broadcast Date</th>
                                <th>Email</th>
                                <th>ID Iklan</th>
                                <th>Nama Iklan</th>
                                <th>Nama Instansi</th>
                                <th>Area Provinsi</th>
                                <th>Campaign Type</th>
                                <th>Inventory Type</th>
                                <th>Total</th>
                                <th>Success</th>
                                <th>Failed</th>
                                <th>Delivered</th>
                                <th>Read</th>
                                <th>Click</th>
                                <th>Balance Terpakai</th>
                                <th>Pesan</th>
                                <th>Campaign Status</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
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
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
$(document).ready(function() {
    function updateExportLink() {
        var month = $('#month').val();
        var source = $('#source').val();

        var url = "{{ route('report-campaign-regional.export') }}" +
                  "?month=" + encodeURIComponent(month);

        if (source) {
            url += "&source=" + encodeURIComponent(source);
        }

        $('#btnExportCampaignRegional').attr('href', url);
    }

    function saveTableAsImage() {
        html2canvas(document.getElementById('campaignRegionalTableWrap'), {
            scale: 2,
            allowTaint: true,
            useCORS: true
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = 'report-campaign-regional-' + new Date().getTime() + '.png';
            link.href = canvas.toDataURL();
            link.click();
        }).catch(err => {
            console.error('Error capturing image:', err);
            alert('Gagal menyimpan gambar. Silakan coba lagi.');
        });
    }

    var table = $('#campaignRegionalTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('report-campaign-regional.data') }}",
            data: function(d) {
                d.month = $('#month').val();
                d.source = $('#source').val();
            }
        },
        columns: [
            { data: 'tanggal_iklan', name: 'tanggal_iklan' },
            { data: 'broadcast_date', name: 'broadcast_date' },
            { data: 'email', name: 'email' },
            { data: 'id_iklan', name: 'id_iklan' },
            { data: 'nama_iklan', name: 'nama_iklan' },
            { data: 'nama_instansi', name: 'nama_instansi' },
            { data: 'area_provinsi', name: 'area_provinsi' },
            { data: 'campaign_type', name: 'campaign_type' },
            { data: 'inventory_type', name: 'inventory_type' },
            { data: 'total', name: 'total' },
            { data: 'success', name: 'success' },
            { data: 'failed', name: 'failed' },
            { data: 'delivered', name: 'delivered' },
            { data: 'read', name: 'read' },
            { data: 'click', name: 'click' },
            { data: 'balance_terpakai', name: 'balance_terpakai' },
            {
                data: 'pesan',
                name: 'pesan',
                render: function(data) {
                    if (!data) return '';
                    var text = data.toString();
                    if (text.length <= 50) return text;
                    var shortText = text.substring(0, 50) + '...';
                    return '<span title="' + text.replace(/"/g, '&quot;') + '">' + shortText + '</span>';
                }
            },
            { data: 'campaign_status', name: 'campaign_status' },
            { data: 'source_label', name: 'source_label' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, "Semua"]
        ],
        language: {
            emptyTable: 'Tidak ada data untuk ditampilkan',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
            infoFiltered: '(disaring dari _MAX_ total data)',
            lengthMenu: 'Tampilkan _MENU_ data',
            search: 'Cari:',
            zeroRecords: 'Data tidak ditemukan',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Selanjutnya',
                previous: 'Sebelumnya'
            }
        },
        drawCallback: function(settings) {
            var summary = (settings.json && settings.json.summary) ? settings.json.summary : {};
            $('#countMitraSbp').text(summary['Mitra SBP'] || 0);
            $('#countPowerhouse').text(summary['Powerhouse'] || 0);
            $('#countMpcc').text(summary['MPCC'] || 0);
        }
    });

    $('#month, #source').on('change', function() {
        updateExportLink();
        table.ajax.reload();
    });

    $('#btnSaveCampaignRegionalImage').on('click', function() {
        saveTableAsImage();
    });

    updateExportLink();
});
</script>
@endsection
