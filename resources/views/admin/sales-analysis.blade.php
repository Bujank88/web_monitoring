@extends('master')
@section('title', 'Analisa Sales')

@section('css')
<style>
    .sales-analysis { color: #243247; padding-bottom: 24px; }
    .sales-analysis .analysis-header { background: #142b48; color: #fff; border-radius: 12px; padding: 24px; }
    .sales-analysis .analysis-header p { color: #d1deef; }
    .sales-analysis .card { border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 3px 12px rgba(20,43,72,.04); }
    .sales-analysis .metric { min-height: 114px; padding: 18px; }
    .sales-analysis .metric-value { display: block; font-size: 1.4rem; font-weight: 700; margin: 6px 0; }
    .sales-analysis .metric small { color: #52647c; }
    .sales-analysis .card-header { background: #fff; border-radius: 10px 10px 0 0; padding: 18px; }
    .sales-analysis h2 { font-size: 1.05rem; font-weight: 700; margin: 0 0 6px; }
    .sales-analysis .chart-state { padding: 45px 15px; text-align: center; color: #52647c; }
    .sales-analysis .chart-scroll { max-height: 520px; overflow-y: auto; }
    .sales-analysis .chart-frame { height: 330px; position: relative; }
    .sales-analysis .chart-content[hidden], .sales-analysis [hidden] { display: none !important; }
    .sales-analysis .table { font-size: 12px; }
    .sales-analysis .table th { white-space: nowrap; }
    .sales-analysis .table td { vertical-align: middle; }
    .sales-analysis .analysis-note { color: #52647c; font-size: 12px; }
    .sales-analysis details summary { cursor: pointer; color: #245ec4; font-size: 13px; margin-top: 16px; }
    .sales-analysis .retention-table td { min-width: 100px; text-align: center; white-space: nowrap; }
    .sales-analysis .retention-table th { text-align: center; }
    .sales-analysis .retention-table td:first-child { text-align: left; font-weight: 600; }
</style>
@endsection

@section('content')
<div class="sales-analysis" id="salesAnalysis" data-url="{{ url('sales-analysis/data') }}">
    <div class="analysis-header mb-4 d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <small class="text-uppercase">Sales performance · Admin</small>
            <h1 class="h3 mt-2 font-weight-bold">Analisa Sales</h1>
            <p class="mb-0">Pantau laju topup dan kontribusi channel.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <label for="analysisMonth" class="d-block mb-1">Bulan laporan</label>
            <input type="month" id="analysisMonth" class="form-control" value="{{ now()->format('Y-m') }}" max="{{ now()->format('Y-m') }}" required>
        </div>
    </div>
    <p id="monthError" class="text-danger" role="alert" hidden>Pilih bulan yang valid, maksimal bulan berjalan.</p>
    <div class="row">
        @foreach([
            ['total', 'Topup semua channel', 'Total settlement pada periode terpilih'],
            ['growth', 'Pertumbuhan topup', 'Dibanding periode setara bulan lalu'],
            ['accounts', 'Akun melakukan topup', 'Email unik selama periode terpilih'],
        ] as [$key, $label, $description])
        <div class="col-12 col-md-4">
            <div class="card metric"><small>{{ $label }}</small><span class="metric-value" id="metric-{{ $key }}">Memuat…</span><small>{{ $description }}</small></div>
        </div>
        @endforeach
    </div>
    <p class="analysis-note mb-4">Grafik topup membandingkan periode setara bulan lalu. Tabel retention mengikuti kelompok akun setiap bulan melalui kolom N+0, N+1, dan seterusnya. Cache disegarkan setelah 5 menit; waktu data tercantum pada setiap panel.</p>
    <div class="row">
        @foreach([
            'trend' => ['Tren kumulatif topup', 'Apakah laju penjualan lebih cepat dibanding bulan lalu?'],
            'accounts-trend' => ['Tren kumulatif akun topup', 'Berapa akun unik yang sudah melakukan topup sejak awal bulan?'],
            'retention' => ['Retention akun topup sejak Januari', 'Semua channel: kelompok akun topup per bulan dan persentase yang kembali topup pada bulan berikutnya.'],
            'channels' => ['Kontribusi & pertumbuhan channel', 'Channel mana yang menyumbang penjualan dan mulai melemah?'],
        ] as $key => [$title, $description])
        <div class="col-12 {{ $key !== 'retention' ? 'col-xl-6' : '' }} d-flex">
            <section class="card w-100" data-chart="{{ $key }}" aria-labelledby="title-{{ $key }}" aria-busy="true">
                <div class="card-header">
                    <h2 id="title-{{ $key }}">{{ $title }}</h2>
                    <p class="text-muted small mb-0">{{ $description }}</p>
                    @if(in_array($key, ['trend', 'accounts-trend']))
                    <label for="channel-{{ $key }}" class="small mt-3 mb-1">Channel</label>
                    <select id="channel-{{ $key }}" class="form-control form-control-sm chart-channel">
                        <option value="all">Semua channel</option>
                        @foreach($channels as $value => $name)<option value="{{ $value }}">{{ $name }}</option>@endforeach
                    </select>
                    @endif
                </div>
                <div class="card-body">
                    <div class="chart-state" role="status" aria-live="polite">Memuat data…</div>
                    <button type="button" class="btn btn-outline-primary btn-sm chart-retry mb-3" hidden>Coba lagi</button>
                    <div class="chart-content" hidden>
                        <p class="analysis-note chart-period"></p>
                        @if($key === 'retention')
                        <div class="table-responsive retention-table"><table class="table table-sm table-bordered"><thead></thead><tbody></tbody></table></div>
                        <p class="analysis-note chart-note mt-3 mb-0"></p>
                        @else
                        <div class="chart-scroll"><div class="chart-frame"><canvas aria-label="{{ $title }}" role="img"></canvas></div></div>
                        <p class="analysis-note chart-note mt-3 mb-0"></p>
                        <details><summary>Lihat angka detail</summary><div class="table-responsive mt-2"><table class="table table-sm table-striped"><thead></thead><tbody></tbody></table></div></details>
                        @endif
                    </div>
                    <small class="text-muted chart-updated d-block mt-3"></small>
                </div>
            </section>
        </div>
        @endforeach
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="{{ route('sales-analysis.script', ['v' => filemtime(base_path('public/js/sales-analysis.js'))]) }}"></script>
@endsection
