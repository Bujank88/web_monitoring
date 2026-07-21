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
</style>
@endsection

@section('content')
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
                    </tr>

                    @foreach($powerhouse['leads'] as $lead)
                        <tr class="lead-row">
                            <td>{{ $lead['lead_name'] }}</td>
                            @foreach($months as $month)
                                <td class="text-right">{{ number_format($lead['months'][$month] ?? 0, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="text-right">{{ number_format($lead['achievement'], 0, ',', '.') }}</td>
                            <td class="text-right"></td>
                            <td class="text-center"></td>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($months) + 4 }}" class="text-center">Belum ada data leads Powerhouse untuk ditampilkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
