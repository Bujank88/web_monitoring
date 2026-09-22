@extends('master')
@section('title') Summary Leads 2M @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet"/>
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet"/>

<style>
    .card-title { font-weight: bold; }
    .form-group label { font-weight: 600; }
    .select2-container .select2-selection--single {
        height: 35px !important;
        padding: 8px 12px;
        border: 1px solid #ced4da !important;
        border-radius: 6px !important;
        display: flex;
        align-items: center;
        font-size: 15px;
        background-color: #fff;
    }
    .text-danger { font-size: 13px; }
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
</style>
@endsection

@section('content')
@if (session('success_with_schedule'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const message = `{{ session('success_with_schedule') }}`.split('\n').join('<br>');
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            html: message,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'OK'
        });
    });
</script>
@endif

<div class="filter-card">
    <h5><i class="fas fa-filter"></i> FILTER DATA LEADS 2M</h5>

    <div class="filter-row">
        @if(Auth::user()->role != 'PH')
        <div class="filter-group">
            <label for="filter_powerhouse">Powerhouse</label>
            <select id="filter_powerhouse" class="form-control select2">
                <option value="">Semua Powerhouse</option>
                @foreach($powerhouses as $powerhouse)
                    <option value="{{ $powerhouse->id }}">{{ $powerhouse->name }}</option>
                @endforeach
            </select>
            <small>Pilih powerhouse untuk memfilter data</small>
        </div>
        @endif

        @if(Auth::user()->role != 'PH')
        <div class="filter-group">
            <label for="filter_regional">Regional</label>
            <select id="filter_regional" class="form-control select2">
                <option value="">Semua Regional</option>
                @foreach($regionals as $regional)
                    <option value="{{ $regional }}">{{ $regional }}</option>
                @endforeach
            </select>
            <small>Pilih regional untuk memfilter regional</small>
        </div>
        @endif

        <div class="filter-group">
            <label for="filter_flag_event">Flag Event</label>
            <select id="filter_flag_event" class="form-control select2">
                <option value="">Semua Flag Event</option>
                @foreach($flagEvents as $flagEvent)
                    <option value="{{ $flagEvent }}">{{ $flagEvent }}</option>
                @endforeach
            </select>
            <small>Pilih flag event untuk memfilter data</small>
        </div>

        <div class="filter-group">
            <label for="start_date">Tanggal Mulai</label>
            <input type="date" id="start_date" class="form-control">
            <small>Pilih tanggal awal periode</small>
        </div>

        <div class="filter-group">
            <label for="end_date">Tanggal Akhir</label>
            <input type="date" id="end_date" class="form-control">
            <small>Pilih tanggal akhir periode</small>
        </div>

        <div class="filter-group">
            <button id="btnExport" class="btn btn-success w-100" style="height: 38px;">
                <i class="fa fa-file-excel"></i> Export Excel
            </button>
            <small style="color: #28a745; margin-top: 6px;">Download data sesuai filter</small>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h4 class="font-weight-bold">Data Detail Leads 2M & Akun Myads</h4>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm" id="powerhouse2MTable">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Powerhouse</th>
                        <th>Regional</th>
                        <th>Nama Perusahaan</th>
                        <th>Email</th>
                        <th>No HP</th>
                        <th>Tipe Data</th>
                        <th>Flag Event</th>
                        <th>Tanggal</th>
                        <th>Total Settlement ({{ now()->translatedFormat('F Y') }})</th>
                        <th>Saldo Utama</th>
                        <th>Rekomendasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function () {
    $('.select2').select2({ width: '100%' });

    let table = $('#powerhouse2MTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        responsive: true,
        ajax: {
            url: "{{ route('powerhouse.2m.summary-data') }}",
            data: function (d) {
                d.powerhouse = $('#filter_powerhouse').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.regional = $('#filter_regional').val();
                d.flag_event = $('#filter_flag_event').val();
            }
        },
        columns: [
            { data: 'user_name', searchable: true },
            { data: 'regional', searchable: true },
            { data: 'company_name', searchable: true },
            { data: 'email', searchable: true },
            { data: 'mobile_phone', searchable: true },
            { data: 'data_type', searchable: false },
            { data: 'flag_event', searchable: true },
            { data: 'created_at', searchable: false },
            { data: 'total_settlement_klien', searchable: false },
            { data: 'saldo_utama', searchable: false },
            { data: 'rekomendasi', searchable: false },
            { data: 'aksi', orderable: false, searchable: false }
        ]
    });

    $('#filter_powerhouse, #filter_regional, #filter_flag_event').on('change', function () {
        table.ajax.reload();
    });

    $('#start_date').on('change', function () {
        let startDate = $(this).val();
        if (startDate) {
            $('#end_date').attr('min', startDate);
            if ($('#end_date').val() && $('#end_date').val() < startDate) {
                $('#end_date').val('');
            }
        }
        table.ajax.reload();
    });

    $('#end_date').on('change', function () {
        let endDate = $(this).val();
        if (endDate) {
            $('#start_date').attr('max', endDate);
            if ($('#start_date').val() && $('#start_date').val() > endDate) {
                $('#start_date').val('');
            }
        }
        table.ajax.reload();
    });

    $('#btnExport').on('click', function () {
        let params = {
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            powerhouse: $('#filter_powerhouse').val(),
            regional: $('#filter_regional').val(),
            flag_event: $('#filter_flag_event').val()
        };
        window.location = "{{ route('powerhouse.2m.summary-export') }}?" + $.param(params);
    });
});
</script>
@endsection
