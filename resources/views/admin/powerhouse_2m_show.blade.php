@extends('master')

@section('title', 'Detail Leads 2M')

@section('css')
<style>
    .card-title { font-weight: bold; }
    .form-group label { font-weight: 600; }
</style>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Detail Leads 2M</h3>
    </div>

    <div class="card-body">
        <div class="form-group">
            <label>Powerhouse</label>
            <input type="text" class="form-control" value="{{ $lead->user->name ?? '-' }}" readonly>
        </div>

        <div class="form-group">
            <label>Source Leads</label>
            <input type="text" class="form-control" value="{{ $lead->source->name ?? '-' }}" readonly>
        </div>

        <div class="form-group">
            <label>Sector</label>
            <input type="text" class="form-control" value="{{ $lead->sector->name ?? '-' }}" readonly>
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
            <label>Nama Pelanggan</label>
            <input type="text" class="form-control" value="{{ $lead->nama }}" readonly>
        </div>

        <div class="form-group">
            <label>Alamat</label>
            <textarea class="form-control" rows="3" readonly>{{ $lead->address }}</textarea>
        </div>

        <div class="form-group">
            <label>Akun MyAds</label>
            <input type="text" class="form-control" value="{{ $lead->myads_account }}" readonly>
        </div>

        <div class="form-group">
            <label>Flag Event</label>
            <input type="text" class="form-control" value="{{ $lead->flag_event }}" readonly>
        </div>

        <div class="form-group">
            <label>Usecase</label>
            <textarea class="form-control" rows="4" readonly>{{ $lead->usecase }}</textarea>
        </div>

        <div class="form-group">
            <label>Solusi</label>
            <textarea class="form-control" rows="4" readonly>{{ $lead->solusi }}</textarea>
        </div>

        <a href="{{ route('powerhouse.2m.summary') }}" class="btn btn-secondary">Kembali</a>
        <a href="{{ route('powerhouse.2m.edit', $lead) }}" class="btn btn-primary">Update</a>
    </div>
</div>
@endsection
