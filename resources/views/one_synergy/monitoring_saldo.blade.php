@extends('master')

@section('title', $pageTitle ?? 'Monitoring Saldo 1Synergy')

@section('css')
<style>
    .one-synergy-saldo-card {
        border: 1px solid #dbeafe;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(37, 99, 235, 0.08);
    }
    .one-synergy-saldo-card .card-body { padding: 1.25rem; }
    .one-synergy-saldo-highlight { background: linear-gradient(135deg, #eff6ff, #dbeafe); border-width: 2px; }
    .one-synergy-saldo-label { color: #64748b; font-size: .82rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .one-synergy-saldo-value { color: #0f172a; font-size: 1.55rem; font-weight: 700; }
    .one-synergy-saldo-highlight .one-synergy-saldo-value { color: #1d4ed8; font-size: 2.25rem; }
    .one-synergy-saldo-note { color: #94a3b8; font-size: .8rem; margin: .4rem 0 0; }
    .one-synergy-history th { background: #2563eb; color: #fff; text-align: center; white-space: nowrap; }
    .one-synergy-history td { vertical-align: middle; }
    .one-synergy-chip { border-radius: 999px; display: inline-flex; font-size: .8rem; font-weight: 700; padding: .35rem .7rem; }
    .one-synergy-chip-in { background: #dcfce7; color: #166534; }
    .one-synergy-chip-out { background: #fee2e2; color: #991b1b; }
</style>
@endsection

@section('content')
<div class="card one-synergy-saldo-card mb-3">
    <div class="card-header bg-light font-weight-bold"><i class="fas fa-filter mr-2"></i>Filter Monitoring Saldo 1Synergy</div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-4">
                <label for="filterMonthOneSynergy">Bulan</label>
                <select id="filterMonthOneSynergy" class="form-control">
                    @foreach($months as $item)
                    <option value="{{ $item['value'] }}" {{ $item['selected'] ? 'selected' : '' }}>{{ $item['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <!-- <div class="col-md-8">
                <div class="alert alert-light border mb-0"><strong>Email Monitoring:</strong> {{ $monitoringEmail }}</div>
            </div> -->
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-3">
        <div class="card one-synergy-saldo-card one-synergy-saldo-highlight">
            <div class="card-body">
                <div class="one-synergy-saldo-label">Sisa Saldo</div>
                <div class="one-synergy-saldo-value">Rp {{ number_format($remainingBalance, 0, ',', '.') }}</div>
                <p class="one-synergy-saldo-note">Akumulasi seluruh histori, tidak mengikuti filter bulan</p>
            </div>
        </div>
    </div>
    @foreach([
        ['Saldo Awal', $openingBalance, 'text-dark', 'Akumulasi sebelum bulan terpilih'],
        ['Total Masuk', $totalIn, 'text-success', 'Saldo masuk melalui top up'],
        ['Total Keluar', $totalOut, 'text-danger', 'Saldo keluar melalui transfer'],
        ['Saldo Akhir', $endingBalance, 'text-primary', 'Saldo akhir bulan terpilih'],
    ] as [$label, $value, $class, $note])
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card one-synergy-saldo-card h-100">
            <div class="card-body">
                <div class="one-synergy-saldo-label">{{ $label }}</div>
                <div class="one-synergy-saldo-value {{ $class }}">Rp {{ number_format($value, 0, ',', '.') }}</div>
                <p class="one-synergy-saldo-note">{{ $note }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title">{{ $pageTitle ?? 'Monitoring Saldo 1Synergy' }}</h3></div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="oneSynergyMonitoringSaldoTable" class="table table-bordered table-striped one-synergy-history w-100">
                <thead><tr><th>No</th><th>Tanggal</th><th>Tipe</th><th>Sumber</th><th>Email Reference</th><th>Saldo Masuk</th><th>Saldo Keluar</th><th>Running Balance</th></tr></thead>
                <tbody>
                    @foreach($historyRows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['transaction_date'] }}</td>
                        <td><span class="one-synergy-chip {{ $row['transaction_type'] === 'Masuk' ? 'one-synergy-chip-in' : 'one-synergy-chip-out' }}">{{ $row['transaction_type'] }}</span></td>
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
    $('#oneSynergyMonitoringSaldoTable').DataTable({
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        order: [[1, 'desc']],
        language: { search: 'Cari:', zeroRecords: 'Data tidak ditemukan', paginate: { previous: 'Prev', next: 'Next' } }
    });
    $('#filterMonthOneSynergy').on('change', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('month', $(this).val());
        window.location.href = url.toString();
    });
});
</script>
@endsection
