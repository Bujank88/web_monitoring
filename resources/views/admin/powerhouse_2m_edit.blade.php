@extends('master')

@section('title', 'Update Leads 2M')

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
        background-color: #fff;
    }
    .text-danger { font-size: 13px; }
</style>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Update Leads 2M</h3>
    </div>

    <div class="card-body">
        <form action="{{ route('powerhouse.2m.update', $lead) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>Powerhouse</label>
                @if(auth()->user()->role === 'Admin')
                    <select name="user_id" class="form-control select2">
                        @foreach($powerhouses as $powerhouse)
                            <option value="{{ $powerhouse->id }}" {{ (string) old('user_id', $lead->user_id) === (string) $powerhouse->id ? 'selected' : '' }}>
                                {{ $powerhouse->name }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" class="form-control" value="{{ $lead->user->name ?? auth()->user()->name }}" disabled>
                    <input type="hidden" name="user_id" value="{{ $lead->user_id }}">
                @endif
            </div>

            <div class="form-group">
                <label for="source_id">Source Leads</label>
                <select name="source_id" id="source_id" class="form-control select2">
                    @foreach ($leadSources as $source)
                        <option value="{{ $source->id }}" {{ (string) old('source_id', $lead->source_id) === (string) $source->id ? 'selected' : '' }}>
                            {{ $source->name }}
                        </option>
                    @endforeach
                </select>
                @error('source_id')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="sector_id">Sector</label>
                <select name="sector_id" id="sector_id" class="form-control select2">
                    @foreach ($sectors as $sector)
                        <option value="{{ $sector->id }}" {{ (string) old('sector_id', $lead->sector_id) === (string) $sector->id ? 'selected' : '' }}>
                            {{ $sector->name }}
                        </option>
                    @endforeach
                </select>
                @error('sector_id')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="company_name">Nama Perusahaan / Instansi</label>
                <input type="text" id="company_name" name="company_name" class="form-control" value="{{ old('company_name', $lead->company_name) }}">
                @error('company_name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="mobile_phone">No HP Pelanggan</label>
                <input type="text" id="mobile_phone" name="mobile_phone" class="form-control" value="{{ old('mobile_phone', $lead->mobile_phone) }}">
                @error('mobile_phone')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="email">Email Pelanggan</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $lead->email) }}">
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="nama">Nama Pelanggan</label>
                <input type="text" id="nama" name="nama" class="form-control" value="{{ old('nama', $lead->nama) }}">
                @error('nama')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="myads_account">Akun MyAds</label>
                <input type="text" id="myads_account" name="myads_account" class="form-control" value="{{ old('myads_account', $lead->myads_account) }}">
                @error('myads_account')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="usecase">Usecase</label>
                <textarea id="usecase" name="usecase" class="form-control" rows="4">{{ old('usecase', $lead->usecase) }}</textarea>
                @error('usecase')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group">
                <label for="solusi">Solusi</label>
                <textarea id="solusi" name="solusi" class="form-control" rows="4" readonly disabled>{{ old('solusi', $lead->solusi) }}</textarea>
                @error('solusi')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="form-group d-flex gap-2">
                <a href="{{ route('powerhouse.2m.summary') }}" class="btn btn-secondary flex-grow-1 m-1">Kembali</a>
                <button type="submit" class="btn btn-primary flex-grow-1 m-1">Simpan</button>
            </div>
        </form>
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
        });
    });
</script>
@endsection
