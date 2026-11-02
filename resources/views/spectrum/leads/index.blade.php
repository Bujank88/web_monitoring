@extends('master')
@section('title', 'Spectrum - Data Leads')

@section('content')
<div class="container-fluid">
    <div class="card card-info">
        <div class="card-header"><h3 class="card-title">Spectrum - Data Leads</h3></div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <form method="GET" action="{{ route('spectrum.leads.index') }}" class="form-inline mb-2">
                    <label for="search" class="sr-only">Cari leads</label>
                    <input type="search" class="form-control mr-2" id="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nama, company, email, telepon">
                    <button class="btn btn-info" type="submit">Cari</button>
                    <a href="{{ route('spectrum.leads.index') }}" class="btn btn-outline-secondary ml-2">Reset</a>
                </form>
                <a href="{{ route('spectrum.leads.create') }}" class="btn btn-info mb-2"><i class="fas fa-upload mr-1" aria-hidden="true"></i> Upload Leads</a>
            </div>
            <p class="text-muted">{{ number_format($leads->total(), 0, ',', '.') }} leads{{ $search !== '' ? ' ditemukan' : ' tersimpan' }}.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead><tr>
                        <th>No.</th>
                        @foreach (\App\Models\LeadSpectrum::HEADERS as $label)<th class="text-nowrap">{{ $label }}</th>@endforeach
                        <th>Action</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <td>{{ $leads->firstItem() + $loop->index }}</td>
                                @foreach (\App\Models\LeadSpectrum::HEADERS as $field => $label)
                                    <td style="min-width: 130px; max-width: 360px; white-space: pre-wrap; overflow-wrap: anywhere;">{{ in_array($field, ['create_date', 'share_date']) ? ($lead->$field?->format('d/m/Y') ?? '—') : ($lead->$field ?? '—') }}</td>
                                @endforeach
                                <td class="text-nowrap">
                                    <a href="{{ route('spectrum.leads.edit', $lead) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="POST" action="{{ route('spectrum.leads.destroy', $lead) }}" class="d-inline" onsubmit="return confirm('Hapus lead ini? Data yang dihapus tidak dapat dikembalikan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count(\App\Models\LeadSpectrum::HEADERS) + 2 }}" class="text-center py-4">{{ $search !== '' ? 'Tidak ada leads yang sesuai pencarian.' : 'Belum ada leads. Upload file melalui menu Input Leads.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $leads->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
