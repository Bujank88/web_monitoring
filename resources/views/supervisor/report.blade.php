@extends('master')
@section('title', $title)
@section('content')
<div class="container-fluid py-3">
    <h3>{{ $title }}</h3><p>{{ $note }}</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="get" class="card card-body"><div class="row">
        <div class="col-md-5"><label for="canvasser">Canvasser</label><select id="canvasser" name="canvasser" class="form-control"><option value="">Semua anggota</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(request('canvasser') == $member->id)>{{ $member->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label for="month">Bulan</label><input id="month" name="month" type="month" value="{{ request('month') ?: now()->format('Y-m') }}" class="form-control"></div>
        <div class="col-md-3 pt-4"><button class="btn btn-primary">Terapkan</button> <a href="{{ url()->current() }}">Reset</a></div>
    </div></form>
    <div class="card card-body"><p>Total {{ $rows->total() }} baris</p><div class="table-responsive"><table class="table table-striped"><thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
    @forelse($rows as $row)<tr>@foreach($columns as $key => $label)<td>{{ str_contains($label, '(Rp)') ? number_format((float) $row->$key, 0, ',', '.') : ($row->$key ?? '-') }}</td>@endforeach</tr>
    @empty<tr><td colspan="{{ count($columns) }}">Tidak ada data anggota binaan sesuai filter.</td></tr>@endforelse
    </tbody></table></div>{{ $rows->links('pagination::bootstrap-4') }}</div>
</div>
@endsection
