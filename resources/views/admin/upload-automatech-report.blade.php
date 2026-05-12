@extends('master')
@section('title') Upload Report Automatech @endsection

@section('css')
<style>
    .upload-card {
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .upload-card .card-header {
        background: linear-gradient(135deg, #dc3545 0%, #b91c1c 100%);
        color: #fff;
        border-radius: 14px 14px 0 0;
    }

    .helper-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 16px;
    }

    .recent-table th {
        background: #495057;
        color: #fff;
        white-space: nowrap;
    }
</style>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0 pl-3">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card upload-card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0" style="font-weight: 700;">
                    <i class="fas fa-file-arrow-up mr-2"></i>Upload Excel Report Automatech
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Upload file report Automatech khusus admin. Format file mengikuti template yang sudah disediakan.</p>

                <form action="{{ route('admin.upload.automatech-report.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="report_file">Pilih File Excel</label>
                        <input type="file" class="form-control" id="report_file" name="report_file" accept=".xlsx,.xls" required>
                        <small class="form-text text-muted">Format yang didukung: <code>.xlsx</code> dan <code>.xls</code>, maksimal 10 MB.</small>
                    </div>

                    <div class="helper-box mb-4">
                        <h5 class="mb-2" style="font-weight: 700;">Contoh File</h5>
                        <p class="mb-3">Gunakan template berikut sebagai acuan sebelum upload.</p>
                        <a href="{{ $templateFile }}" class="btn btn-outline-primary" target="_blank">
                            <i class="fas fa-file-excel mr-1"></i>Download Template Laporan MyADS
                        </a>
                    </div>

                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-upload mr-1"></i>Upload Report
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card upload-card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0" style="font-weight: 700;">
                    <i class="fas fa-clock-rotate-left mr-2"></i>Riwayat Upload
                </h3>
            </div>
            <div class="card-body">
                @if($uploadedFiles->isEmpty())
                <div class="text-muted">Belum ada file report Automatech yang diupload.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-bordered recent-table mb-0">
                        <thead>
                            <tr>
                                <th>Nama File</th>
                                <th>Ukuran</th>
                                <th>Waktu Upload</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($uploadedFiles as $file)
                            <tr>
                                <td>{{ $file['name'] }}</td>
                                <td>{{ $file['size'] }}</td>
                                <td>{{ $file['uploaded_at'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
