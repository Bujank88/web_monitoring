@extends('master')
@section('title') MPCC Pilot City Report @endsection
@section('css')
<style>
    .pilot-card {
        border: 1px solid #dbe4ff;
        border-radius: 16px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .pilot-card .card-header {
        background: linear-gradient(135deg, #123c69 0%, #1d6fa3 100%);
        color: #fff;
        border-bottom: none;
    }
    .pilot-table {
        margin-bottom: 0;
        min-width: 1100px;
    }
    .pilot-table th,
    .pilot-table td {
        border: 1px solid #d7deea;
        padding: 10px 12px;
        vertical-align: middle;
    }
    .pilot-table thead th {
        background: #eef4ff;
        text-align: center;
        font-weight: 700;
    }
    .pilot-table .row-label {
        background: #f8fbff;
        font-weight: 700;
        min-width: 240px;
        text-align: left;
    }
    .pilot-table .section-row td {
        background: #16324f;
        color: #fff;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .pilot-table .value-cell {
        text-align: center;
        white-space: nowrap;
    }
    .pilot-table .unavailable {
        color: #8a94a6;
        font-style: italic;
    }
    .note-list {
        margin: 0;
        padding-left: 1rem;
    }
    .note-list li + li {
        margin-top: 0.35rem;
    }
</style>
@endsection
@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">MPCC Pilot City Report</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card pilot-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filter Bulan</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('mpcc.report.pilot-city') }}">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-3">
                            <label for="month">Bulan</label>
                            <select name="month" id="month" class="form-control">
                                @foreach($availableMonths as $month)
                                    <option value="{{ $month['value'] }}" {{ $selectedMonth === $month['value'] ? 'selected' : '' }}>
                                        {{ $month['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-2"></i>Tampilkan Report
                            </button>
                        </div>
                        <div class="col-md-5 mb-3 text-md-right">
                            <span class="badge badge-info p-2">Periode: {{ $periodLabel }}</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card pilot-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Matrix Pilot City MPCC</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table pilot-table">
                        <thead>
                            <tr>
                                <th class="row-label">Area VP</th>
                                @foreach($reportConfig as $group)
                                    <th colspan="{{ count($group['cities']) }}">{{ $group['vp'] }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="row-label">Area</th>
                                @foreach($reportConfig as $group)
                                    <th colspan="{{ count($group['cities']) }}">{{ $group['area'] }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                <th class="row-label">Pilot cities</th>
                                @foreach($reportConfig as $group)
                                    @foreach($group['cities'] as $city)
                                        <th>{{ $city['label'] }}</th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $lastSection = null;
                            @endphp
                            @foreach($reportRows as $row)
                                @if($row['section'] !== $lastSection && $row['section'] !== 'basic')
                                    <tr class="section-row">
                                        <td colspan="{{ 1 + collect($reportConfig)->sum(fn($group) => count($group['cities'])) }}">
                                            {{ ucfirst($row['section']) }}
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="row-label">{{ $row['label'] }}</td>
                                    @foreach($row['values'] as $value)
                                        <td class="value-cell {{ $value === '-' ? 'unavailable' : '' }}">{{ $value }}</td>
                                    @endforeach
                                </tr>
                                @php
                                    $lastSection = $row['section'];
                                @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
