@extends('master')
@section('title') Input Data AM Level UP @endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .card {
        animation: fadeInUp 0.6s ease-out;
    }

    .form-label {
        font-weight: 600;
        color: #5a5c69;
    }

    .btn-submit {
        background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
        border: none;
        padding: 10px 30px;
    }

    .btn-submit:hover {
        background: linear-gradient(180deg, #224abe 10%, #4e73df 100%);
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
</style>
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-gradient-info text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus mr-2"></i>Form Input Data Pelanggan AM Level UP</h5>
            </div>

            <form action="{{ route('amlevelup.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">User</label>
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                                <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="company_name" class="form-label">Nama Perusahaan / Instansi <span class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('company_name') is-invalid @enderror"
                                    id="company_name"
                                    name="company_name"
                                    value="{{ old('company_name') }}"
                                    placeholder="Masukkan nama perusahaan atau instansi"
                                    required>
                                @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mobile_phone" class="form-label">No HP Pelanggan <span class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('mobile_phone') is-invalid @enderror"
                                    id="mobile_phone"
                                    name="mobile_phone"
                                    value="{{ old('mobile_phone') }}"
                                    placeholder="62xxxxxxxxxxx"
                                    required>
                                @error('mobile_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Pelanggan <span class="text-danger">*</span></label>
                                <input type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="Masukkan email pelanggan"
                                    required>
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nama" class="form-label">Nama Pelanggan</label>
                                <input type="text"
                                    class="form-control @error('nama') is-invalid @enderror"
                                    id="nama"
                                    name="nama"
                                    value="{{ old('nama') }}"
                                    placeholder="Masukkan nama pelanggan">
                                @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sector_id" class="form-label">Sector</label>
                                <select name="sector_id" id="sector_id" class="form-control select2">
                                    <option value="">-- Pilih Sector --</option>
                                    @foreach($sectors as $sector)
                                        <option value="{{ $sector->id }}" {{ old('sector_id') == $sector->id ? 'selected' : '' }}>
                                            {{ $sector->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('sector_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="myads_account" class="form-label">Akun MyAds <span class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control @error('myads_account') is-invalid @enderror"
                                    id="myads_account"
                                    name="myads_account"
                                    value="{{ old('myads_account') }}"
                                    placeholder="Masukkan akun MyAds"
                                    required>
                                <small class="text-danger">*) Diisi jika sudah register akun MyAds</small>
                                @error('myads_account')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea
                            class="form-control @error('remarks') is-invalid @enderror"
                            id="remarks"
                            name="remarks"
                            rows="3"
                            placeholder="Tambahkan catatan jika perlu">{{ old('remarks') }}</textarea>
                        @error('remarks')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <small>Pastikan data yang diinput sudah benar sebelum menyimpan.</small>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-save mr-2"></i>Simpan Data
                    </button>
                    <a href="{{ route('amlevelup.report') }}" class="btn btn-secondary">
                        <i class="fas fa-chart-bar mr-2"></i>Lihat Report
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "-- Pilih --",
            allowClear: true,
            width: '100%'
        });

        // Show success message using SweetAlert
        @if(session('success'))
            @if(session('is_existing_account'))
                Swal.fire({
                    icon: 'info',
                    title: 'Akun Sudah Ada',
                    html: `{{ session('success') }}<br><br><small class="text-muted">Notifikasi akun telah dikirim ke email dan WhatsApp Anda.</small>`,
                    confirmButtonColor: '#4e73df',
                    timer: 5000,
                    showConfirmButton: true
                });
            @else
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: `{{ session('success') }}`,
                    confirmButtonColor: '#4e73df',
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif
        @endif

        // Show error message using SweetAlert
        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan!',
                text: `{{ session('error') }}`,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Tutup'
            });
        @endif

        // Validate phone number
        $('#mobile_phone').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
</script>
@endsection
