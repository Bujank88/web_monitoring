@extends('master')
@section('title', 'Spectrum - Edit Lead')

@section('content')
<div class="container-fluid">
    <div class="card card-info">
        <div class="card-header"><h3 class="card-title">Spectrum - Edit Lead</h3></div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('spectrum.leads.update', $lead) }}">
                @csrf
                @method('PATCH')
                <div class="row">
                    @foreach (\App\Models\LeadSpectrum::HEADERS as $field => $label)
                        @php
                            $isDate = in_array($field, ['create_date', 'share_date']);
                            $isText = in_array($field, ['description', 'fu', 'response']);
                            $value = old($field, $isDate ? $lead->$field?->format('Y-m-d') : $lead->$field);
                        @endphp
                        <div class="form-group col-md-{{ $isText ? '12' : '6' }}">
                            <label for="{{ $field }}">{{ $label }}</label>
                            @if ($isText)
                                <textarea id="{{ $field }}" name="{{ $field }}" rows="3" class="form-control @error($field) is-invalid @enderror">{{ $value }}</textarea>
                            @else
                                <input type="{{ $isDate ? 'date' : 'text' }}" id="{{ $field }}" name="{{ $field }}" value="{{ $value }}" class="form-control @error($field) is-invalid @enderror" @if (! $isDate) maxlength="255" @endif @if ($field === 'mobile_phone') inputmode="tel" @endif>
                            @endif
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-info">Simpan Perubahan</button>
                <a href="{{ route('spectrum.leads.index') }}" class="btn btn-outline-secondary ml-1">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
