@extends('master')
@section('title', 'Spectrum - Input Leads')

@section('content')
<div class="container-fluid">
    <div class="card card-info">
        <div class="card-header"><h3 class="card-title">Spectrum - Input Leads</h3></div>
        <div class="card-body">
            <p>Upload file Excel untuk menambahkan leads ke Data Leads Spectrum.</p>
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('spectrum.leads.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="file">File leads Excel <span class="text-danger">*</span></label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xlsx,.xls" required>
                    <small class="form-text text-muted">Format .xlsx / .xls, maksimal 10 MB dan 10.000 baris. Baris yang seluruh datanya identik akan dilewati.</small>
                </div>
                <div class="alert alert-light border">
                    <strong>Header pada baris pertama:</strong>
                    <p class="mb-2">{{ implode(' · ', \App\Models\LeadSpectrum::HEADERS) }}</p>
                    <small>Urutan kolom boleh berbeda. Tanggal menggunakan format tanggal Excel, YYYY-MM-DD, atau M/D/YYYY. Simpan nomor telepon sebagai teks agar angka nol di depan tetap tersimpan. File harus berisi satu sheet leads dengan header lengkap.</small>
                </div>
                <button type="submit" class="btn btn-info"><i class="fas fa-upload mr-1" aria-hidden="true"></i> Upload Leads</button>
                <a href="{{ route('spectrum.leads.index') }}" class="btn btn-outline-secondary ml-1">Data Leads</a>
            </form>
        </div>
    </div>
</div>
@endsection
