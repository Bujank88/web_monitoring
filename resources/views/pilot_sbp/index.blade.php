@extends('master')

@section('title', $pageTitle ?? 'Pilot SBP to SME')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ $pageTitle ?? 'Pilot SBP to SME' }}</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-0">
            Modul <strong>Pilot SBP to SME</strong> sudah dipisah ke controller dan view sendiri. Silakan lanjutkan lewat submenu yang tersedia.
        </div>
    </div>
</div>
@endsection
