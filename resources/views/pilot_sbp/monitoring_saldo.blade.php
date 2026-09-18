@extends('master')

@section('title', $pageTitle ?? 'Monitoring Saldo')

@section('css')
<style>
    .saldo-summary-card {
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
    }

    .saldo-summary-card-highlight {
        border-width: 2px;
        box-shadow: 0 12px 28px rgba(37, 99, 235, 0.12);
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    }

    .saldo-summary-card .card-body {
        padding: 1.25rem;
    }

    .saldo-summary-label {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin-bottom: 0.35rem;
    }

    .saldo-summary-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0;
    }

    .saldo-summary-card-highlight .saldo-summary-value {
        font-size: 2.35rem;
        color: #1d4ed8;
    }

    .saldo-summary-note {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-top: 0.4rem;
        margin-bottom: 0;
    }

    .saldo-filter-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fff;
        margin-bottom: 1rem;
    }

    .saldo-filter-card .card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 700;
    }

    .saldo-history-table th {
        background: #0f766e;
        color: #fff;
        white-space: nowrap;
        text-align: center;
        vertical-align: middle;
    }

    .saldo-history-table td {
        vertical-align: middle;
    }

    .saldo-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .saldo-chip-masuk {
        background: #dcfce7;
        color: #166534;
    }

    .saldo-chip-keluar {
        background: #fee2e2;
        color: #991b1b;
    }
</style>
@endsection

@section('content')
<div class="saldo-filter-card card">
    <div class="card-header">
        <i class="fas fa-filter mr-2"></i> Filter Monitoring Saldo
    </div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="filterMonthSaldo">Bulan</label>
                <select id="filterMonthSaldo" class="form-control">
                    @foreach($months as $item)
                    <option value="{{ $item['value'] }}" {{ $item['selected'] ? 'selected' : '' }}>
                        {{ $item['label'] }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <div class="alert alert-light border mb-0">
                    <strong>Email Monitoring:</strong> {{ $monitoringEmail }}<br>    
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-3">
        <div class="card saldo-summary-card saldo-summary-card-highlight border-primary h-100">
            <div class="card-body">
                <div class="saldo-summary-label">Sisa Saldo</div>
                <div class="saldo-summary-value text-primary">Rp {{ number_format($remainingBalance, 0, ',', '.') }}</div>
                <p class="saldo-summary-note">Akumulasi seluruh histori, tidak mengikuti filter bulan</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card saldo-summary-card h-100">
            <div class="card-body">
                <div class="saldo-summary-label">Saldo Awal</div>
                <div class="saldo-summary-value">Rp {{ number_format($openingBalance, 0, ',', '.') }}</div>
                <p class="saldo-summary-note">Akumulasi sebelum bulan terpilih</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card saldo-summary-card h-100">
            <div class="card-body">
                <div class="saldo-summary-label">Total Masuk</div>
                <div class="saldo-summary-value text-success">Rp {{ number_format($totalIn, 0, ',', '.') }}</div>
                <p class="saldo-summary-note">Saldo Masuk by Top Up</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card saldo-summary-card h-100">
            <div class="card-body">
                <div class="saldo-summary-label">Total Keluar</div>
                <div class="saldo-summary-value text-danger">Rp {{ number_format($totalOut, 0, ',', '.') }}</div>
                <p class="saldo-summary-note">Saldo Keluar by Transfer</p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card saldo-summary-card h-100">
            <div class="card-body">
                <div class="saldo-summary-label">Saldo Akhir</div>
                <div class="saldo-summary-value text-primary">Rp {{ number_format($endingBalance, 0, ',', '.') }}</div>
                <p class="saldo-summary-note">Saldo akhir bulan terpilih</p>
            </div>
        </div>
    </div>
</div>

<div class="card card-primary card-outline mt-3">
    <div class="card-header">
        <h3 class="card-title">{{ $pageTitle ?? 'Monitoring Saldo' }}</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="monitoringSaldoTable" class="table table-bordered table-striped saldo-history-table w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Sumber</th>
                        <th>Email Reference</th>
                        <th>Saldo Masuk</th>
                        <th>Saldo Keluar</th>
                        <th>Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historyRows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['transaction_date'] }}</td>
                        <td>
                            <span class="saldo-chip {{ $row['transaction_type'] === 'Masuk' ? 'saldo-chip-masuk' : 'saldo-chip-keluar' }}">
                                {{ $row['transaction_type'] }}
                            </span>
                        </td>
                        <td>{{ $row['source'] }}</td>
                        <td>{{ $row['reference_email'] }}</td>
                        <td>{{ $row['amount_in'] > 0 ? 'Rp ' . number_format($row['amount_in'], 0, ',', '.') : '-' }}</td>
                        <td>{{ $row['amount_out'] > 0 ? 'Rp ' . number_format($row['amount_out'], 0, ',', '.') : '-' }}</td>
                        <td><strong>Rp {{ number_format($row['running_balance'], 0, ',', '.') }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(function () {
        $('#monitoringSaldoTable').DataTable({
            paging: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            searching: true,
            ordering: true,
            info: true,
            responsive: true,
            order: [[1, 'desc']],
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                zeroRecords: 'Data tidak ditemukan',
                paginate: {
                    previous: 'Prev',
                    next: 'Next'
                }
            }
        });

        $('#filterMonthSaldo').on('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('month', $(this).val());
            window.location.href = url.toString();
        });
    });
</script>
@endsection
