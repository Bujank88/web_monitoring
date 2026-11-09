@extends('master')
@section('title', 'Report Ticketing')

@section('content')
<div class="container-fluid">
    <div class="card card-info">
        <div class="card-header"><h3 class="card-title">Report Ticketing</h3></div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <form method="GET" action="{{ route('ticketing.report') }}" class="row align-items-end">
                <div class="form-group col-md-2">
                    <label for="report-year">Tahun</label>
                    <select name="year" id="report-year" class="form-control">
                        @foreach($years as $year)<option value="{{ $year }}" @selected($filters['year'] === $year)>{{ $year }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="report-month">Bulan</label>
                    <select name="month" id="report-month" class="form-control">
                        @foreach(range(1, 12) as $month)<option value="{{ $month }}" @selected($filters['month'] === $month)>{{ \Carbon\Carbon::create($filters['year'], $month, 1)->locale('id')->translatedFormat('F') }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="report-from">Tanggal pengajuan dari</label>
                    <input type="date" id="report-from" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="form-group col-md-2">
                    <label for="report-to">Sampai tanggal</label>
                    <input type="date" id="report-to" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-info">Tampilkan</button>
                    <a href="{{ route('ticketing.report') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
            <p class="text-muted mb-0">Kartu ringkasan, Channel, dan Complaint Type mengikuti tahun, bulan, serta rentang tanggal terpilih ({{ $report['filtered_total'] }} tiket). Tabel ringkasan bulanan dan tren menampilkan tahun terpilih dengan batas rentang tanggal yang sama.</p>
        </div>
    </div>
    <div class="row">
        @foreach(['Total tiket' => number_format($report['filtered_total']), 'Closed' => number_format($report['closed']), 'Sedang berlangsung' => number_format($report['ongoing']), 'Resolution rate' => $report['resolution_rate'] === null ? '—' : number_format($report['resolution_rate'], 2, ',', '.').'%', 'Rata-rata penyelesaian' => $report['average_minutes'] === null ? '—' : number_format($report['average_minutes'], 2, ',', '.').' menit'] as $label => $value)
        <div class="col-md"><div class="card"><div class="card-body"><div class="text-muted">{{ $label }}</div><strong class="h4">{{ $value }}</strong></div></div></div>
        @endforeach
    </div>
    @if($report['filtered_total'] === 0)<div class="alert alert-info">Tidak ada tiket pada periode ini.</div>@endif
    @if($report['invalid_times'] > 0)
        <div class="alert alert-warning">{{ $report['invalid_times'] }} tiket Closed perlu pemeriksaan waktu penyelesaian. Durasi negatif tetap masuk rata-rata sesuai perhitungan Excel; waktu selesai yang kosong tidak masuk rata-rata.</div>
    @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title">Tren tiket bulanan {{ $filters['year'] }}</h3></div>
        <div class="card-body">
            @php $maximum = max(1, max(array_column($report['trend'], 'total'))); @endphp
            @foreach($report['trend'] as $item)
            <div class="row align-items-center mb-2">
                <div class="col-2 col-md-1">{{ $item['label'] }}</div>
                <div class="col-7 col-md-9"><div class="progress" style="height:20px;">
                    <div class="progress-bar bg-success" style="width:{{ $item['closed'] / $maximum * 100 }}%;" title="Closed: {{ $item['closed'] }}"></div>
                    <div class="progress-bar bg-warning" style="width:{{ ($item['total'] - $item['closed']) / $maximum * 100 }}%;" title="Sedang berlangsung: {{ $item['total'] - $item['closed'] }}"></div>
                </div></div>
                <div class="col-3 col-md-2 text-right">{{ $item['total'] }} tiket / {{ $item['closed'] }} Closed</div>
            </div>
            @endforeach
            <small class="text-muted">Hijau: Closed. Kuning: sedang berlangsung.</small>
        </div>
    </div>
    <ul class="nav nav-tabs mb-3" role="tablist">
        @foreach(['channel' => 'PVT Channel', 'complaint' => 'PVT Complaint Type', 'summary' => 'Ringkasan Bulanan'] as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $loop->first ? 'active' : '' }}" id="tab-{{ $key }}" data-toggle="tab" href="#report-{{ $key }}" role="tab" aria-controls="report-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</a></li>
        @endforeach
    </ul>
    <div class="tab-content">
        @foreach(['channel', 'complaint', 'summary'] as $key)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="report-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}">
            @foreach($report[$key] as $pivot)@include('ticketing.report.pivot', ['pivot' => $pivot])@endforeach
        </div>
        @endforeach
    </div>
    <p class="text-muted small">Week 1: tanggal 1–7; Week 2: 8–14; Week 3: 15–21; Week 4: 22–akhir bulan. Cepat ≤15 menit; Sedang &gt;15–60 menit; Lambat &gt;60–1.440 menit; Lebih dari 1 Hari &gt;1.440 menit. Rata-rata hanya menghitung durasi tiket Closed yang memiliki waktu selesai. Grand Total dihitung dari seluruh tiket, bukan penjumlahan kolom subtotal atau rata-rata mingguan.</p>
</div>
@endsection
