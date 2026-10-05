@extends('master')
@section('title', 'Pengajuan SOF')
@section('content')
<div class="container-fluid py-3"><h3>Pengajuan SOF</h3><p>Ajukan SOF atas nama canvasser binaan Anda.</p>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if($members->isEmpty())<div class="alert alert-info">Belum ada anggota aktif yang dapat dipilih. Hubungi admin jika nama akun anggota sama dengan pengguna lain.</div>@endif
<form method="post" action="{{ route('supervisor.sof.store') }}" class="card card-body">@csrf
    <div class="form-group"><label for="canvasser_id">Canvasser / PIC</label><select id="canvasser_id" name="canvasser_id" class="form-control" required><option value="">Pilih canvasser</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(old('canvasser_id') == $member->id)>{{ $member->name }}</option>@endforeach</select></div>
    @foreach(['sender_name' => 'Sender Name', 'nomor_wa' => 'Nomor WA', 'waba_id' => 'WABA ID'] as $key => $label)
    <div class="form-group"><label for="{{ $key }}">{{ $label }}</label><input id="{{ $key }}" name="{{ $key }}" value="{{ old($key) }}" class="form-control" maxlength="{{ $key === 'nomor_wa' ? 50 : 255 }}" @required($key !== 'waba_id')></div>
    @endforeach
    <div class="form-group"><label for="verif_bisnis">Verifikasi Bisnis</label><select id="verif_bisnis" name="verif_bisnis" class="form-control" required>@foreach(['No', 'On Progress', 'Yes'] as $status)<option @selected(old('verif_bisnis') === $status)>{{ $status }}</option>@endforeach</select></div>
    <button class="btn btn-primary" @disabled($members->isEmpty())>Simpan Pengajuan</button>
</form></div>
@endsection
