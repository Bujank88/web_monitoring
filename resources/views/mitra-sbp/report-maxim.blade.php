@extends('master')
@section('title') {{ $pageTitle ?? 'Report Campaign Maxim' }} @endsection

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
    <h5><i class="fas fa-filter"></i> MAXIM REPORT FILTER</h5>
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
            <div class="col-lg col-md-6 col-6 mb-2">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Success</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="countSuccess">0</div>
                    </div>
                </div>
            </div>
            <div class="col-lg col-md-6 col-6 mb-2">
                <div class="card border-left-danger">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Failed</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="countFailed">0</div>
                    </div>
                </div>
            </div>
            <div class="col-lg col-md-6 col-6 mb-2">
                <div class="card border-left-success">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Harga</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="countTotalHarga">Rp 0</div>
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
                    <i class="fas fa-bullhorn mr-2"></i>{{ $pageTitle ?? 'Report Campaign Maxim' }}
                </h3>
                <div class="btn-actions">
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
                                <th>Tanggal Tayang</th>
                                <th>ID Iklan</th>
                                <th>Judul Pesan Iklan</th>
                                <th>Operator Seluler</th>
                                <th>Kategori Iklan</th>
                                <th>Tipe Kanal</th>
                                <th>Success</th>
                                <th>Failed</th>
                                <th>Read</th>
                                <th>Click</th>
                                <th>Percentage Read</th>
                                <th>Percentage Click</th>
                                <th>Total Harga</th>
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
<script>
    $(document).ready(function() {
        function formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
        }

        function updateExportLink() {
            var month = $('#month').val();
            var url = "{{ route('report-maxim.export') }}" + "?month=" + encodeURIComponent(month);
            $('#btnExportCampaign').attr('href', url);
        }

        var table = $('#campaignTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('report-maxim.data') }}",
                data: function(d) {
                    d.month = $('#month').val();
                }
            },
            columns: [
                { data: 'tanggal_iklan', name: 'mr.tgl_tayang' },
                { data: 'id_iklan', name: 'mr.id_iklan' },
                { data: 'judul_pesan_iklan', name: 'mr.judul_pesan_iklan' },
                { data: 'operator_seluler', name: 'mr.operator_seluler' },
                { data: 'kategori_iklan', name: 'mr.kategori_iklan' },
                { data: 'tipe_kanal', name: 'mr.tipe_kanal' },
                { data: 'success', name: 'mr.sukses' },
                { data: 'failed', name: 'mr.gagal' },
                { data: 'read', name: 'mr.read' },
                { data: 'click', name: 'mr.click' },
                { data: 'percentage_read', name: 'percentage_read', searchable: false },
                { data: 'percentage_click', name: 'percentage_click', searchable: false },
                { data: 'total_harga', name: 'mr.total_harga' }
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
                $('#countSuccess').text(new Intl.NumberFormat('id-ID').format(summary.total_success || 0));
                $('#countFailed').text(new Intl.NumberFormat('id-ID').format(summary.total_failed || 0));
                $('#countTotalHarga').text(formatRupiah(summary.total_harga || 0));
            }
        });

        $('#month').on('change', function() {
            updateExportLink();
            table.ajax.reload();
        });

        updateExportLink();
    });
</script>
@endsection



