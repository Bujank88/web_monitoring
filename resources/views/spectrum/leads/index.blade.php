@extends('master')
@section('title', 'Spectrum - Data Leads')

@section('content')
@php
    $roleUpper = strtoupper(trim((string) Auth::user()->role));
    $canManageLeads = in_array($roleUpper, ['ADMIN', 'SPECTRUM'], true);
    $isCanvasser = $roleUpper === 'CVSR';

    $summaryFields = [
        'provider' => 'Provider',
        'last_name' => 'Last Name',
        'company_account' => 'Company / Account',
        'mobile_phone' => 'Mobile Phone',
        'email' => 'Email',
        'product' => 'Product',
        'pillar' => 'Pillar',
        'create_date' => 'Create Date',
        'status_call' => 'Status Call',
        'update_status' => 'Update',
    ];
    $dateFields = ['create_date', 'share_date', 'tgl_dispatch'];
@endphp

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
                    <label for="month" class="mr-2">Bulan Create Date</label>
                    <input type="month" class="form-control mr-2" id="month" name="month" value="{{ $month }}">
                    <button class="btn btn-info" type="submit">Filter</button>
                    <a href="{{ route('spectrum.leads.index') }}" class="btn btn-outline-secondary ml-2">Reset</a>
                </form>
                @if ($canManageLeads)
                    <a href="{{ route('spectrum.leads.create') }}" class="btn btn-info mb-2">
                        <i class="fas fa-plus mr-1" aria-hidden="true"></i> Input / Upload Leads
                    </a>
                @endif
            </div>
            <p class="text-muted">{{ number_format($leads->total(), 0, ',', '.') }} leads{{ $search !== '' || $month !== '' ? ' ditemukan' : ' tersimpan' }}.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th class="text-nowrap">Canvasser Penerima</th>
                            @foreach ($summaryFields as $label)
                                <th class="text-nowrap">{{ $label }}</th>
                            @endforeach
                            <th class="text-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            @php
                                $initialData = [];
                                foreach (\App\Models\LeadSpectrum::INITIAL_FIELDS as $field => $label) {
                                    $initialData[$label] = in_array($field, $dateFields, true)
                                        ? ($lead->$field?->format('d/m/Y') ?? '—')
                                        : (($lead->$field !== null && $lead->$field !== '') ? (string) $lead->$field : '—');
                                }

                                $canvasserData = [];
                                foreach (\App\Models\LeadSpectrum::CANVASSER_FIELDS as $field => $label) {
                                    $canvasserData[$label] = in_array($field, $dateFields, true)
                                        ? ($lead->$field?->format('d/m/Y') ?? '—')
                                        : (($lead->$field !== null && $lead->$field !== '') ? (string) $lead->$field : '—');
                                }
                            @endphp
                            <tr>
                                <td>{{ $leads->firstItem() + $loop->index }}</td>
                                <td style="min-width:240px;">
                                    @if (Auth::user()->hasRole('Admin'))
                                        <form method="POST" action="{{ route('spectrum.leads.assign', $lead) }}">
                                            @csrf
                                            @method('PATCH')
                                            <label class="sr-only" for="assign_{{ $lead->id }}">Bagikan lead ke canvasser</label>
                                            <select id="assign_{{ $lead->id }}" name="user_id" class="form-control form-control-sm mb-1">
                                                <option value="">Belum dibagikan</option>
                                                @if ($lead->user_id && ! $canvassers->contains('id', $lead->user_id))
                                                    <option value="{{ $lead->user_id }}" selected disabled>{{ $lead->canvasser?->name ?? 'Penerima tidak tersedia' }} (tidak aktif)</option>
                                                @endif
                                                @foreach ($canvassers as $canvasser)
                                                    <option value="{{ $canvasser->id }}" @selected((int) $lead->user_id === (int) $canvasser->id)>{{ $canvasser->name }} ({{ $canvasser->email }})</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-user-check mr-1"></i> Bagikan</button>
                                        </form>
                                    @else
                                        {{ $lead->canvasser?->name ?? 'Belum dibagikan' }}
                                    @endif
                                </td>
                                @foreach ($summaryFields as $field => $label)
                                    <td style="max-width: 220px; white-space: normal; overflow-wrap: anywhere;">
                                        {{ in_array($field, $dateFields, true) ? ($lead->$field?->format('d/m/Y') ?? '—') : ($lead->$field ?? '—') }}
                                    </td>
                                @endforeach
                                <td class="text-nowrap">
                                    <button type="button"
                                            class="btn btn-sm btn-info btn-read-lead"
                                            data-toggle="modal"
                                            data-target="#modalReadLead"
                                            data-title="{{ $lead->company_account ?? $lead->last_name ?? 'Detail Lead' }}"
                                            data-edit-url="{{ route('spectrum.leads.edit', $lead) }}"
                                            data-initial='@json($initialData)'
                                            data-canvasser='@json($canvasserData)'>
                                        Read
                                    </button>
                                    <a href="{{ route('spectrum.leads.edit', $lead) }}" class="btn btn-sm btn-warning">
                                        {{ $isCanvasser ? 'Update Progress' : 'Edit' }}
                                    </a>
                                    @if ($canManageLeads)
                                        <form method="POST" action="{{ route('spectrum.leads.destroy', $lead) }}" class="d-inline" onsubmit="return confirm('Hapus lead ini? Data yang dihapus tidak dapat dikembalikan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($summaryFields) + 3 }}" class="text-center py-4">
                                    {{ $search !== '' || $month !== '' ? 'Tidak ada leads yang sesuai filter.' : 'Belum ada leads tersimpan.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $leads->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

{{-- Modal Detail / Read Lead --}}
<div class="modal fade" id="modalReadLead" tabindex="-1" role="dialog" aria-labelledby="modalReadLeadLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalReadLeadLabel">Detail Lead</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6 class="font-weight-bold text-secondary border-bottom pb-1 mb-2">1. Data Awal Leads</h6>
                <div class="row mb-3" id="modalReadInitialBody"></div>

                <h6 class="font-weight-bold text-info border-bottom pb-1 mb-2">2. Progress & Update Canvasser (Call, Kualifikasi & Dispatch)</h6>
                <div class="row" id="modalReadCanvasserBody"></div>
            </div>
            <div class="modal-footer">
                <a href="#" id="modalReadEditBtn" class="btn btn-warning">
                    <i class="fas fa-edit mr-1"></i> {{ $isCanvasser ? 'Update Progress' : 'Edit Lead' }}
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const longLabels = ['Description', 'FU', 'Respon', 'Notes', 'Alamat', 'Detail Kebutuhan'];

    function renderGrid(container, entries) {
        container.innerHTML = '';
        Object.entries(entries).forEach(function ([label, value]) {
            const isLong = longLabels.includes(label);
            const col = document.createElement('div');
            col.className = isLong ? 'col-md-12 mb-2' : 'col-md-4 mb-2';

            const box = document.createElement('div');
            box.className = 'border rounded p-2 h-100 bg-light';

            const lbl = document.createElement('small');
            lbl.className = 'text-muted d-block font-weight-bold';
            lbl.textContent = label;

            const val = document.createElement('div');
            val.style.whiteSpace = 'pre-wrap';
            val.style.overflowWrap = 'anywhere';
            val.textContent = value;

            box.appendChild(lbl);
            box.appendChild(val);
            col.appendChild(box);
            container.appendChild(col);
        });
    }

    document.querySelectorAll('.btn-read-lead').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const title = this.getAttribute('data-title') || 'Detail Lead';
            const editUrl = this.getAttribute('data-edit-url') || '#';
            const initialData = JSON.parse(this.getAttribute('data-initial') || '{}');
            const canvasserData = JSON.parse(this.getAttribute('data-canvasser') || '{}');

            document.getElementById('modalReadLeadLabel').textContent = 'Detail Lead — ' + title;
            document.getElementById('modalReadEditBtn').setAttribute('href', editUrl);

            renderGrid(document.getElementById('modalReadInitialBody'), initialData);
            renderGrid(document.getElementById('modalReadCanvasserBody'), canvasserData);
        });
    });
});
</script>
@endsection
