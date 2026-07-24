@extends('master')

@section('title', $pageTitle ?? 'Pengajuan SOF')

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">{{ $pageTitle ?? 'Pengajuan SOF' }}</h3>
    </div>
    <div class="card-body">
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

        <form action="{{ route('fbm.sof.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sender_name">Sender Name</label>
                        <input type="text" name="sender_name" id="sender_name" class="form-control" value="{{ old('sender_name') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="nomor_wa">Nomor WA</label>
                        <input type="text" name="nomor_wa" id="nomor_wa" class="form-control" value="{{ old('nomor_wa') }}" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pic_display">PIC</label>
                        <input type="text" id="pic_display" class="form-control" value="{{ optional(Auth::user())->name }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="verif_bisnis">Verif Bisnis</label>
                        <select name="verif_bisnis" id="verif_bisnis" class="form-control" required>
                            <option value="">Pilih Status</option>
                            @foreach(['Yes', 'On Progress', 'No'] as $status)
                                <option value="{{ $status }}" {{ old('verif_bisnis') === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
