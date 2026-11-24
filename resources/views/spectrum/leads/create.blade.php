@extends('master')
@section('title', 'Spectrum - Input Leads')

@section('content')
<div class="container-fluid">
    <div class="card card-info card-outline card-tabs">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="input-leads-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ old('_mode') !== 'manual' ? 'active' : '' }}" id="tab-excel-link" data-toggle="pill" href="#tab-excel" role="tab">
                        <i class="fas fa-file-excel mr-1"></i> Upload Excel
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ old('_mode') === 'manual' ? 'active' : '' }}" id="tab-manual-link" data-toggle="pill" href="#tab-manual" role="tab">
                        <i class="fas fa-edit mr-1"></i> Input Manual
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="tab-content" id="input-leads-tabContent">
                {{-- TAB 1: UPLOAD EXCEL --}}
                <div class="tab-pane fade {{ old('_mode') !== 'manual' ? 'show active' : '' }}" id="tab-excel" role="tabpanel">
                    <p>Upload file Excel untuk menambahkan leads ke Data Leads Spectrum.</p>
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
                            <small>Kolom setelah 15 header utama bersifat opsional (file lama tetap dapat diunggah). Tanggal menggunakan format tanggal Excel, YYYY-MM-DD, atau M/D/YYYY. Simpan nomor telepon sebagai teks agar angka nol di depan tetap tersimpan.</small>
                        </div>
                        <button type="submit" class="btn btn-info"><i class="fas fa-upload mr-1" aria-hidden="true"></i> Upload Leads</button>
                        <a href="{{ route('spectrum.leads.index') }}" class="btn btn-outline-secondary ml-1">Data Leads</a>
                    </form>
                </div>

                {{-- TAB 2: INPUT MANUAL --}}
                <div class="tab-pane fade {{ old('_mode') === 'manual' ? 'show active' : '' }}" id="tab-manual" role="tabpanel">
                    <form method="POST" action="{{ route('spectrum.leads.store') }}">
                        @csrf
                        <input type="hidden" name="_mode" value="manual">
                        @include('spectrum.leads.assignment')
                        <div class="row">
                            @foreach (\App\Models\LeadSpectrum::HEADERS as $field => $label)
                                @php
                                    $isDate = in_array($field, ['create_date', 'share_date', 'tgl_dispatch']);
                                    $isText = in_array($field, ['description', 'fu', 'response', 'notes', 'alamat', 'detail_kebutuhan']);
                                    $value = old($field);
                                @endphp
                                <div class="form-group col-md-{{ $isText ? '12' : '4' }}">
                                    <label for="manual_{{ $field }}">
                                        {{ $label }}
                                        @if(in_array($field, ['last_name', 'company_account'])) <span class="text-danger">*</span> @endif
                                    </label>
                                    @if ($isText)
                                        <textarea id="manual_{{ $field }}" name="{{ $field }}" rows="2" class="form-control @error($field) is-invalid @enderror">{{ $value }}</textarea>
                                    @elseif ($field === 'provider')
                                        <select id="manual_{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror">
                                            <option value="">-- Pilih Provider --</option>
                                            <option value="Tsel" {{ $value === 'Tsel' ? 'selected' : '' }}>Tsel</option>
                                            <option value="Non Tsel" {{ $value === 'Non Tsel' ? 'selected' : '' }}>Non Tsel</option>
                                        </select>
                                    @elseif ($field === 'company_size')
                                        <select id="manual_{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror">
                                            <option value="">-- Pilih Company Size --</option>
                                            @foreach(['1 - 9 Karyawan', '10 - 49 Karyawan', '50 - 249 Karyawan', '>250 Karyawan'] as $opt)
                                                <option value="{{ $opt }}" {{ $value === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($field === 'pillar')
                                        <select id="manual_{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror">
                                            <option value="">-- Pilih Pillar --</option>
                                            @foreach(['MyAds', 'Digital Advertising'] as $opt)
                                                <option value="{{ $opt }}" {{ $value === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="{{ $isDate ? 'date' : 'text' }}" id="manual_{{ $field }}" name="{{ $field }}" value="{{ $value }}" class="form-control @error($field) is-invalid @enderror" @if (! $isDate) maxlength="255" @endif @if (in_array($field, ['mobile_phone', 'nomor_pic'])) inputmode="tel" @endif>
                                    @endif
                                    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1" aria-hidden="true"></i> Simpan Lead</button>
                        <a href="{{ route('spectrum.leads.index') }}" class="btn btn-outline-secondary ml-1">Data Leads</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
