@extends('master')
@section('title', 'Spectrum - Update Progress Lead')

@section('content')
@php
    $isCanvasser = strtolower(trim((string) Auth::user()->role)) === 'cvsr';
    $dateFields = ['create_date', 'share_date', 'tgl_dispatch'];
    $textFields = ['description', 'fu', 'response', 'notes', 'alamat', 'detail_kebutuhan'];
@endphp

<div class="container-fluid">
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('spectrum.leads.update', $lead) }}">
        @csrf
        @method('PATCH')
        @include('spectrum.leads.assignment')

        {{-- Data awal hanya dapat diedit Admin dan Spectrum. --}}
        <div class="card card-secondary collapsed-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-1"></i>
                    Data Awal Lead: <strong>{{ $lead->company_account ?? $lead->last_name }}</strong>
                    ({{ $lead->mobile_phone ?? 'No Phone' }} · {{ $lead->pillar ?? '-' }})
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i> {{ $isCanvasser ? 'Lihat Data Awal' : 'Lihat / Ubah Data Awal' }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if ($isCanvasser)
                    <p class="text-muted">Data awal lead hanya dapat dilihat. Perbarui progress pada bagian Call, Kualifikasi &amp; Dispatch di bawah.</p>
                    <div class="row">
                        @foreach (\App\Models\LeadSpectrum::INITIAL_FIELDS as $field => $label)
                            <div class="col-md-{{ in_array($field, $textFields, true) ? '12' : '4' }} mb-2">
                                <div class="border rounded p-2 bg-light h-100">
                                    <small class="text-muted d-block font-weight-bold">{{ $label }}</small>
                                    <div style="white-space: pre-wrap;">{{ in_array($field, $dateFields, true) ? ($lead->$field?->format('d/m/Y') ?? '—') : ($lead->$field ?? '—') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="row">
                        @foreach (\App\Models\LeadSpectrum::INITIAL_FIELDS as $field => $label)
                            @php
                                $isDate = in_array($field, $dateFields, true);
                                $isText = in_array($field, $textFields, true);
                                $value = old($field, $isDate ? $lead->$field?->format('Y-m-d') : $lead->$field);
                            @endphp
                            <div class="form-group col-md-{{ $isText ? '12' : '4' }}">
                                <label for="{{ $field }}">{{ $label }}</label>
                                @if ($isText)
                                    <textarea id="{{ $field }}" name="{{ $field }}" rows="2" class="form-control @error($field) is-invalid @enderror">{{ $value }}</textarea>
                                @else
                                    <input type="{{ $isDate ? 'date' : 'text' }}" id="{{ $field }}" name="{{ $field }}" value="{{ $value }}" class="form-control @error($field) is-invalid @enderror" @if (! $isDate) maxlength="255" @endif>
                                @endif
                                @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- BAGIAN 2: INPUTAN CANVASSER (TGL CALL 1ST - UPDATE) --}}
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-headset mr-1"></i> Update Canvasser (Call, Kualifikasi & Dispatch)</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach (\App\Models\LeadSpectrum::CANVASSER_FIELDS as $field => $label)
                        @php
                            $isDate = in_array($field, $dateFields, true);
                            $isText = in_array($field, $textFields, true);
                            $value = old($field, $isDate ? $lead->$field?->format('Y-m-d') : $lead->$field);
                        @endphp
                        <div class="form-group col-md-{{ $isText ? '12' : '4' }}">
                            <label for="{{ $field }}">{{ $label }}</label>
                            @if ($isText)
                                <textarea id="{{ $field }}" name="{{ $field }}" rows="2" class="form-control @error($field) is-invalid @enderror">{{ $value }}</textarea>
                            @else
                                <input type="{{ $isDate ? 'date' : 'text' }}" id="{{ $field }}" name="{{ $field }}" value="{{ $value }}" class="form-control @error($field) is-invalid @enderror" @if (! $isDate) maxlength="255" @endif @if ($field === 'nomor_pic') inputmode="tel" @endif>
                            @endif
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i> Simpan Update</button>
                <a href="{{ route('spectrum.leads.index') }}" class="btn btn-outline-secondary ml-1">Kembali</a>
            </div>
        </div>
    </form>
</div>
@endsection
