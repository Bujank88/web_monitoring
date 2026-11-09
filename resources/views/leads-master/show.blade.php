@extends('master')

@section('title', 'Detail Leads')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
        background-color: #f9f9f9;
    }
    .text-danger { font-size: 13px; }
</style>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Detail Leads</h3>
    </div>

    <div class="card-body">
        {{-- USER NAME --}}
        <div class="form-group">
            <label>User Canvasser</label>
            <input type="text" class="form-control" value="{{ $lead->user->name ?? '-' }}" readonly>
        </div>

        {{-- KODE VOUCHER --}}
        <div class="form-group">
            <label>Kode Voucher</label>
            <input type="text" class="form-control" value="{{ $lead->kode_voucher }}" readonly>
        </div>

        {{-- NAMA PERUSAHAAN / INSTANSI --}}
        <div class="form-group">
            <label>Nama Perusahaan / Instansi</label>
            <input type="text" class="form-control" value="{{ $lead->company_name }}" readonly>
        </div>

        {{-- NO HP --}}
        <div class="form-group">
            <label>No HP Pelanggan</label>
            <input type="text" class="form-control" value="{{ $lead->mobile_phone }}" readonly>
        </div>

        {{-- EMAIL --}}
        <div class="form-group">
            <label>Email Pelanggan</label>
            <input type="text" class="form-control" value="{{ $lead->email }}" readonly>
        </div>

        {{-- LEAD SOURCE --}}
        <div class="form-group">
            <label>Source</label>
            <input type="text" class="form-control" value="{{ $lead->source->name ?? '-' }}" readonly>
        </div>

        {{-- NAMA PELANGGAN --}}
        <div class="form-group">
            <label>Nama Pelanggan</label>
            <input type="text" class="form-control" value="{{ $lead->nama }}" readonly>
        </div>

        {{-- SECTOR --}}
        <div class="form-group">
            <label>Sector</label>
            <input type="text" class="form-control" value="{{ $lead->sector->name ?? '-' }}" readonly>
        </div>

        {{-- STATUS --}}
        <div class="form-group">
            <label>Status</label>
            <input type="text" class="form-control" 
                value="{{ $lead->status == 1 ? 'Ok' : 'No' }}" readonly>
        </div>

        {{-- REMARKS --}}
        <div class="form-group">
            <label>Remarks</label>
            <textarea class="form-control" rows="3" readonly>{{ $lead->remarks }}</textarea>
        </div>

        <a href="{{ route('leads-master.index') }}" class="btn btn-secondary btn-danger">Kembali</a>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "-- Pilih --",
            allowClear: true,
            width: '100%'
        }).prop("disabled", true); // disable select2
    });
</script>
@endsection
