@extends('master')
@section('title') Tips Sales @endsection

@section('content')
@php
    $categories = [
        ['name' => 'Jasa', 'url' => 'https://canva.link/ix624fxqvuw6gto', 'icon' => 'fa-briefcase', 'color' => '#0d6efd', 'tips' => 'Waktu terbaik untuk posting adalah 1-2 jam sebelum peak time. Untuk bisnis seperti transportasi, misalnya, konten akan lebih relevan jika dipublikasikan menjelang jam pulang sekolah atau jam pulang kerja.'],
        ['name' => 'FNB', 'url' => 'https://canva.link/96fao4osr86f3t9', 'icon' => 'fa-utensils', 'color' => '#fd7e14', 'tips' => 'Konten sebaiknya dipublikasikan sekitar 2 jam sebelum jam makan agar lebih efektif menarik perhatian calon pelanggan.'],
        ['name' => 'Beauty and Health', 'url' => 'https://canva.link/bcjtdvk5jg4323w', 'icon' => 'fa-heart-pulse', 'color' => '#e83e8c', 'tips' => 'Jam yang disarankan untuk posting adalah pagi hari dan sore hari, saat audiens cenderung lebih siap menerima konten seputar perawatan dan kesehatan.'],
        ['name' => 'Edukasi', 'url' => 'https://canva.link/71b7px0sklbbiju', 'icon' => 'fa-graduation-cap', 'color' => '#6610f2', 'tips' => 'Konten edukasi cocok dipublikasikan mulai hari Jumat hingga akhir pekan. Alternatif waktu yang juga cukup efektif adalah sore hari, sekitar pukul 15.00-17.00.'],
        ['name' => 'Crafts', 'url' => 'https://canva.link/o1zbmrkb15g2mxg', 'icon' => 'fa-scissors', 'color' => '#20c997', 'tips' => 'Waktu posting yang disarankan adalah menjelang akhir pekan atau sebelum periode long weekend, saat minat audiens terhadap produk kreatif biasanya meningkat.'],
        ['name' => 'Fashion', 'url' => 'https://canva.link/62ad1fi8fhbxglv', 'icon' => 'fa-shirt', 'color' => '#6f42c1', 'tips' => 'Konten fashion cenderung lebih menarik jika dipublikasikan saat akhir pekan atau menjelang momen hari raya, ketika kebutuhan dan minat belanja biasanya meningkat.'],
        ['name' => 'Retail and Reseller', 'url' => 'https://canva.link/v6zox64dmieg67c', 'icon' => 'fa-store', 'color' => '#198754', 'tips' => 'Kategori ini cukup fleksibel untuk dipublikasikan setiap hari, terutama pada jam-jam aktif ketika audiens lebih sering membuka media sosial.'],
        ['name' => 'Hari Tematik', 'url' => 'https://canva.link/k232jbcdzbh5ohn', 'icon' => 'fa-calendar-days', 'color' => '#dc3545', 'tips' => 'Konten bertema khusus akan lebih optimal jika dipublikasikan mendekati atau tepat pada momentum hari besar yang relevan.'],
    ];
@endphp

@section('css')
<style>
    .tips-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .tips-hero {
        background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
        color: #fff;
        border-radius: 18px;
    }

    .category-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        height: 100%;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .category-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.10);
    }

    .category-icon {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: #fff;
        margin-bottom: 16px;
    }

    .btn-template-link {
        border-radius: 999px;
        font-weight: 600;
        padding: 0.65rem 1rem;
    }

    .tips-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 14px;
        min-height: 88px;
        text-align: left;
    }
</style>
@endsection

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card tips-card">
                <div class="card-body tips-hero p-4 p-md-5">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-2"><i class="fas fa-lightbulb mr-2"></i>Tips Sales</h3>
                            <p class="mb-0">Pilih kategori bisnis lalu buka template image yang sesuai untuk kebutuhan promosi.</p>
                        </div>
                        <div class="col-md-4 text-md-right mt-3 mt-md-0">
                            <i class="fas fa-images" style="font-size: 64px; opacity: 0.9;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($categories as $category)
        <div class="col-md-6 col-xl-3 mb-4">
            <div class="category-card p-4 text-center">
                <div class="category-icon" style="background: {{ $category['color'] }};">
                    <i class="fas {{ $category['icon'] }}"></i>
                </div>
                <h5 class="mb-2">{{ $category['name'] }}</h5>
                <div class="tips-box mb-4">
                    <div class="small font-weight-bold mb-1" style="color: {{ $category['color'] }};">Tips Posting</div>
                    <div class="text-muted small mb-0">{{ $category['tips'] }}</div>
                </div>
                <a href="{{ $category['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-template-link">
                    <i class="fas fa-up-right-from-square mr-2"></i>Link Template Image
                </a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center text-muted">
                    <small>Menu ini tersedia untuk Admin, Canvasser, dan PowerHouse.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
