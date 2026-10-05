@extends('master')
@section('title', request()->routeIs('supervisor.leads') ? 'Data Leads & Akun' : 'Monitoring Tim Canvasser')
@section('content')
<div class="container-fluid py-3">
    <h3>{{ request()->routeIs('supervisor.leads') ? 'Data Leads & Akun' : 'Monitoring Tim Canvasser' }}</h3>
    <p>Leads dan akun anggota binaan Anda. Periode mengikuti tanggal pembuatan leads.</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="get" class="card card-body mb-3">
        <div class="row">
            <div class="col-md-3"><label for="canvasser">Canvasser</label><select id="canvasser" name="canvasser" class="form-control"><option value="">Semua anggota</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(request('canvasser') == $member->id)>{{ $member->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label for="from">Dari tanggal</label><input id="from" class="form-control" type="date" name="from" value="{{ request('from') }}"></div>
            <div class="col-md-2"><label for="to">Sampai tanggal</label><input id="to" class="form-control" type="date" name="to" value="{{ request('to') }}"></div>
            <div class="col-md-3"><label for="q">Perusahaan / email</label><input id="q" class="form-control" name="q" maxlength="100" value="{{ request('q') }}"></div>
            <div class="col-md-2 pt-4"><button class="btn btn-primary">Terapkan</button> <a href="{{ url()->current() }}">Reset</a></div>
        </div>
    </form>
    <div class="card card-body"><h5>Ringkasan anggota (sesuai filter)</h5>
        <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Canvasser</th><th>Status akun</th><th>Leads</th><th>Eksisting akun</th><th>Total</th></tr></thead><tbody>
        @forelse($members as $member)
            <tr><td>{{ $member->name }}</td><td>{{ $member->status }}</td><td>{{ $summary->get($member->id)->leads ?? 0 }}</td><td>{{ $summary->get($member->id)->accounts ?? 0 }}</td><td>{{ $summary->get($member->id)->total ?? 0 }}</td></tr>
        @empty<tr><td colspan="5">Belum ada anggota binaan. Admin dapat mengatur anggota melalui menu Tim Canvasser.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="card card-body"><h5>Data leads & akun ({{ $leads->total() }})</h5><div class="table-responsive">
        <table class="table table-striped"><thead><tr><th>Canvasser</th><th>Perusahaan</th><th>Kontak</th><th>Jenis</th><th>Rencana top up</th><th>Catatan</th><th>Dibuat</th></tr></thead><tbody>
        @forelse($leads as $lead)<tr><td>{{ $lead->canvasser_name }}</td><td>{{ $lead->company_name }}</td><td>{{ $lead->nama }}<br>{{ $lead->email }}<br>{{ $lead->mobile_phone }}</td><td>{{ $lead->data_type }}</td><td>Rp {{ number_format($lead->plan_min_topup, 0, ',', '.') }}</td><td style="white-space: pre-wrap">{{ $lead->remarks }}</td><td>{{ $lead->created_at }}</td></tr>
        @empty<tr><td colspan="7">Tidak ada data sesuai filter.</td></tr>@endforelse
        </tbody></table></div>{{ $leads->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
