@extends('master')
@section('title', 'Tim Canvasser')
@section('content')
<div class="container-fluid py-3">
    <h3>Pengaturan Tim Canvasser</h3>
    <p>Buat akun dengan role Supervisor melalui menu User, kemudian pilih PIC setiap canvasser di bawah.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <div class="card card-body"><div class="table-responsive"><table class="table"><thead><tr><th>Canvasser</th><th>Email</th><th>PIC</th></tr></thead><tbody>
    @forelse($canvassers as $member)
        <tr><td>{{ $member->name }}</td><td>{{ $member->email }}</td><td><form method="post" action="{{ route('supervisor.team.assign') }}" class="d-flex">@csrf
            <input type="hidden" name="canvasser_id" value="{{ $member->id }}">
            <select name="supervisor_id" class="form-control mr-2" aria-label="PIC {{ $member->name }}"><option value="">Tanpa PIC</option>@foreach($supervisors as $pic)<option value="{{ $pic->id }}" @selected($member->supervisor_id == $pic->id)>{{ $pic->name }}</option>@endforeach</select>
            <button class="btn btn-primary">Simpan</button>
        </form></td></tr>
    @empty<tr><td colspan="3">Belum ada canvasser.</td></tr>@endforelse
    </tbody></table></div></div>
</div>
@endsection
