@extends('master')
@section('title') Powerhouse 2M - Report 2M @endsection

@section('css')
<style>
    .report-hero {
        background: linear-gradient(135deg, #581c87 0%, #7c3aed 100%);
        color: #fff;
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 18px;
        box-shadow: 0 12px 28px rgba(88, 28, 135, 0.18);
    }

    .report-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .report-table th,
    .report-table td {
        border: 1px solid #d6d9de;
        padding: 10px 12px;
        vertical-align: middle;
    }

    .report-table thead th {
        background: #f1f5f9;
        color: #0f172a;
        font-weight: 700;
        text-align: center;
    }

    .ph-row td {
        background: #f8f4ff;
        font-weight: 700;
        color: #4c1d95;
    }

    .lead-row td:first-child {
        padding-left: 28px;
        color: #334155;
    }

    .total-row td {
        background: #fff7ed;
        font-weight: 700;
        color: #9a3412;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .lead-actions {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .lead-actions .btn {
        white-space: nowrap;
    }

    .modal-body pre {
        white-space: pre-wrap;
        word-break: break-word;
        font-family: inherit;
        font-size: 14px;
        margin: 0;
    }
</style>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Solusi belum bisa disimpan.</strong>
        <ul class="mb-0 mt-2 pl-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="report-hero">
    <h3 class="mb-2"><i class="fas fa-chart-bar mr-2"></i>Report 2M</h3>
    <p class="mb-0">Ringkasan per Powerhouse dan detail leads untuk periode Juli sampai Desember {{ $reportYear }}.</p>
</div>

<div class="report-card">
    <div class="table-responsive">
        <table class="report-table">
            <thead>
                <tr>
                    <th style="min-width: 280px; text-align: left;">Powerhouse / Leads</th>
                    @foreach($months as $month)
                        <th>{{ $month }}</th>
                    @endforeach
                    <th>Achievement</th>
                    <th>Target</th>
                    <th>%</th>
                    <th style="min-width: 180px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportRows as $powerhouse)
                    <tr class="ph-row">
                        <td>{{ $powerhouse['powerhouse_name'] }}</td>
                        @foreach($months as $month)
                            <td class="text-right">{{ number_format($powerhouse['month_totals'][$month] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td class="text-right">{{ number_format($powerhouse['achievement_total'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($powerhouse['target'], 0, ',', '.') }}</td>
                        <td class="text-center">{{ number_format($powerhouse['percentage'], 1, ',', '.') }}</td>
                        <td></td>
                    </tr>

                    @foreach($powerhouse['leads'] as $lead)
                        <tr class="lead-row">
                            <td>
                                <div>{{ $lead['lead_name'] }}</div>
                                @if(!empty($lead['email']))
                                    <small class="text-muted">{{ $lead['email'] }}</small>
                                @endif
                            </td>
                            @foreach($months as $month)
                                <td class="text-right">{{ number_format($lead['months'][$month] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-right">{{ number_format($lead['achievement'], 0, ',', '.') }}</td>
                            <td class="text-right"></td>
                            <td class="text-center"></td>
                            <td class="text-center">
                                <div class="lead-actions">
                                    <button
                                        type="button"
                                        class="btn btn-info btn-sm btn-view-usecase"
                                        data-toggle="modal"
                                        data-target="#modalUsecase"
                                        data-lead-name="{{ $lead['lead_name'] }}"
                                        data-usecase="{{ $lead['usecase'] ?? '' }}"
                                    >
                                        Lihat Usecase
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm btn-edit-solusi"
                                        data-toggle="modal"
                                        data-target="#modalSolusi"
                                        data-lead-id="{{ $lead['lead_id'] }}"
                                        data-lead-name="{{ $lead['lead_name'] }}"
                                        data-solusi="{{ $lead['solusi'] ?? '' }}"
                                    >
                                        Isi Solusi
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    <tr class="total-row">
                        <td>Total {{ $powerhouse['powerhouse_name'] }}</td>
                        @foreach($months as $month)
                            <td class="text-right">{{ number_format($powerhouse['month_totals'][$month] ?? 0, 0, ',', '.') }}</td>
                        @endforeach
                        <td class="text-right">{{ number_format($powerhouse['achievement_total'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($powerhouse['target'], 0, ',', '.') }}</td>
                        <td class="text-center">{{ number_format($powerhouse['percentage'], 1, ',', '.') }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($months) + 5 }}" class="text-center">Belum ada data leads Powerhouse untuk ditampilkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalUsecase" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Detail Usecase</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h6 id="modalUsecaseLeadName" class="font-weight-bold mb-3"></h6>
                <pre id="modalUsecaseContent">-</pre>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSolusi" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" id="formSolusi">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Isi Solusi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6 id="modalSolusiLeadName" class="font-weight-bold mb-3"></h6>
                    <div class="form-group mb-0">
                        <label for="solusi_text">Solusi</label>
                        <textarea name="solusi" id="solusi_text" class="form-control" rows="6" placeholder="Masukkan solusi untuk lead ini"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Solusi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).on('click', '.btn-view-usecase', function () {
        const leadName = $(this).data('lead-name') || 'Lead Powerhouse';
        const usecase = $(this).data('usecase') || '-';

        $('#modalUsecaseLeadName').text(leadName);
        $('#modalUsecaseContent').text(usecase);
    });

    $(document).on('click', '.btn-edit-solusi', function () {
        const leadId = $(this).data('lead-id');
        const leadName = $(this).data('lead-name') || 'Lead Powerhouse';
        const solusi = $(this).data('solusi') || '';
        const actionTemplate = @json(route('powerhouse.2m.solusi.update', ['lead' => '__LEAD__']));

        $('#modalSolusiLeadName').text(leadName);
        $('#solusi_text').val(solusi);
        $('#formSolusi').attr('action', actionTemplate.replace('__LEAD__', leadId));
    });
</script>
@endsection
