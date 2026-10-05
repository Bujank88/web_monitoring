@extends('master')
@section('title', 'List Ticketing')

@section('content')
<div class="container-fluid">
    <div class="card card-info">
        <div class="card-header"><h3 class="card-title">List Ticketing</h3></div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <p>Aksi belum berhasil:</p>
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="GET" action="{{ route('ticketing.index') }}" class="row align-items-end mb-3">
                <div class="form-group col-md-6">
                    <label for="q">Cari tiket</label>
                    <input id="q" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" maxlength="255" placeholder="Nomor tiket, PIC, akun / campaign, atau diagnosis">
                </div>
                <div class="form-group col-md-3">
                    <label for="filter-status">Status</label>
                    <select id="filter-status" name="status" class="form-control">
                        <option value="">Semua status</option>
                        @foreach(\App\Models\Ticket::STATUSES as $status)
                            <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="date_from">Tanggal pengajuan dari</label>
                    <input type="date" id="date_from" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="form-group col-md-3">
                    <label for="date_to">Sampai tanggal</label>
                    <input type="date" id="date_to" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-info">Cari</button>
                    <a href="{{ route('ticketing.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">{{ $tickets->total() }} tiket ditemukan</span>
                <a href="{{ route('ticketing.create') }}" class="btn btn-info"><i class="fas fa-plus mr-1"></i> Input tiket</a>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead><tr>
                        <th>Nomor tiket</th><th>PIC</th><th>Waktu pengajuan</th><th>Keluhan</th>
                        <th>Akun / campaign</th><th>Prioritas</th><th>Status</th><th>Waktu selesai</th><th>Aksi</th>
                    </tr></thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr>
                            <td class="text-nowrap">{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticket->user_name ?? '—' }}</td>
                            <td class="text-nowrap">{{ $ticket->requested_at->format('d/m/Y H:i:s') }}</td>
                            <td style="min-width:240px;max-width:400px;overflow-wrap:anywhere;">
                                <strong>{{ $ticket->complaint_type ?? '—' }}</strong>
                                <div>{{ \Illuminate\Support\Str::limit($ticket->diagnosis_issue, 120) }}</div>
                                <details class="mt-1">
                                    <summary class="text-info">Detail penanganan</summary>
                                    <dl class="mb-0 mt-2">
                                        <dt>Jenis permintaan</dt><dd>{{ $ticket->request_type ?? '—' }}</dd>
                                        <dt>Channel / metode</dt><dd>{{ $ticket->channel ?? '—' }} / {{ $ticket->method ?? '—' }}</dd>
                                        <dt>Diagnosis</dt><dd style="white-space:pre-wrap;">{{ $ticket->diagnosis_issue ?? '—' }}</dd>
                                        <dt>Referensi bukti</dt><dd style="white-space:pre-wrap;">{{ $ticket->evidence_reference ?? '—' }}</dd>
                                        <dt>Hasil / update penanganan</dt><dd style="white-space:pre-wrap;">{{ $ticket->resolution_update ?? '—' }}</dd>
                                        <dt>Level penanganan</dt><dd>{{ $ticket->handling_level ?? '—' }}</dd>
                                    </dl>
                                </details>
                            </td>
                            <td style="max-width:240px;overflow-wrap:anywhere;">{{ $ticket->account_campaign_id ?? '—' }}</td>
                            <td>{{ $ticket->priority ?? '—' }}</td>
                            <td><span class="badge {{ $ticket->status === 'Closed' ? 'badge-success' : ($ticket->status === 'Open' ? 'badge-warning' : 'badge-secondary') }}">{{ $ticket->status }}</span></td>
                            <td class="text-nowrap">{{ $ticket->resolved_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                            <td style="min-width:190px;">
                                <a href="{{ route('ticketing.edit', $ticket) }}" class="btn btn-sm btn-primary mb-1">Edit</a>
                                <form method="POST" action="{{ route('ticketing.destroy', $ticket) }}" class="d-inline delete-ticket-form" data-ticket-number="{{ $ticket->ticket_number }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="version" value="{{ $ticket->versionToken() }}">
                                    <button type="submit" class="btn btn-sm btn-danger mb-1">Delete</button>
                                </form>
                                @if($ticket->status === 'Open')
                                    <button type="button" class="btn btn-sm btn-success text-nowrap" data-toggle="modal" data-target="#close-ticket-modal" data-close-url="{{ route('ticketing.close', $ticket) }}" data-ticket-number="{{ $ticket->ticket_number }}" data-requested-at="{{ $ticket->requested_at->format('Y-m-d\TH:i:s') }}">Closed Ticket</button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">Tidak ada tiket yang sesuai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $tickets->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<div class="modal fade" id="close-ticket-modal" tabindex="-1" aria-labelledby="close-ticket-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="close-ticket-form">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title" id="close-ticket-title">Tutup tiket <span id="close-ticket-number"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Batal"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Status akan menjadi Closed dengan tanggal dan jam penyelesaian yang Anda isi.</p>
                    <div class="form-group">
                        <label for="close-resolved-at">Tanggal dan jam Closed *</label>
                        <input type="datetime-local" id="close-resolved-at" name="resolved_at" class="form-control" step="1" value="{{ old('resolved_at', now()->format('Y-m-d\TH:i:s')) }}" required>
                        <small class="form-text text-muted">Tidak boleh sebelum waktu pengajuan atau melewati waktu saat ini.</small>
                    </div>
                    <label for="close-resolution">Hasil penanganan *</label>
                    <textarea id="close-resolution" name="resolution_update" class="form-control" rows="4" maxlength="10000" required>{{ old('resolution_update') }}</textarea>
                    <small class="form-text text-muted">Tuliskan penyelesaian keluhan. Catatan penanganan sebelumnya tetap tersimpan.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="confirm-close-ticket" disabled>Simpan dan tutup tiket</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.querySelectorAll('.delete-ticket-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!window.confirm('Hapus tiket ' + form.dataset.ticketNumber + '? Data tiket akan dihapus permanen.')) {
            event.preventDefault();
            return;
        }
        form.querySelector('button').disabled = true;
    });
});
$('#close-ticket-modal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    document.getElementById('close-ticket-form').action = button.attr('data-close-url');
    document.getElementById('close-ticket-number').textContent = button.attr('data-ticket-number');
    document.getElementById('close-resolved-at').min = button.attr('data-requested-at');
    document.getElementById('confirm-close-ticket').disabled = false;
    document.getElementById('confirm-close-ticket').textContent = 'Simpan dan tutup tiket';
});
$('#close-ticket-modal').on('shown.bs.modal', function () {
    document.getElementById('close-resolution').focus();
});
$('#close-ticket-modal').on('hidden.bs.modal', function () {
    document.getElementById('close-resolution').value = '';
    document.getElementById('close-resolved-at').value = document.getElementById('close-resolved-at').defaultValue;
    document.getElementById('confirm-close-ticket').disabled = true;
});
document.getElementById('close-ticket-form').addEventListener('submit', function () {
    const button = document.getElementById('confirm-close-ticket');
    button.disabled = true;
    button.textContent = 'Menyimpan...';
});
</script>
@endsection
