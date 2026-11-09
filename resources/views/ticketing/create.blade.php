@extends('master')
@section('title', isset($ticket) ? 'Edit Ticketing' : 'Input Ticketing')

@section('content')
<div class="container-fluid">
    <div class="card card-info">
        <div class="card-header"><h3 class="card-title">{{ isset($ticket) ? 'Edit tiket '.$ticket->ticket_number : 'Input Ticketing' }}</h3></div>
        <div class="card-body">
            <p class="text-muted">{{ isset($ticket) ? 'Perbarui data tiket. Nomor tiket tetap sama.' : 'Nomor tiket dibuat otomatis setelah disimpan.' }} Kolom bertanda * wajib diisi.</p>
            @if(session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <p>Periksa kembali data tiket:</p>
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ isset($ticket) ? route('ticketing.update', $ticket) : route('ticketing.store') }}" id="ticket-form">
                @csrf
                @if(isset($ticket))
                    @method('PATCH')
                    <input type="hidden" name="version" value="{{ old('version', $ticket->versionToken()) }}">
                @endif
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="pic_user_id">PIC pengajuan{{ isset($ticket) ? '' : ' *' }}</label>
                        <select id="pic_user_id" name="pic_user_id" class="form-control" @required(!isset($ticket))>
                            <option value="">{{ isset($ticket) ? 'Tetap gunakan PIC: '.($ticket->user_name ?? 'belum diisi') : 'Pilih PIC pengajuan' }}</option>
                            @foreach($picUsersByRole as $role => $picUsers)
                                <optgroup label="{{ $role }}">
                                    @foreach($picUsers as $picUser)
                                        <option value="{{ $picUser->id }}" @selected((string) old('pic_user_id', isset($ticket) ? '' : auth()->id()) === (string) $picUser->id)>{{ $picUser->name }} — {{ $picUser->email }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Cari nama, email, atau role pengguna Tracers.</small>
                        @if(isset($ticket))<small class="form-text text-muted">PIC saat ini: {{ $ticket->user_name ?? 'belum diisi' }}. Kosongkan pilihan untuk mempertahankannya.</small>@endif
                    </div>
                    <div class="form-group col-md-6">
                        <label for="requested_at">Waktu pengajuan *</label>
                        <input type="datetime-local" id="requested_at" name="requested_at" class="form-control" step="1" value="{{ old('requested_at', isset($ticket) ? $ticket->requested_at->format('Y-m-d\TH:i:s') : now()->format('Y-m-d\TH:i:s')) }}" required>
                    </div>
                    @php
                        $selects = [
                            'request_type' => ['Jenis permintaan', \App\Models\Ticket::REQUEST_TYPES, 'Complaint', true],
                            'complaint_type' => ['Kategori keluhan', \App\Models\Ticket::COMPLAINT_TYPES, '', true],
                            'channel' => ['Channel', \App\Models\Ticket::CHANNELS, '', false],
                            'method' => ['Metode pengiriman', \App\Models\Ticket::METHODS, '', false],
                            'status' => ['Status', \App\Models\Ticket::STATUSES, 'Open', true],
                            'priority' => ['Prioritas', \App\Models\Ticket::PRIORITIES, 'Low', true],
                            'handling_level' => ['Level penanganan', \App\Models\Ticket::HANDLING_LEVELS, '', false],
                        ];
                    @endphp
                    @foreach($selects as $name => [$label, $options, $default, $required])
                    <div class="form-group col-md-6">
                        <label for="{{ $name }}">{{ $label }}{{ $required ? ' *' : '' }}</label>
                        <select id="{{ $name }}" name="{{ $name }}" class="form-control" @required($required)>
                            <option value="">{{ $required ? 'Pilih '.$label : 'Tidak berlaku / belum diketahui' }}</option>
                            @foreach($options as $option)
                                <option value="{{ $option }}" @selected(old($name, isset($ticket) ? $ticket->{$name} : $default) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                    <div class="form-group col-md-6">
                        <label for="account_campaign_id">Akun / invoice / campaign ID *</label>
                        <input id="account_campaign_id" name="account_campaign_id" class="form-control" value="{{ old('account_campaign_id', $ticket->account_campaign_id ?? '') }}" maxlength="5000" required>
                        <small class="form-text text-muted">Boleh diisi email akun atau beberapa ID campaign.</small>
                    </div>
                    @foreach(['diagnosis_issue' => ['Diagnosis isu', true], 'evidence_reference' => ['Referensi bukti', false], 'resolution_update' => ['Hasil / update penanganan', false]] as $name => [$label, $required])
                    <div class="form-group col-md-12">
                        <label for="{{ $name }}">{{ $label }}{{ $required ? ' *' : '' }}</label>
                        <textarea id="{{ $name }}" name="{{ $name }}" class="form-control" rows="3" maxlength="{{ $name === 'evidence_reference' ? 5000 : 10000 }}" @required($required)>{{ old($name, isset($ticket) ? $ticket->{$name} : '') }}</textarea>
                        @if($name === 'evidence_reference')<small class="form-text text-muted">Isi nama sheet bukti, nomor referensi, atau tautan bukti.</small>@endif
                        @if($name === 'resolution_update')<small class="form-text text-muted">Wajib diisi jika status Closed.</small>@endif
                    </div>
                    @endforeach
                    <div class="form-group col-md-6">
                        <label for="resolved_at">Waktu penyelesaian</label>
                        <input type="datetime-local" id="resolved_at" name="resolved_at" class="form-control" step="1" value="{{ old('resolved_at', isset($ticket) ? $ticket->resolved_at?->format('Y-m-d\TH:i:s') : '') }}">
                        <small class="form-text text-muted">Isi hanya untuk tiket Closed, minimal sama dengan waktu pengajuan.</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-info" id="save-ticket"><i class="fas fa-save mr-1"></i> Simpan tiket</button>
                @if(isset($ticket))<a href="{{ route('ticketing.index') }}" class="btn btn-outline-secondary">Batal</a>@endif
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$('#pic_user_id').select2({
    width: '100%',
    placeholder: 'Cari nama, email, atau role PIC',
    minimumResultsForSearch: 0,
    matcher: function (params, data) {
        const term = (params.term || '').trim().toLowerCase();
        if (!term) return data;
        if (data.children) {
            const roleMatches = data.text.toLowerCase().includes(term);
            const children = data.children.filter(function (child) {
                return roleMatches || child.text.toLowerCase().includes(term);
            });
            return children.length ? $.extend({}, data, { children: children }) : null;
        }
        return data.text.toLowerCase().includes(term) ? data : null;
    },
    language: {
        noResults: function () { return 'Pengguna tidak ditemukan'; },
        searching: function () { return 'Mencari...'; }
    }
});
document.getElementById('ticket-form').addEventListener('submit', function () {
    const button = document.getElementById('save-ticket');
    button.disabled = true;
    button.textContent = 'Menyimpan...';
});
</script>
@endsection
