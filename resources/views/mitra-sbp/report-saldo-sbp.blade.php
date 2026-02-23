@extends('master')
@section('title') {{ $pageTitle ?? 'Report Saldo' }} @endsection

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

    .table th {
        font-weight: bold;
        color: #ffffff !important;
        background: #dc3545;
        text-align: center;
        white-space: nowrap;
    }

    .table td {
        text-align: center;
        white-space: nowrap;
    }
</style>
@endsection

@section('content')

<div class="filter-card">
    <h5><i class="fas fa-filter"></i> FILTER REPORT SALDO</h5>
    <div class="row">
        <div class="col-md-3">
            <div class="filter-group">
                <label for="remark">Remark</label>
                <select id="remark" name="remark" class="form-control">
                    <option value="">Semua Remark</option>
                    <option value="Mitra SBP">Mitra SBP</option>
                    <option value="Agency">Agency</option>
                    <option value="Internal">Internal</option>
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="filter-group">
                <label for="area">Area</label>
                <select id="area" class="form-control">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area }}">{{ $area }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="filter-group">
                <label for="regional">Regional</label>
                <select id="regional" class="form-control">
                    <option value="">Semua Regional</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-wallet mr-2"></i>
            {{ $pageTitle ?? 'Report Saldo' }}
        </h5>
        <div class="btn-actions">
            <button type="button" class="btn btn-success btn-sm" id="btnSaveSaldoImage">
                <i class="fas fa-image mr-1"></i> Save Image
            </button>
            <a class="btn btn-success btn-sm" id="btnExportSaldo" href="#">
                <i class="fas fa-file-excel mr-1"></i> Download Excel
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive" id="saldoTableWrap">
            <table id="saldoTable" class="table table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Regional</th>
                        <th>Email</th>
                        <th>Remark</th>
                        <th>Saldo Utama</th>
                        <th>Saldo Monet</th>
                        <th>Saldo Exp Utama</th>
                        <th>Saldo Exp Monet</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Last Updated Info -->
<div class="row">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body text-center">
                <small class="text-muted">
                    <i class="fas fa-clock"></i> Last updated: @if($lastUpdated)
    {{ $lastUpdated }} WIB
@else
    -
@endif
                </small>
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
$(function() {

    // ============================
    // Mapping Area → Regional
    // ============================
    var areaRegionalMap = @json($areaRegionalMap); // contoh: { "Area1": ["SUMBAGSEL", "SUMBAGUT"], ... }

    function updateRegionalDropdown(area) {
        var regionalSelect = $('#regional');
        regionalSelect.empty().append('<option value="">Semua Regional</option>');

        if (area && areaRegionalMap[area]) {
            areaRegionalMap[area].forEach(function(r) {
                regionalSelect.append('<option value="'+r+'">'+r+'</option>');
            });
        }
    }

    function updateExportLink() {
        var remark = $('#remark').val();
        var area = $('#area').val();
        var regional = $('#regional').val();
        var month = $('#month').val();

        var url = "{{ route('report-saldo-sbp.export') }}";
        var params = [];

        if (month) params.push('month=' + encodeURIComponent(month));
        if (remark) params.push('remark=' + encodeURIComponent(remark));
        if (area) params.push('area=' + encodeURIComponent(area));
        if (regional) params.push('regional=' + encodeURIComponent(regional));

        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        $('#btnExportSaldo').attr('href', url);
    }

    function saveTableAsImage() {
        html2canvas(document.getElementById('saldoTableWrap'), {
            scale: 2,
            allowTaint: true,
            useCORS: true
        }).then(canvas => {
            var link = document.createElement('a');
            link.download = 'report-saldo-sbp-' + new Date().getTime() + '.png';
            link.href = canvas.toDataURL();
            link.click();
        }).catch(function() {
            alert('Gagal menyimpan gambar. Silakan coba lagi.');
        });
    }

    // ============================
    // Initialize DataTable
    // ============================
    var table = $('#saldoTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('report-saldo-sbp.data') }}",
            data: function(d) {
                d.remark = $('#remark').val();
                d.area = $('#area').val();
                d.regional = $('#regional').val();
            }
        },
        columns: [
            { data: 'area', name: 'a.area' },
            { data: 'regional', name: 'a.regional' },
            { data: 'email_myads', name: 'a.email_myads' },
            { data: 'remark', name: 'a.remark' },
            { data: 'saldo_utama', name: 'b.saldo_utama', render: function(d){ return d; } },
            { data: 'saldo_monet', name: 'b.saldo_monet', render: function(d){ return d; } },
            { data: 'saldo_exp_utama', name: 'b.saldo_exp_utama', render: function(d){ return d; } },
            { data: 'saldo_exp_monet', name: 'b.saldo_exp_monet', render: function(d){ return d; } }
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: 'Tidak ada data',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            paginate: { next: 'Next', previous: 'Prev' }
        }
    });

    // ============================
    // Filter Change
    // ============================
    $('#remark, #area, #regional, #month').on('change', function() {
        updateExportLink();
        table.ajax.reload();
    });

    // ============================
    // Update Regional when Area changes
    // ============================
    $('#area').on('change', function() {
        updateRegionalDropdown($(this).val());
        updateExportLink();
        table.ajax.reload();
    });

    $('#btnSaveSaldoImage').on('click', function() {
        saveTableAsImage();
    });

    updateExportLink();

});
</script>
@endsection
