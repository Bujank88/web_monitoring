@extends('master')

@section('title', 'Edit Lead')

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
    .d-flex.gap-3 > label {
        cursor: pointer;
        user-select: none;
    }
    .text-danger { font-size: 13px; }
</style>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Edit Lead</h3>
    </div>

    <div class="card-body">
        <form action="{{ route('leads-master.update', $lead) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- USER NAME --}}
            <div class="form-group">
                <label>User Canvasser</label>
                @if(auth()->user()->role === 'Admin')
                    <select name="user_id" class="form-control select2">
                        @foreach($canvassers as $canvasser)
                            <option value="{{ $canvasser->id }}" {{ (string) old('user_id', $lead->user_id) === (string) $canvasser->id ? 'selected' : '' }}>
                                {{ $canvasser->name }} ({{ $canvasser->role }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" class="form-control" value="{{ $lead->user->name ?? auth()->user()->name }}" disabled>
                    <input type="hidden" name="user_id" value="{{ $lead->user_id ?? auth()->id() }}">
                @endif
            </div>
            @if(($lead->data_type ?? old('data_type')) === 'Eksisting Akun')
                    
            @else
                {{-- LEAD SOURCES --}}
                <div class="form-group">
                    <label for="source_id">Source Leads</label>
                    <select name="source_id" id="source_id" class="form-control select2">
                        {{-- <option value="">-- Pilih Source --</option> --}}
                        @foreach ($leadSources as $ls)
                            <option value="{{ $ls->id }}" {{ old('source_id', $lead->source_id) == $ls->id ? 'selected' : '' }}>
                                {{ $ls->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('source_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            @endif
            

            {{-- NAMA PERUSAHAAN / INSTANSI --}}
            <div class="form-group">
                <label for="company_name">Nama Perusahaan / Instansi</label>
                <input type="text" id="company_name" name="company_name" class="form-control" 
                    placeholder="Masukkan nama perusahaan atau instansi" value="{{ old('company_name', $lead->company_name) }}">
                @error('company_name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- NO HP --}}
            <div class="form-group">
                <label for="mobile_phone">No HP Pelanggan</label>
                <input type="text" id="mobile_phone" name="mobile_phone" class="form-control" 
                    placeholder="Masukkan nomor HP pelanggan" value="{{ old('mobile_phone', $lead->mobile_phone) }}">
                @error('mobile_phone')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            
            {{-- EMAIL --}}
            <div class="form-group">
                <label for="email">Email Pelanggan</label>
                <input type="email" id="email" name="email" class="form-control" 
                    placeholder="Masukkan email pelanggan" value="{{ old('email', $lead->email) }}">
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- NAMA PELANGGAN --}}
            <div class="form-group">
                <label for="nama">Nama Pelanggan</label>
                <input type="text" id="nama" name="nama" class="form-control" 
                    placeholder="Masukkan nama pelanggan" value="{{ old('nama', $lead->nama) }}">
                @error('nama')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- SECTOR --}}
            <div class="form-group">
                <label for="sector_id">Sector</label>
                <select name="sector_id" id="sector_id" class="form-control select2">
                    <option value="">-- Pilih Sector --</option>
                    @foreach ($sectors as $sector)
                        <option value="{{ $sector->id }}" {{ old('sector_id', $lead->sector_id) == $sector->id ? 'selected' : '' }}>
                            {{ $sector->name }}
                        </option>
                    @endforeach
                </select>
                @error('sector_id')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- Akun Myads --}}
            <div class="form-group">
                <label for="myads_account">Akun MyAds</label>
                <input type="text" id="myads_account" name="myads_account" class="form-control" placeholder="Masukkan Akun MyAds" value="{{ old('myads_account', $lead->myads_account ?? '') }}"
                    @if(($lead->data_type ?? old('data_type')) === 'Eksisting Akun')
                        required
                    @endif
                >

                @if(($lead->data_type ?? old('data_type')) === 'Eksisting Akun')
                    
                @else
                    <small class="text-danger">
                        *) Diisi jika sudah register akun MyAds
                    </small>
                @endif
                @error('myads_account')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- STATUS DEAL --}}
            {{-- <div class="form-group">
                <label>Status Deal</label>
                <div class="d-flex gap-3">
                    <label>
                        <input type="radio" name="status" value="No" {{ old('status', $lead->status == 0 ? 'No' : 'Ok') == 'No' ? 'checked' : '' }}> No
                    </label>
                    <label>
                        <input type="radio" name="status" value="Ok" {{ old('status', $lead->status == 1 ? 'Ok' : 'No') == 'Ok' ? 'checked' : '' }}> Ok
                    </label>
                </div>
                @error('status')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div> --}}

            {{-- REMARKS --}}
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" class="form-control" rows="3" 
                    placeholder="Tambahkan catatan jika perlu">{{ old('remarks', $lead->remarks) }}</textarea>
                @error('remarks')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            {{-- BUTTONS KEMBALI & SIMPAN --}}
            <div class="form-group d-flex gap-2">
                <a href="{{ route('leads-master.index') }}" class="btn btn-secondary flex-grow-1 m-1">Kembali</a>
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
