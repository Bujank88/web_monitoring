@extends('master')

@section('title', 'Detail Leads Area 2')

@section('css')
<style>
    .card-title { font-weight: bold; }
    .form-group label { font-weight: 600; }
</style>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Detail Leads Area 2</h3>
    </div>

    <div class="card-body">
        <div class="form-group">
            <label>User Canvasser</label>
            <input type="text" class="form-control" value="{{ $lead->user->name ?? '-' }}" readonly>
        </div>

        <div class="form-group">
            <label>Nama Perusahaan / Instansi</label>
            <input type="text" class="form-control" value="{{ $lead->company_name }}" readonly>
        </div>

        <div class="form-group">
            <label>No HP Pelanggan</label>
            <input type="text" class="form-control" value="{{ $lead->mobile_phone }}" readonly>
        </div>

        <div class="form-group">
            <label>Email Pelanggan</label>
            <input type="text" class="form-control" value="{{ $lead->email }}" readonly>
        </div>

        <div class="form-group">
            <label>Source</label>
            <input type="text" class="form-control" value="{{ $lead->source->name ?? '-' }}" readonly>
        </div>

        <div class="form-group">
            <label>Nama Pelanggan</label>
            <input type="text" class="form-control" value="{{ $lead->nama }}" readonly>
        </div>

        <div class="form-group">
            <label>Sector</label>
            <input type="text" class="form-control" value="{{ $lead->sector->name ?? '-' }}" readonly>
        </div>

        <div class="form-group">
            <label>Status</label>
            <input type="text" class="form-control" value="{{ $lead->status == 1 ? 'Ok' : 'No' }}" readonly>
        </div>

        <div class="form-group">
            <label>Remarks</label>
            <textarea class="form-control" rows="3" readonly>{{ $lead->remarks }}</textarea>
        </div>

        <a href="{{ route('area2.leads-master.index') }}" class="btn btn-secondary btn-danger">Kembali</a>
    </div>
</div>
@endsection
