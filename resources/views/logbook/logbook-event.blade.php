@extends('master')
@section('title') Logbook Ramadhan 2026 @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet"/>
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet"/>
<style>
    .card-title { font-weight: bold; }
    .select2-container .select2-selection--single {
        height: 35px !important;
        padding: 6px 10px;
    }

    /* Filter Card Styling */
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

    .filter-group small {
        font-size: 11px;
        color: #6c757d;
        margin-top: 2px;
        font-weight: normal;
    }

    .filter-group input,
    .filter-group select {
        width: 100%;
    }

    .loading-spinner {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        text-align: center;
    }

    .loading-spinner.active {
        display: block;
    }

    .loading-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
    }

    .loading-overlay.active {
        display: block;
    }

    .spinner-border-sm {
        width: 2rem;
        height: 2rem;
    }
</style>
@endsection

@section('content')

{{-- FILTER BAR --}}
<!-- Loading Overlay -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999;">
    <div id="loading-spinner" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; z-index: 10000;">
        <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: white; margin-bottom: 10px; display: block;"></i>
        <p style="color: white; font-size: 16px;">Loading data...</p>
    </div>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <h5><i class="fas fa-filter"></i> FILTER DATA LOGBOOK RAMADHAN 2026</h5>
    
    <div class="filter-row">
        @if(Auth::user()->role === 'Admin')
        <div class="filter-group">
            <label for="filter_canvasser">Canvasser</label>
            <select id="filter_canvasser" class="form-control select2">
                <option value="">Semua Canvasser</option>
                @foreach($canvassers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <small>Pilih canvasser untuk melihat logbook spesifik</small>
        </div>
        @endif

        <div class="filter-group">
            <label for="filter_regional">Regional</label>
            <select id="filter_regional" class="form-control select2">
                <option value="">Semua Regional</option>
                @foreach($regionals as $regional)
                    <option value="{{ $regional }}">{{ $regional }}</option>
                @endforeach
            </select>
            <small>Pilih regional untuk memfilter area geografis</small>
        </div>

        <div class="filter-group">
            <label for="month">Bulan</label>
            <input type="month" id="month" class="form-control" value="{{ now()->format('Y-m') }}">
            <small>Pilih bulan untuk melihat logbook periode tertentu</small>
        </div>

        <div class="filter-group">
            <button id="btnExport" class="btn btn-success w-100" style="height: 38px;">
                <i class="fa fa-file-excel"></i> Export Excel
            </button>
            <small style="color: #28a745; margin-top: 6px;">Download data sesuai filter</small>
        </div>
    </div>
</div>


{{-- TABLE --}}
<div class="card">
    <div class="card-header bg-danger text-white">
        <h4 class="font-weight-bold">Logbook Ramadhan 2026</h4>
    </div>

    <div class="card-body table-responsive">
        <table class="table table-bordered table-sm" id="leadsMasterTable">
            <thead class="bg-dark text-white">
                <tr>
                    <th>Canvasser</th>
                    <th>Regional</th>
                    <th>Nama Perusahaan</th>
                    <th>Akun Myads</th>
                    <th>No HP</th>
                    <th>Tipe Data</th>
                    <th>Tanggal</th>
                    <th>Komitmen</th>
                    <th>Plan Min Topup</th>
                    <th>Status</th>
                    <th>Realisasi Topup</th>

                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>


@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

$('#formEdit').on('submit', function (e) {
    e.preventDefault();

    $.ajax({
        url: $(this).attr('action'),
        type: 'POST',
        data: $(this).serialize(),
        success: function () {
            $('#modalEdit').modal('hide');
            $('#leadsMasterTable').DataTable().ajax.reload(null, false);
        },
        error: function (xhr) {
            alert('Update gagal');
            console.log(xhr.responseText);
        }
    });
});

$(function () {
    $('.select2').select2({ width: '100%' });

    // Function untuk show loading
    function showLoading() {
        $('#loading-overlay').show();
    }

    // Function untuk hide loading
    function hideLoading() {
        $('#loading-overlay').hide();
    }

    let table = $('#leadsMasterTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        responsive: true,
        ajax: {
            url: "{{ route('logbook.data') }}",
            data: function (d) {
                d.canvasser   = $('#filter_canvasser').val();
                d.regional    = $('#filter_regional').val();
                d.month       = $('#month').val();
            },
            beforeSend: function() {
                showLoading();
            },
            complete: function() {
                hideLoading();
            }
        },
        columns: [
            { data: 'user_name', searchable: true },
            { data: 'regional', searchable: true },
            { data: 'company_name', searchable: true },
            { data: 'myads_account', searchable: true },
            { data: 'mobile_phone', searchable: true },
            { data: 'data_type' },
            { data: 'created_at' },
            { data: 'komitmen' },
            { data: 'plan_min_topup' },
            { data: 'status' },
            { data: 'total_settlement_klien' }
        ]
    });

    // Auto-reload table ketika filter berubah
    $('#filter_canvasser').on('change', function () {
        table.ajax.reload();
    });

    $('#filter_regional').on('change', function () {
        table.ajax.reload();
    });

    $('#month').on('change', function () {
        table.ajax.reload();
    });

    // Export dengan filter yang sedang diterapkan
    $('#btnExport').on('click', function () {
        let params = {
            regional: $('#filter_regional').val(),
            month: $('#month').val()
        };

        window.location = "{{ route('leads-master.export') }}?" + $.param(params);
    });

});
</script>
@endsection
