@extends('master')
@section('title', 'Pengali Bulanan 1Synergy')

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif
<div class="card">
    <div class="card-header"><h3 class="card-title">Pengali Bulanan 1Synergy</h3></div>
    <div class="card-body">
        <p>Total Balance Terpakai = (sukses SMS &times; pengali SMS) + (sukses WABA &times; pengali WABA) pada bulan terpilih.</p>
        <form method="GET" action="{{ route('one-synergy.monthly-multipliers') }}" class="mb-4">
            <label for="selectedMonth">Pilih bulan</label>
            <div class="input-group" style="max-width: 380px;">
                <input type="month" id="selectedMonth" name="month" class="form-control" value="{{ $month }}" required>
                <div class="input-group-append"><button class="btn btn-outline-primary" type="submit">Tampilkan</button></div>
            </div>
        </form>
        <form method="POST" action="{{ route('one-synergy.monthly-multipliers.store') }}">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            @foreach(['sms_multiplier' => 'SMS', 'waba_multiplier' => 'WABA'] as $field => $channel)
            <div class="form-group" style="max-width: 380px;">
                <label for="{{ $field }}">Pengali {{ $channel }} untuk {{ $month }} (Rp per sukses)</label>
                <input type="number" id="{{ $field }}" name="{{ $field }}" class="form-control" min="0" max="9999999999999.99" step="0.01" value="{{ old('month') === $month ? old($field, $rates?->{$field}) : $rates?->{$field} }}" required>
            </div>
            @endforeach
            <p class="text-muted">Perubahan langsung digunakan pada ringkasan report bulan tersebut.</p>
            <button type="submit" class="btn btn-primary">Simpan Pengali</button>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header"><h3 class="card-title">Daftar Pengali Bulanan</h3></div>
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead><tr><th>Bulan</th><th>Pengali SMS</th><th>Pengali WABA</th><th>Terakhir diubah</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($settings as $setting)
                <tr>
                    <td>{{ $setting->month }}</td>
                    <td>{{ number_format($setting->sms_multiplier, 2, ',', '.') }}</td>
                    <td>{{ $setting->waba_multiplier === null ? 'Belum diatur' : number_format($setting->waba_multiplier, 2, ',', '.') }}</td>
                    <td>{{ $setting->updated_at }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('one-synergy.monthly-multipliers', ['month' => $setting->month]) }}">Edit</a></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">Belum ada pengali yang diatur.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
