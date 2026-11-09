@extends('master')
@section('title') Dashboard @endsection
@section('css')

<style>
    .btn-ref {
        position: fixed;
        top: 50px;
        left: 1000px;
    }

    #loading-overlay {

        position: fixed;

        top: 0;

        left: 0;

        width: 100%;

        height: 100%;

        background: rgba(0, 0, 0, 0.7);

        z-index: 9999;

        display: none;

    }

    /* Dashboard Cards */
    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2);
    }

    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }

    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }

    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }

    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }

    .border-left-danger {
        border-left: 0.25rem solid #e74a3b !important;
    }

    .border-left-secondary {
        border-left: 0.25rem solid #858796 !important;
    }

    .border-left-dark {
        border-left: 0.25rem solid #5a5c69 !important;
    }

    .bg-gradient-primary {
        background: linear-gradient(87deg, #4e73df 0, #224abe 100%);
    }

    .bg-gradient-success {
        background: linear-gradient(87deg, #1cc88a 0, #169b6b 100%);
    }

    .bg-gradient-info {
        background: linear-gradient(87deg, #36b9cc 0, #258391 100%);
    }

    .bg-gradient-danger {
        background: linear-gradient(87deg, #e74a3b 0, #be2617 100%);
    }

    .bg-gradient-warning {
        background: linear-gradient(87deg, #f6c23e 0, #dda20a 100%);
    }

    .text-xs {
        font-size: 0.75rem;
    }

    .text-gray-800 {
        color: #5a5c69 !important;
    }

    .text-gray-300 {
        color: #dddfeb !important;
    }

    .text-white-50 {
        color: rgba(255, 255, 255, 0.5) !important;
    }

    .bg-white-50 {
        background-color: rgba(255, 255, 255, 0.5) !important;
    }

    .badge-purple {
        background-color: #6f42c1;
        color: white;
    }

    .btn-group-vertical .btn {
        border-radius: 0.25rem;
        margin-bottom: 0.25rem;
    }

    /* Animation */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translate3d(0, 40px, 0);
        }
        to {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }
    }

    .card {
        animation: fadeInUp 0.6s ease-out;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .h3, .h4, .h5 {
            font-size: 1.2rem;
        }
        
        .fa-2x {
            font-size: 1.5em;
        }
        
        .btn-group-vertical .btn {
            font-size: 0.8rem;
            padding: 0.375rem 0.75rem;
        }
    }

    .table {
        background-color: #f9f9f9;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        margin-top: 15px;
        border: 0.5px solid #ccc;
        table-layout: auto;

        /* Allow dynamic column sizing */
    }

    .table th,
    .table td {
        padding: 8px !important;
        font-size: 16px;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
    }

    .table th {
        font-weight: bold;
        text-align: center;
        /* Align text to the left */
    }


    .table tbody tr {
        transition: background-color 0.3s;
    }

    .table tbody tr:hover {
        background-color: #e2e2e2;
    }

    .table tbody tr:nth-child(odd) {
        background-color: #f2f2f2;
        /* Odd row background color */
    }

    .table tbody tr:nth-child(even) {
        background-color: #ffffff;
        /* Even row background color */
    }

    @media (max-width: 768px) {

        .table th,
        .table td {
            font-size: 12px;
        }
    }
</style>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-gradient-primary">
            <div class="card-body text-white text-center">
                <h2><i class="fas fa-tachometer-alt"></i> Dashboard MyAds Monitoring</h2>
                <p class="mb-0">Selamat datang di sistem monitoring MyAds Telkomsel</p>
            </div>
        </div>
    </div>
</div>

{{-- <!-- Row 1: User Management & System -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="card bg-gradient-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="text-white mb-0">{{ $data['users']['total_users'] ?? 0 }}</h3>
                        <p class="text-white-50 mb-0">Total Users</p>
                    </div>
                    <div class="text-white">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
                <hr class="bg-white-50">
                <small class="text-white">
                    <i class="fas fa-user-shield"></i> Admin: {{ $data['users']['total_admin'] ?? 0 }} |
                    <i class="fas fa-user-cog"></i> Tsel: {{ $data['users']['total_tsel'] ?? 0 }} |
                    <i class="fas fa-user-tag"></i> Treg: {{ $data['users']['total_treg'] ?? 0 }}
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card bg-gradient-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="text-white mb-0">{{ $data['voucher']['total_voucher'] ?? 0 }}</h3>
                        <p class="text-white-50 mb-0">Total Voucher</p>
                    </div>
                    <div class="text-white">
                        <i class="fas fa-ticket-alt fa-2x"></i>
                    </div>
                </div>
                <hr class="bg-white-50">
                <small class="text-white">
                    <i class="fas fa-check-circle"></i> Diklaim: {{ $data['voucher']['total_claimed'] ?? 0 }} |
                    <i class="fas fa-clock"></i> Tersisa: {{ $data['voucher']['total_not_claimed'] ?? 0 }}
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card bg-gradient-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="text-white mb-0">{{ $data['treg_race']['total_treg'] ?? 0 }}</h3>
                        <p class="text-white-50 mb-0">Total TREG</p>
                    </div>
                    <div class="text-white">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
                <hr class="bg-white-50">
                <small class="text-white">
                    <i class="fas fa-trophy"></i> Akuisisi: {{ $data['treg_race']['total_akuisisi'] ?? 0 }} |
                    <i class="fas fa-coins"></i> Revenue: Rp {{ number_format($data['treg_race']['total_revenue'] ?? 0, 0, ',', '.') }}
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Program Marketing -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Padi UMKM</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $data['padi_umkm']['total_form'] ?? 0 }}</div>
                        <small class="text-muted">
                            <i class="fas fa-shopping-cart"></i> Topup: {{ $data['padi_umkm']['jumlah_topup'] ?? 0 }}<br>
                            <i class="fas fa-money-bill-wave"></i> Total: Rp {{ number_format($data['padi_umkm']['total_topup'] ?? 0, 0, ',', '.') }}
                        </small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-store fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card border-left-warning">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Simpati TikTok</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $data['simpati_tiktok']['total_form'] ?? 0 }}</div>
                        <small class="text-muted">
                            <i class="fas fa-shopping-cart"></i> Topup: {{ $data['simpati_tiktok']['jumlah_topup'] ?? 0 }}<br>
                            <i class="fas fa-money-bill-wave"></i> Total: Rp {{ number_format($data['simpati_tiktok']['total_topup'] ?? 0, 0, ',', '.') }}
                        </small>
                    </div>
                    <div class="col-auto">
                        <i class="fab fa-tiktok fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card border-left-success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Event Sponsorship</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $data['event_sponsorship']['total'] ?? 0 }}</div>
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt"></i> Bulan ini: {{ $data['event_sponsorship']['this_month'] ?? 0 }}
                        </small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-handshake fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card border-left-info">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Sultam Racing</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $data['sultam_racing']['total'] ?? 0 }}</div>
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt"></i> Bulan ini: {{ $data['sultam_racing']['this_month'] ?? 0 }}
                        </small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-car fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: KOL & Influencer -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="card border-left-secondary">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Creator Partner</div>
                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $data['creator_partner']['total'] ?? 0 }}</div>
                <div class="mt-2">
                    <span class="badge badge-warning mr-1">
                        <i class="fas fa-bullhorn"></i> Buzzer: {{ $data['creator_partner']['buzzer'] ?? 0 }}
                    </span>
                    <span class="badge badge-purple">
                        <i class="fas fa-crown"></i> Influencer: {{ $data['creator_partner']['influencer'] ?? 0 }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card border-left-dark">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Rekruter KOL</div>
                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $data['rekruter_kol']['total'] ?? 0 }}</div>
                <div class="mt-2">
                    <span class="badge badge-warning mr-1">
                        <i class="fas fa-bullhorn"></i> Buzzer: {{ $data['rekruter_kol']['buzzer'] ?? 0 }}
                    </span>
                    <span class="badge badge-purple">
                        <i class="fas fa-crown"></i> Influencer: {{ $data['rekruter_kol']['influencer'] ?? 0 }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6">
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Area Marcom</div>
                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $data['area_marcom']['total_areas'] ?? 0 }}</div>
                <small class="text-muted">
                    <i class="fas fa-map-marked-alt"></i> Rata-rata KOL per Area: {{ $data['area_marcom']['avg_kol_per_area'] ?? 0 }}
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Referral Champion -->
<div class="row mb-4">
    <div class="col-lg-6 col-md-12">
        <div class="card border-left-success">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Referral Champion</div>
                <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $data['referral_champion']['total'] ?? 0 }}</div>
                <small class="text-muted">
                    <i class="fas fa-calendar-alt"></i> Bulan ini: {{ $data['referral_champion']['this_month'] ?? 0 }}
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 col-md-12">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">Quick Actions</h5>
                <div class="btn-group-vertical btn-group-sm">
                    <a href="{{ route('admin.monitoring.padi_umkm') }}" class="btn btn-outline-primary mb-1">
                        <i class="fas fa-store"></i> Monitoring Padi UMKM
                    </a>
                    <a href="{{ route('admin.monitoring.creator_partner') }}" class="btn btn-outline-warning mb-1">
                        <i class="fas fa-users"></i> Monitoring Creator Partner
                    </a>
                    <a href="{{ route('admin.voucher') }}" class="btn btn-outline-info mb-1">
                        <i class="fas fa-ticket-alt"></i> Manajemen Voucher
                    </a>
                    <a href="{{ route('users.page') }}" class="btn btn-outline-success">
                        <i class="fas fa-users-cog"></i> Manajemen Users
                    </a>
                </div>
            </div>
        </div>
    </div>
</div> --}}

<!-- Last Updated Info -->
<div class="row">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body text-center">
                <small class="text-muted">
                    <i class="fas fa-clock"></i> Last updated: {{ now()->format('d F Y, H:i:s') }} WIB
                </small>
            </div>
        </div>
    </div>
</div>

@endsection