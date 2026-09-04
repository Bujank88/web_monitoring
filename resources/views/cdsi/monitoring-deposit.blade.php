@extends('master')
@php($brandLabel = $brandLabel ?? 'CDSI')
@section('title') {{ $pageTitle ?? 'Monitoring Deposit CDSI' }} @endsection

@section('css')
<style>
    .deposit-filter-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .deposit-summary-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
        height: 100%;
    }

    .deposit-summary-card .card-body {
        padding: 1.25rem;
    }

    .deposit-summary-label {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin-bottom: 0.35rem;
    }

    .deposit-summary-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0;
    }

    .deposit-summary-note {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-top: 0.4rem;
        margin-bottom: 0;
    }

    .deposit-table th {
        background: #991b1b;
        color: #fff;
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
    }

    .deposit-table td {
        vertical-align: middle;
    }
</style>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card deposit-summary-card">
            <div class="card-body">
                <div class="deposit-summary-label">Total Topup</div>
                <div class="deposit-summary-value text-primary">Rp {{ number_format($summary['total_topup'] ?? 0, 0, ',', '.') }}</div>
                <p class="deposit-summary-note">Akumulasi {{ $periodLabel ?? 'Juni 2026 - Desember 2026' }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card deposit-summary-card">
            <div class="card-body">
                <div class="deposit-summary-label">Target Full</div>
                <div class="deposit-summary-value text-success">Rp {{ number_format($summary['target'] ?? 0, 0, ',', '.') }}</div>
                <p class="deposit-summary-note">Target full program deposit {{ $brandLabel }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="card deposit-summary-card">
            <div class="card-body">
                <div class="deposit-summary-label">Gap to Full Target</div>
                <div class="deposit-summary-value {{ ($summary['gap_to_target'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                    Rp {{ number_format($summary['gap_to_target'] ?? 0, 0, ',', '.') }}
                </div>
                <p class="deposit-summary-note">Achievement total {{ number_format($summary['achievement_percent'] ?? 0, 2, ',', '.') }}%</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-danger text-white">
        <h4 class="mb-0">
            <i class="fas fa-coins mr-2"></i>{{ $pageTitle ?? 'Monitoring Deposit CDSI' }}
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover deposit-table w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Month</th>
                        <th>Total Topup</th>
                        <th>Target Full</th>
                        <th>Gap / Sisa Target</th>
                        <th>Achievement</th>
                        <th>Transaksi</th>
                        <th>Akun</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($allMonthSummaries ?? []) as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['label'] ?? '-' }}</td>
                        <td class="text-right">Rp {{ number_format($item['total_topup'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($item['target'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right {{ ($item['gap_to_target'] ?? 0) <= 0 ? 'text-success font-weight-bold' : 'text-danger font-weight-bold' }}">
                            Rp {{ number_format($item['gap_to_target'] ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="text-center">{{ number_format($item['achievement_percent'] ?? 0, 2, ',', '.') }}%</td>
                        <td class="text-center">{{ number_format($item['total_transactions'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-center">{{ number_format($item['total_accounts'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
