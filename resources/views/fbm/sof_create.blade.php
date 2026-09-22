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
                        <label for="waba_id">WABA ID</label>
                        <input type="text" name="waba_id" id="waba_id" class="form-control" value="{{ old('waba_id') }}">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pic{{ $isAdmin ? '' : '_display' }}">PIC</label>
                        @if($isAdmin)
                            <select name="pic" id="pic" class="form-control select2" required>
                                <option value="">Pilih PIC</option>
                                @foreach($picOptions as $picOption)
                                    <option value="{{ $picOption }}" {{ old('pic') === $picOption ? 'selected' : '' }}>{{ $picOption }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" id="pic_display" class="form-control" value="{{ optional(Auth::user())->name }}" readonly>
                        @endif
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

@section('js')
<style>
    .select2-container--default .select2-selection--single {
        height: calc(2.25rem + 2px);
        padding: .375rem .75rem;
        border: 1px solid #ced4da;
        border-radius: .25rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5rem;
        padding-left: 0;
        color: #495057;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(2.25rem + 2px);
        right: .5rem;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        $('.select2').select2({
            width: '100%',
            placeholder: 'Pilih PIC',
            allowClear: false
        });
    });
</script>
@endsection
