@extends('master')
@section('title') Riwayat Presensi @endsection
@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<style>
    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
    }

    .filter-section {
        background-color: #fff;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .filter-group {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }

    .form-group-inline {
        flex: 1;
        min-width: 150px;
    }

    .form-group-inline label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: block;
        color: #333;
    }

    .form-group-inline input {
        width: 100%;
        padding: 0.6rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.95rem;
    }

    .btn-filter {
        padding: 0.6rem 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.active a {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover a {
        background: #667eea !important;
        color: white !important;
    }

    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 0.5rem;
    }

    table.dataTable thead th {
        background: #f8f9fa;
        color: #333;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }

    table.dataTable tbody tr:hover {
        background-color: #f8f9fa;
    }

    .status-badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-hadir {
        background-color: #d4edda;
        color: #155724;
    }

    .status-terlambat {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-izin {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .status-sakit {
        background-color: #f8d7da;
        color: #721c24;
    }

    .status-pulang-awal {
        background-color: #cce5ff;
        color: #004085;
    }

    .status-tepat-waktu {
        background-color: #d4edda;
        color: #155724;
    }

    .foto-cell {
        text-align: center;
    }

    .foto-cell img {
        max-width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .foto-cell img:hover {
        transform: scale(1.2);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .foto-cell a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }

    .foto-cell a:hover {
        text-decoration: underline;
    }

    .durasi-terlambat {
        font-weight: 600;
        color: #dc3545;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #999;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    .modal-foto {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal-foto.active {
        display: flex;
    }

    .modal-foto-content {
        max-width: 500px;
        width: 90%;
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        position: relative;
    }

    .modal-foto-content img {
        width: 100%;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .close-modal {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 1.5rem;
        cursor: pointer;
        background: none;
        border: none;
        color: #666;
    }

    @media (max-width: 768px) {
        .filter-group {
            flex-direction: column;
        }

        .form-group-inline,
        .btn-filter {
            width: 100%;
        }

        .dataTables_wrapper {
            overflow-x: auto;
        }
    }
</style>
@endsection

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Riwayat Presensi</h1>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-group">
                <div class="form-group-inline">
                    <label>Tanggal Dari</label>
                    <input type="date" name="dateFrom" value="{{ $dateFrom }}" required>
                </div>
                <div class="form-group-inline">
                    <label>Tanggal Sampai</label>
                    <input type="date" name="dateTo" value="{{ $dateTo }}" required>
                </div>
                <button type="submit" class="btn-filter">🔍 Filter</button>
            </form>
        </div>

        <!-- Data Table -->
        @if ($presensi->count() > 0)
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="presensiTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Jam Masuk</th>
                                <th>Status</th>
                                <th>Durasi Terlambat</th>
                                <th>Foto Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Foto Pulang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($presensi as $item)
                            <tr>
                                <td><strong>{{ $item->tanggal->translatedFormat('d M Y') }}</strong></td>
                                <td>{{ $item->user->name }}</td>
                                <td>
                                    @if ($item->jam_datang)
                                    {{ $item->jam_datang->format('H:i') }}
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->status_datang)
                                    <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $item->status_datang)) }}">
                                        {{ $item->status_datang }}
                                    </span>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->status_datang === 'Terlambat' && $item->jam_datang)
                                    @php
                                    $jamKerjaAwal = \Carbon\Carbon::createFromFormat('H:i', '08:00');
                                    $selisih = $item->jam_datang->diffInMinutes($jamKerjaAwal);
                                    $jam = intdiv($selisih, 60);
                                    $menit = $selisih % 60;
                                    $durasiFormatted = ($jam > 0 ? $jam . ' jam ' : '') . ($menit > 0 ? $menit . ' menit' : '');
                                    @endphp
                                    <span class="durasi-terlambat">{{ trim($durasiFormatted) }}</span>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td class="foto-cell">
                                    @if ($item->foto_datang)
                                    <a href="#" onclick="openModalFoto('{{ \Illuminate\Support\Facades\Storage::url($item->foto_datang) }}', event)">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item->foto_datang) }}" alt="Foto Masuk" title="Klik untuk preview">
                                    </a>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->jam_pulang)
                                    {{ $item->jam_pulang->format('H:i') }}
                                    @else
                                    -
                                    @endif
                                </td>
                                <td class="foto-cell">
                                    @if ($item->foto_pulang)
                                    <a href="#" onclick="openModalFoto('{{ \Illuminate\Support\Facades\Storage::url($item->foto_pulang) }}', event)">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($item->foto_pulang) }}" alt="Foto Keluar" title="Klik untuk preview">
                                    </a>
                                    @else
                                    -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $presensi->render() }}
                </div>
            </div>
        </div>
        @else
        <div class="card">
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p style="font-size: 1.1rem;">Tidak ada data presensi</p>
                <p style="font-size: 0.9rem; color: #ccc;">untuk periode {{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y') }}</p>
            </div>
        </div>
        @endif
    </div>
</div>


<!-- Modal Preview Foto -->
<div class="modal-foto" id="modalFoto">
    <div class="modal-foto-content">
        <button class="close-modal" onclick="closeModalFoto()">&times;</button>
        <img id="modalFotoImg" src="" alt="Preview Foto">
        <p id="modalFotoCaption"></p>
    </div>
</div>

@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#presensiTable').DataTable({
            "paging": true,
            "pageLength": 10,
            "searching": true,
            "ordering": true,
            "info": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
            }
        });
    });

    // Modal Foto Functions
    function openModalFoto(url, event) {
        event.preventDefault();
        document.getElementById('modalFotoImg').src = url;
        document.getElementById('modalFoto').classList.add('active');
    }

    function closeModalFoto() {
        document.getElementById('modalFoto').classList.remove('active');
    }

    // Close modal on outside click
    document.getElementById('modalFoto').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalFoto();
        }
    });

    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModalFoto();
        }
    });
</script>
@endsection
