@extends('master')
@section('title') Input Data Panen Poin @endsection

@section('css')
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
</style>
@endsection

@section('content')

<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-gradient-info text-white">
                <h5 class="mb-0"><i class="fas fa-user-plus mr-2"></i>Form Input Data Pelanggan Panen Poin</h5>
            </div>

            <form action="{{ route('panenpoin.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="nama_pelanggan" class="form-label">
                            Nama Pelanggan <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            class="form-control @error('nama_pelanggan') is-invalid @enderror"
                            id="nama_pelanggan"
                            name="nama_pelanggan"
                            value="{{ old('nama_pelanggan') }}"
                            placeholder="Masukkan nama pelanggan"
                            required>
                        @error('nama_pelanggan')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="akun_myads_pelanggan" class="form-label">
                            Akun MyAds Pelanggan (Email) <span class="text-danger">*</span>
                        </label>
                        <input type="email"
                            class="form-control @error('akun_myads_pelanggan') is-invalid @enderror"
                            id="akun_myads_pelanggan"
                            name="akun_myads_pelanggan"
                            value="{{ old('akun_myads_pelanggan') }}"
                            placeholder="contoh@email.com"
                            required>
                        @error('akun_myads_pelanggan')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="nomor_hp_pelanggan" class="form-label">
                            Nomor HP Pelanggan <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            class="form-control @error('nomor_hp_pelanggan') is-invalid @enderror"
                            id="nomor_hp_pelanggan"
                            name="nomor_hp_pelanggan"
                            value="{{ old('nomor_hp_pelanggan') }}"
                            placeholder="08xxxxxxxxxx"
                            required>
                        @error('nomor_hp_pelanggan')
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
                    <a href="{{ route('panenpoin.report') }}" class="btn btn-secondary">
                        <i class="fas fa-chart-bar mr-2"></i>Lihat Report
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
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
        $('#nomor_hp_pelanggan').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });
</script>
@endsection