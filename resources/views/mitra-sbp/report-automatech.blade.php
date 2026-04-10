@extends('master')
@section('title') {{ $pageTitle ?? 'Report Campaign Automatech' }} @endsection

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
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    <h5><i class="fas fa-filter"></i> CAMPAIGN REPORT FILTER</h5>
    <div class="filter-row">
        <div class="filter-group">
            <label for="month">Month</label>
            <select id="month" name="month" class="form-control">
                @foreach ($months as $m)
                <option value="{{ $m['value'] }}" {{ $m['selected'] ? 'selected' : '' }}>
                    {{ $m['label'] }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <div class="row">
            <div class="col-lg col-md-4 col-6 mb-2">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Success</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="countSuccess">0</div>
                    </div>
                </div>
            </div>
            <div class="col-lg col-md-4 col-6 mb-2">
                <div class="card border-left-success">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Failed</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="countFailed">0</div>
                    </div>
                </div>
            </div>
            <div class="col-lg col-md-4 col-6 mb-2">
                <div class="card border-left-warning">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="countTotal">0</div>
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
                    <i class="fas fa-bullhorn mr-2"></i>{{ $pageTitle ?? 'Report Campaign Automatech' }}
                </h3>
                <div class="btn-actions">
                    <button type="button" class="btn btn-success btn-sm" id="btnSaveCampaignImage">
                        <i class="fas fa-image mr-1"></i> Save Image
                    </button>
                    <a class="btn btn-success btn-sm" id="btnExportCampaign" href="#">
                        <i class="fas fa-file-excel mr-1"></i> Download Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" id="campaignTableWrap">
                    <table id="campaignTable" class="table table-bordered table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Ad Date</th>
                                <th>Broadcast Date</th>
                                <th>Email</th>
                                <th>Ad ID</th>
                                <th>Ad Name</th>
                                <th>Institution Name</th>
                                <th>Province Area</th>
                                <th>Campaign Type</th>
                                <th>Inventory Type</th>
                                <th>Total</th>
                                <th>Success</th>
                                <th>Failed</th>
                                <th>Delivered</th>
                                <th>Read</th>
                                <th>Click</th>
                                <th>Used Balance</th>
                                <th>Message</th>
                                <th>Campaign Status</th>
                                <th>Remark</th>
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
        var selectedRemark = @json($selectedRemark ?? '');

        function updateExportLink() {
            var month = $('#month').val();
            var url = "{{ route('report-automatech.export') }}" + "?month=" + encodeURIComponent(month);
            if (selectedRemark) {
                url += "&remark=" + encodeURIComponent(selectedRemark);
            }
            $('#btnExportCampaign').attr('href', url);
        }

        function saveTableAsImage() {
            html2canvas(document.getElementById('campaignTableWrap'), {
                scale: 2,
                allowTaint: true,
                useCORS: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'report-campaign-automatech-' + new Date().getTime() + '.png';
                link.href = canvas.toDataURL();
                link.click();
            }).catch(err => {
                console.error('Error capturing image:', err);
                alert('Failed to save image. Please try again.');
            });
        }

        var table = $('#campaignTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('report-automatech.data') }}",
                data: function(d) {
                    d.month = $('#month').val();
                    d.remark = selectedRemark;
                }
            },
            columns: [
                { data: 'tanggal_iklan', name: 'b.created_at' },
                { data: 'broadcast_date', name: 'b.broadcast_date' },
                { data: 'email', name: 'a.email_myads' },
                { data: 'id_iklan', name: 'b.id_iklan' },
                { data: 'nama_iklan', name: 'b.nama_iklan' },
                { data: 'nama_instansi', name: 'b.nama_brand' },
                { data: 'area_provinsi', name: 'b.area_provinsi' },
                { data: 'campaign_type', name: 'b.tipe_iklan' },
                { data: 'inventory_type', name: 'b.tipe_inventori' },
                { data: 'total', name: 'b.total' },
                { data: 'success', name: 'b.sukses' },
                { data: 'failed', name: 'b.gagal' },
                { data: 'delivered', name: 'b.delivered' },
                { data: 'read', name: 'b.read' },
                { data: 'click', name: 'b.click' },
                { data: 'balance_terpakai', name: 'b.balance_terpakai' },
                {
                    data: 'pesan',
                    name: 'b.pesan',
                    render: function(data) {
                        if (!data) return '';
                        var text = data.toString();
                        if (text.length <= 50) return text;
                        var shortText = text.substring(0, 50) + '...';
                        return '<span title="' + text.replace(/"/g, '&quot;') + '">' + shortText + '</span>';
                    }
                },
                { data: 'campaign_status', name: 'b.status' },
                { data: 'remark', name: 'a.remark' }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Semua"]
            ],
            language: {
                emptyTable: 'No data available',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                lengthMenu: 'Show _MENU_ entries',
                search: 'Search:',
                zeroRecords: 'No matching records found',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous'
                }
            },
            drawCallback: function(settings) {
                var summary = (settings.json && settings.json.summary) ? settings.json.summary : {};
                $('#countSuccess').text(summary.success || 0);
                $('#countFailed').text(summary.failed || 0);
                $('#countTotal').text(summary.total || 0);
            }
        });

        $('#month').on('change', function() {
            updateExportLink();
            table.ajax.reload();
        });

        updateExportLink();
        $('#btnSaveCampaignImage').on('click', function() {
            saveTableAsImage();
        });
    });
</script>
@endsection

