@extends('master')

@section('title', 'Eksisting Akun')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .card-title { font-weight: bold; }
    .form-group label { font-weight: 600; font-size: 14px; color: #333; }
    
    /* Input styling */
    .form-control {
        border: 2px solid #e0e0e0 !important;
        border-radius: 8px !important;
        padding: 10px 12px !important;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #17a2b8 !important;
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25) !important;
        background-color: #f8ffff;
    }
    
    .select2-container .select2-selection--single {
        height: 40px !important;
        padding: 8px 12px !important;
        border: 2px solid #e0e0e0 !important;
        border-radius: 8px !important;
        display: flex;
        align-items: center;
        font-size: 14px;
        background-color: #fff;
    }
    
    .select2-container .select2-selection--single:focus {
        border-color: #17a2b8 !important;
    }
    
    .d-flex.gap-3 > label {
        cursor: pointer;
        user-select: none;
    }
    
    .text-danger { font-size: 13px; }
    
    /* Card styling */
    .card {
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
    }
    
    .card-header {
        background-color: #007bff !important;
        border-radius: 8px 8px 0 0 !important;
    }
    
    /* Button styling */
    .btn {
        border-radius: 6px !important;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
    }
    
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
    }
</style>
@endsection

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Eksisting Akun</h3>
    </div>

    <div class="card-body">
        <form action="{{ route('leads-master.store-existing') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                {{-- USER NAME --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label>User Canvasser</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                        <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- NAMA PERUSAHAAN / INSTANSI --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="company_name">Nama Perusahaan / Instansi</label>
                        <input type="text" id="company_name" name="company_name" class="form-control" 
                            placeholder="Masukkan nama perusahaan atau instansi" value="{{ old('company_name') }}">
                        @error('company_name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- NO HP --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="mobile_phone">No HP Pelanggan</label>
                        <input type="text" id="mobile_phone" name="mobile_phone" class="form-control" 
                            placeholder="Masukkan nomor HP pelanggan" value="{{ old('mobile_phone') }}">
                        @error('mobile_phone')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- EMAIL --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email">Email Pelanggan</label>
                        <input type="email" id="email" name="email" class="form-control" 
                            placeholder="Masukkan email pelanggan" value="{{ old('email') }}">
                        @error('email')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- NAMA PELANGGAN --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nama">Nama Pelanggan</label>
                        <input type="text" id="nama" name="nama" class="form-control" 
                            placeholder="Masukkan nama pelanggan" value="{{ old('nama') }}">
                        @error('nama')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- SECTOR --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sector_id">Sector</label>
                        <select name="sector_id" id="sector_id" class="form-control select2">
                            {{-- <option value="">-- Pilih Sector --</option> --}}
                            @foreach ($sectors as $sector)
                                <option value="{{ $sector->id }}" {{ old('sector_id') == $sector->id ? 'selected' : '' }}>
                                    {{ $sector->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('sector_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                {{-- Akun Myads --}}
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="myads_account">Akun MyAds</label>
                        <input type="text" id="myads_account" name="myads_account" class="form-control" 
                            placeholder="Masukkan Akun MyAds" value="{{ old('myads_account') }}" required>
                            <small class="text-danger">*) Diisi jika sudah register akun MyAds</small>
                        @error('myads_account')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- REMARKS --}}
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks" class="form-control" rows="3" 
                            placeholder="Tambahkan catatan jika perlu">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group d-flex gap-2">
                        <a href="{{ route('leads-master.index') }}" class="btn btn-secondary flex-grow-1 m-1">Kembali</a>
                        <button type="submit" class="btn btn-primary flex-grow-1 m-1">Simpan</button>
                    </div>
                </div>
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
