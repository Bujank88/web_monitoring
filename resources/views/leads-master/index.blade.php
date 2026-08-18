@extends('master')
@section('title') Leads Master @endsection

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
    .d-flex.gap-3 > label {
        cursor: pointer;
        user-select: none;
    }
    .text-danger { font-size: 13px; }

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
@elseif (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
@endif

@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
@endif

<!-- Loading Overlay -->
<div id="loading-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999;">
    <div id="loading-spinner" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; z-index: 10000;">
        <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: white; margin-bottom: 10px; display: block;"></i>
        <p style="color: white; font-size: 16px;">Loading data...</p>
    </div>
</div>

<!-- Filter Card -->
<div class="filter-card">
    <h5><i class="fas fa-filter"></i> FILTER DATA LEADS</h5>
    
    <div class="filter-row">
        @if(Auth::user()->role != 'cvsr')
        <div class="filter-group">
            <label for="filter_canvasser">Canvasser</label>
            <select id="filter_canvasser" class="form-control select2">
                <option value="">Semua Canvasser</option>
                @foreach($canvassers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <small>Pilih canvasser untuk memfilter canvasser</small>
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
            <label for="filter_data_type">Tipe Data</label>
            <select id="filter_data_type" class="form-control select2">
                <option value="">Semua Tipe Data</option>
                <option value="Leads">Leads</option>
                <option value="Eksisting Akun">Eksisting Akun</option>
            </select>
            <small>Pilih tipe data untuk memfilter data</small>
        </div>

        <div class="filter-group">
            <label for="filter_rekomendasi">Rekomendasi</label>
            <select id="filter_rekomendasi" class="form-control select2">
                <option value="">Semua Rekomendasi</option>
                <option value="Push Campaign">Push Campaign</option>
                <option value="Push Topup">Push Topup</option>
            </select>
            <small>Pilih rekomendasi untuk memfilter data</small>
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
        <h4 class="font-weight-bold">Data Detail Leads & Akun Myads</h4>
    </div>

    <div class="card-body">
        {{-- TABLE --}}
        <div class="table-responsive">
            <table class="table table-bordered table-sm" id="leadsMasterTable">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Canvasser</th>
                        <th>Regional</th>
                        <th>Nama Perusahaan</th>
                        <th>Email</th>
                        <th>No HP</th>
                        <th>Tipe Data</th>
                        <th>Flag Event</th>
                        <th>Tanggal</th>
                        <th>Total Settlement ({{now()->translatedFormat('F Y')}})</th>
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

@if(auth()->check() && in_array(auth()->user()->role, ['Admin', 'cvsr']))
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Insert Logbook</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>

      <form id="formEdit" action="{{ route('logbook.insert') }}" method="POST">
        @csrf
        <input type="hidden" name="id" id="insert_id">

        <div class="modal-body">

          <div class="form-group">
            <label>Komitmen</label>
            <select id="edit_komitmen" class="form-control" name="komitmen">
              <option value="New Leads">New Leads</option>
              <option value="100%">100%</option>
              <option value="50%">50%</option>
              <option value="<50%">&lt;50%</option>
            </select>
          </div>

          <div class="form-group">
            <label>Plan Min Topup</label>
            <input type="number" id="edit_plan" class="form-control" name="plan_min_topup">
          </div>

          <div class="form-group">
            <label>Status</label>
            <select id="edit_status" class="form-control" name="status">
              <option value="Initial">Initial</option>
              <option value="Prospect">Prospect</option>
              <option value="Register">Register</option>
              <option value="Repeat">Repeat</option>
              <option value="No Response">No Response</option>
            </select>
          </div>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>

      </form>

    </div>
  </div>
</div>
@endif
@endsection


@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    $('#formEdit').on('submit', function () {
        let btn = $('#btnSave');

        btn.prop('disabled', true);
        btn.html(
            '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>Saving...'
        );
    });
});

$(document).on('click', '.btn-add-logbook', function() {
    let id = $(this).data('id');
    $('#insert_id').val(id);
    $('#modalEdit').modal('show');
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
            url: "{{ route('leads-master.data') }}",
            data: function (d) {
                d.canvasser = $('#filter_canvasser').val();
                d.start_date = $('#start_date').val();
                d.end_date   = $('#end_date').val();
                d.regional = $('#filter_regional').val();
                d.flag_event = $('#filter_flag_event').val();
                d.data_type = $('#filter_data_type').val();
                d.rekomendasi = $('#filter_rekomendasi').val();
            },
            beforeSend: function() {
                showLoading();
            },
            complete: function() {
                hideLoading();
            }
        },
        columns: [
            { data: 'user_name', name: 'dls.user_name', searchable: true },
            { data: 'regional', name: 'dls.regional', searchable: true },
            { data: 'company_name', name: 'dls.company_name', searchable: true },
            { data: 'email', name: 'dls.email', searchable: true },
            { data: 'mobile_phone', name: 'dls.mobile_phone', searchable: true },
            { data: 'data_type', name: 'dls.data_type', searchable: false },
            { data: 'flag_event', name: 'dls.flag_event', searchable: true },
            { data: 'created_at', name: 'dls.created_at', searchable: false },
            { data: 'total_settlement_klien', name: 'dls.total_settlement_klien', searchable: false },
            { data: 'saldo_utama', name: 'dls.saldo_utama', searchable: false },
            { data: 'rekomendasi', name: 'rekomendasi', searchable: false },
            { data: 'aksi', orderable: false, searchable: false }
        ]
    });

    // Auto-reload table ketika filter berubah
    $('#filter_canvasser').on('change', function () {
        table.ajax.reload();
    });

    $('#filter_regional').on('change', function () {
        table.ajax.reload();
    });

    $('#filter_flag_event').on('change', function () {
        table.ajax.reload();
    });

    $('#filter_data_type').on('change', function () {
        table.ajax.reload();
    });

    $('#filter_rekomendasi').on('change', function () {
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

    // Export dengan filter yang sedang diterapkan
    $('#btnExport').on('click', function () {
        let params = {
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val(),
            canvasser: $('#filter_canvasser').val(),
            regional: $('#filter_regional').val(),
            flag_event: $('#filter_flag_event').val(),
            data_type: $('#filter_data_type').val(),
            rekomendasi: $('#filter_rekomendasi').val()
        };

        let query = $.param(params);
        window.location = "{{ route('leads-master.export') }}?" + query;
    });
});
</script>
@endsection
