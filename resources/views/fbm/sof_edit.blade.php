@extends('master')

@section('title', $pageTitle ?? 'Edit SOF')

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">{{ $pageTitle ?? 'Edit SOF' }}</h3>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('fbm.sof.update', $sof->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Sender Name</label>
                        <input type="text" class="form-control" value="{{ $sof->sender_name }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Nomor WA</label>
                        <input type="text" class="form-control" value="{{ $sof->nomor_wa }}" readonly>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>PIC</label>
                        <input type="text" class="form-control" value="{{ $sof->pic }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="verif_bisnis">Verif Bisnis</label>
                        <select name="verif_bisnis" id="verif_bisnis" class="form-control" required>
                            @foreach(['Yes', 'On Progress', 'No'] as $status)
                                <option value="{{ $status }}" {{ old('verif_bisnis', $sof->verif_bisnis) === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="credit_line">Credit Line</label>
                        @if($isAdmin)
                            <select name="credit_line" id="credit_line" class="form-control" required>
                                @foreach(['Yes', 'On Progress', 'No'] as $status)
                                    <option value="{{ $status }}" {{ old('credit_line', $sof->credit_line) === $status ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" class="form-control" value="{{ $sof->credit_line }}" readonly>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>SOF Saat Ini</label>
                        @if($isAdmin)
                            @if($sof->sof_file)
                                <div>
                                    <a href="{{ asset($sof->sof_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-pdf mr-1"></i> Lihat PDF
                                    </a>
                                </div>
                            @else
                                <input type="text" class="form-control" value="Belum ada file" readonly>
                            @endif
                        @else
                            <input type="text" class="form-control" value="Hanya Admin yang dapat melihat SOF" readonly>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sof_file">Upload SOF (PDF)</label>
                        @if($isAdmin)
                            <input type="file" name="sof_file" id="sof_file" class="form-control" accept="application/pdf">
                            <small class="text-muted">Kosongkan jika tidak ingin mengganti file.</small>
                        @else
                            <input type="text" class="form-control" value="Hanya Admin yang dapat upload PDF" readonly>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <a href="{{ route('fbm.sof.index') }}" class="btn btn-secondary mr-2">Kembali</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
