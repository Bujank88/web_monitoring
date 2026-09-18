@extends('master')

@section('title', 'Report Performance')

@section('css')
<style>
    .performance-filter-wrap {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 12px 14px;
    }

    .performance-filter-label {
        font-size: 12px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 4px;
        display: block;
    }

    .performance-filter-row {
        display: flex;
        gap: 10px;
        align-items: end;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .performance-month-control {
        min-width: 220px;
    }

    .performance-month-control .select2-container {
        width: 100% !important;
    }

    .performance-month-control .select2-selection {
        min-height: 38px !important;
        border: 1px solid #ced4da !important;
        border-radius: .25rem !important;
    }

    .performance-month-control .select2-selection__rendered {
        line-height: 36px !important;
    }

    .performance-month-control .select2-selection__arrow {
        height: 36px !important;
    }

    @media (max-width: 768px) {
        .performance-filter-row {
            justify-content: stretch;
        }

        .performance-month-control,
        .performance-filter-row .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')

<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="performance-filter-wrap">
            <div class="performance-filter-row">
                <form id="filterForm" method="GET" action="{{ route('mitra-sbp') }}">
                    
                    <div class="performance-month-control">
                        <label for="month" class="performance-filter-label">Pilih Bulan</label>
                        <select id="month" name="month" class="form-control">
                            
                            @foreach ($months as $m)
                            <option value="{{ $m['value'] }}" {{ $m['selected'] ? 'selected' : '' }}>
                                {{ $m['label'] }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </form>
                <button type="button" id="btnExportAllExcel" class="btn btn-success btn-sm" title="Download Excel All" style="white-space: nowrap;">
                    <i class="fas fa-file-excel mr-2"></i> Download Excel All
                </button>
            </div>
        </div>
    </div>
</div>

@php
// Parse selected month for display
[$selectedYear, $selectedMonth] = explode('-', $month);
$monthDisplay = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->translatedFormat('F Y');
@endphp

{{-- SUMMARY CARDS --}}
<div class="row mb-2">
    {{-- Mitra SBP Card --}}
    @php
    $mitraTotalTarget = $data_mitra_sbp->sum('target_amount');
    $mitraTotalRealisasi = $data_mitra_sbp->sum('mitra_sbp');
    $mitraPct = $mitraTotalTarget > 0 ? round(($mitraTotalRealisasi / $mitraTotalTarget) * 100, 2) : 0;
    $mitraStatus = $mitraPct >= 100 ? 'success' : ($mitraPct >= 75 ? 'warning' : 'danger');
    @endphp
    <div class="col-md-4 mb-2">
        <div class="small-box" style="background: linear-gradient(135deg, #1cc88a 0%, #169b6b 100%); position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; top: -10px; font-size: 80px; opacity: 0.1; color: white;">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="inner" style="padding: 20px 15px; position: relative; z-index: 1;">
                <small style="color: rgba(255,255,255,0.8); display: block; font-size: 11px; margin-bottom: 8px;">{{ $monthDisplay }}</small>
                <h5 style="color: white; font-weight: 700; margin-bottom: 15px;">
                    <i class="fas fa-handshake" style="margin-right: 8px;"></i>Mitra SBP
                </h5>
                <h6 style="color: rgba(255,255,255,0.9); margin-bottom: 12px;">
                    <strong>Target:</strong> <span style="color: white; font-weight: 700; font-size: 16px;">Rp {{ number_format($mitraTotalTarget) }}</span>
                </h6>
                <h6 style="color: rgba(255,255,255,0.9); margin-bottom: 12px;">
                    <strong>Realisasi:</strong> <span style="color: white; font-weight: 700; font-size: 16px;">Rp {{ number_format($mitraTotalRealisasi) }}</span>
                </h6>
                <h6 style="color: rgba(255,255,255,0.9); margin-bottom: 8px;">
                    <strong>Achievement:</strong> <span class="badge bg-{{ $mitraStatus }}" style="font-size: 12px; padding: 6px 12px;">{{ $mitraPct }}%</span>
                </h6>
            </div>
        </div>
    </div>

    {{-- Agency Card --}}
    @php
    $agencyTotalTarget = $data_agency->sum('target_amount');
    $agencyTotalRealisasi = $data_agency->sum('agency');
    $agencyPct = $agencyTotalTarget > 0 ? round(($agencyTotalRealisasi / $agencyTotalTarget) * 100, 2) : 0;
    $agencyStatus = $agencyPct >= 100 ? 'success' : ($agencyPct >= 75 ? 'warning' : 'danger');
    @endphp
    <div class="col-md-4 mb-2">
        <div class="small-box" style="background: linear-gradient(135deg, #36b9cc 0%, #258391 100%); position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; top: -10px; font-size: 80px; opacity: 0.1; color: white;">
                <i class="fas fa-building"></i>
            </div>
            <div class="inner" style="padding: 20px 15px; position: relative; z-index: 1;">
                <small style="color: rgba(255,255,255,0.8); display: block; font-size: 11px; margin-bottom: 8px;">{{ $monthDisplay }}</small>
                <h5 style="color: white; font-weight: 700; margin-bottom: 15px;">
                    <i class="fas fa-building" style="margin-right: 8px;"></i>Agency Indihome
                </h5>
                <h6 style="color: rgba(255,255,255,0.9); margin-bottom: 12px;">
                    <strong>Target:</strong> <span style="color: white; font-weight: 700; font-size: 16px;">Rp {{ number_format($agencyTotalTarget) }}</span>
                </h6>
                <h6 style="color: rgba(255,255,255,0.9); margin-bottom: 12px;">
                    <strong>Realisasi:</strong> <span style="color: white; font-weight: 700; font-size: 16px;">Rp {{ number_format($agencyTotalRealisasi) }}</span>
                </h6>
                <h6 style="color: rgba(255,255,255,0.9); margin-bottom: 8px;">
                    <strong>Achievement:</strong> <span class="badge bg-{{ $agencyStatus }}" style="font-size: 12px; padding: 6px 12px;">{{ $agencyPct }}%</span>
                </h6>
            </div>
        </div>
    </div>

    {{-- Internal Card --}}
    @php
    $internalTotalTarget = $data_internal->sum('target_amount');
    $internalTotalRealisasi = $data_internal->sum('internal');
    $internalPct = $internalTotalTarget > 0 ? round(($internalTotalRealisasi / $internalTotalTarget) * 100, 2) : 0;
    $internalStatus = $internalPct >= 100 ? 'success' : ($internalPct >= 75 ? 'warning' : 'danger');
    @endphp
    <div class="col-md-4 mb-2">
        <div class="small-box" style="background: linear-gradient(135deg, #fea136 0%, #be8c17 100%); position: relative; overflow: hidden;">
            <div style="position: absolute; right: -10px; top: -10px; font-size: 80px; opacity: 0.1; color: white;">
                <i class="fas fa-home"></i>
            </div>
            <div class="inner" style="padding: 20px 15px; position: relative; z-index: 1;">
                <small style="color: rgba(255,255,255,0.8); display: block; font-size: 11px; margin-bottom: 8px;">{{ $monthDisplay }}</small>
                <h5 style="color: white; font-weight: 700; margin-bottom: 15px;">
                    <i class="fas fa-home" style="margin-right: 8px;"></i>Internal Indihome
                </h5>
                <h6 style="color: rgba(255,255,255,0.9); margin-bottom: 12px;">
                    <strong>Target:</strong> <span style="color: white; font-weight: 700; font-size: 16px;">Rp {{ number_format($internalTotalTarget) }}</span>
                </h6>
                <h6 style="color: rgba(255,255,255,0.9); margin-bottom: 12px;">
                    <strong>Realisasi:</strong> <span style="color: white; font-weight: 700; font-size: 16px;">Rp {{ number_format($internalTotalRealisasi) }}</span>
                </h6>
                <h6 style="color: rgba(255,255,255,0.9); margin-bottom: 8px;">
                    <strong>Achievement:</strong> <span class="badge bg-{{ $internalStatus }}" style="font-size: 12px; padding: 6px 12px;">{{ $internalPct }}%</span>
                </h6>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="mitraSBPTableCard">
            <div class="card-header bg-gradient-success text-white d-flex justify-content-between align-items-center"  style="padding: 1rem; border-radius: 0.35rem 0.35rem 0 0;">
                <h4 class="mb-0"><i class="fas fa-table"></i> Report Mitra SBP</h4>
                <div class="d-flex gap-2">
                    <button type="button" id="btnSaveMitraSBP" class="btn btn-light btn-sm" title="Save as Image" style="padding: 6px 12px; white-space: nowrap; margin-right:5px;">
                        <i class="fas fa-image mr-2"></i> Save Image
                    </button>
                    <button type="button" id="btnExportMitraSBPExcel" class="btn btn-light btn-sm" title="Download Excel" style="padding: 6px 12px; white-space: nowrap; margin-right:5px;">
                        <i class="fas fa-file-excel mr-2"></i> Download Excel
                    </button>
                </div>
            </div>
            <div class="card-body" id="captureMitraSBPTable">
                <small style="color: #666; display: block; margin-bottom: 15px;"><strong>Data Bulan:</strong> {{ $monthDisplay }}</small>
                <table class="table table-sm table-bordered table-hover align-middle">
                    <thead>
                        <tr class="text-center" style="background-color:#e3f2fd;">
                            <th>Area / Region</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>Achievement (%)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($grouped_mitra_sbp as $area => $rows)

                        @php
                        $areaTarget = $rows->sum('target_amount');
                        $areaMitra = $rows->sum('mitra_sbp');
                        $areaPct = $areaTarget > 0 ? round(($areaMitra / $areaTarget) * 100, 2) : 0;
                        @endphp

                        {{-- HEADER AREA --}}
                        <tr style="background: linear-gradient(90deg,#d1ecf1,#f8f9fa); font-weight:bold;">
                            <td class="text-start">
                                <i class="fas fa-layer-group text-info me-1"></i>
                                {{ $area }}
                            </td>
                            <td class="text-center">Rp {{ number_format($areaTarget) }}</td>
                            <td class="text-center">Rp {{ number_format($areaMitra) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $areaPct >= 100 ? 'bg-success' : ($areaPct >= 75 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                    {{ $areaPct }}%
                                </span>
                            </td>
                        </tr>

                        {{-- DETAIL REGION --}}
                        @foreach($rows as $row)
                        @php
                        $pct = $row->target_amount > 0
                        ? round(($row->mitra_sbp / $row->target_amount) * 100, 2)
                        : 0;
                        @endphp
                        <tr>
                            <td class="text-start ps-4">
                                <i class="fas fa-map-marker-alt text-secondary me-1"></i>
                                {{ $row->region_name }}
                            </td>
                            <td class="text-center">Rp {{ number_format($row->target_amount) }}</td>
                            <td class="text-center">Rp {{ number_format($row->mitra_sbp) }}</td>
                            <td class="text-center">
                                <span class="text-{{ $pct >= 100 ? 'success' : ($pct >= 75 ? 'warning' : 'danger') }} fw-bold">
                                    {{ $pct }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach

                        @endforeach
                    </tbody>

                    {{-- TOTAL --}}
                    @php
                    $totalTarget = $data_mitra_sbp->sum('target_amount');
                    $totalMitra = $data_mitra_sbp->sum('mitra_sbp');
                    $totalPct = $totalTarget > 0 ? round(($totalMitra / $totalTarget) * 100, 2) : 0;
                    @endphp

                    <tfoot>
                        <tr style="background-color:#fff3cd; font-weight:bold; border-top:2px solid #ffc107;">
                            <td class="text-start">
                                <i class="fas fa-calculator me-1"></i> TOTAL
                            </td>
                            <td class="text-center">Rp {{ number_format($totalTarget) }}</td>
                            <td class="text-center">Rp {{ number_format($totalMitra) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $totalPct >= 100 ? 'success' : ($totalPct >= 75 ? 'warning' : 'danger') }}">
                                    {{ $totalPct }}%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>
</div>


<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="agencyTableCard">
            <div class="card-header bg-gradient-info text-white d-flex justify-content-between align-items-center"  style="padding: 1rem; border-radius: 0.35rem 0.35rem 0 0;">
                <h4 class="mb-0"><i class="fas fa-table"></i> Report Agency Indihome</h4>
                <div class="d-flex gap-2">
                    <button type="button" id="btnSaveAgency" class="btn btn-light btn-sm" title="Save as Image" style="padding: 6px 12px; white-space: nowrap; margin-right:5px;">
                        <i class="fas fa-image mr-2"></i> Save Image
                    </button>
                    <button type="button" id="btnExportAgencyExcel" class="btn btn-light btn-sm" title="Download Excel" style="padding: 6px 12px; white-space: nowrap; margin-right:5px;">
                        <i class="fas fa-file-excel mr-2"></i> Download Excel
                    </button>
                </div>
            </div>
            <div class="card-body" id="captureAgencyTable">
                <small style="color: #666; display: block; margin-bottom: 15px;"><strong>Data Bulan:</strong> {{ $monthDisplay }}</small>
                <table class="table table-sm table-bordered table-hover align-middle">
                    <thead>
                        <tr class="text-center" style="background-color:#e3f2fd;">
                            <th>Area / Region</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>Achievement (%)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($grouped_agency as $area => $rows)

                        @php
                        $areaTarget = $rows->sum('target_amount');
                        $areaMitra = $rows->sum('agency');
                        $areaPct = $areaTarget > 0 ? round(($areaMitra / $areaTarget) * 100, 2) : 0;
                        @endphp

                        {{-- HEADER AREA --}}
                        <tr style="background: linear-gradient(90deg,#d1ecf1,#f8f9fa); font-weight:bold;">
                            <td class="text-start">
                                <i class="fas fa-layer-group text-info me-1"></i>
                                {{ $area }}
                            </td>
                            <td class="text-center">Rp {{ number_format($areaTarget) }}</td>
                            <td class="text-center">Rp {{ number_format($areaMitra) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $areaPct >= 100 ? 'bg-success' : ($areaPct >= 75 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                    {{ $areaPct }}%
                                </span>
                            </td>
                        </tr>

                        {{-- DETAIL REGION --}}
                        @foreach($rows as $row)
                        @php
                        $pct = $row->target_amount > 0
                        ? round(($row->agency / $row->target_amount) * 100, 2)
                        : 0;
                        @endphp
                        <tr>
                            <td class="text-start ps-4">
                                <i class="fas fa-map-marker-alt text-secondary me-1"></i>
                                {{ $row->region_name }}
                            </td>
                            <td class="text-center">Rp {{ number_format($row->target_amount) }}</td>
                            <td class="text-center">Rp {{ number_format($row->agency) }}</td>
                            <td class="text-center">
                                <span class="text-{{ $pct >= 100 ? 'success' : ($pct >= 75 ? 'warning' : 'danger') }} fw-bold">
                                    {{ $pct }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach

                        @endforeach
                    </tbody>

                    {{-- TOTAL --}}
                    @php
                    $totalTarget = $data_agency->sum('target_amount');
                    $totalMitra = $data_agency->sum('agency');
                    $totalPct = $totalTarget > 0 ? round(($totalMitra / $totalTarget) * 100, 2) : 0;
                    @endphp

                    <tfoot>
                        <tr style="background-color:#fff3cd; font-weight:bold; border-top:2px solid #ffc107;">
                            <td class="text-start">
                                <i class="fas fa-calculator me-1"></i> TOTAL
                            </td>
                            <td class="text-center">Rp {{ number_format($totalTarget) }}</td>
                            <td class="text-center">Rp {{ number_format($totalMitra) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $totalPct >= 100 ? 'success' : ($totalPct >= 75 ? 'warning' : 'danger') }}">
                                    {{ $totalPct }}%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="internalTableCard">
            <div class="card-header bg-gradient-danger text-white d-flex justify-content-between align-items-center"  style="padding: 1rem; border-radius: 0.35rem 0.35rem 0 0;">
                <h4 class="mb-0"><i class="fas fa-table"></i> Report Internal Indihome</h4>
                <div class="d-flex gap-2">
                    <button type="button" id="btnSaveInternal" class="btn btn-light btn-sm" title="Save as Image" style="padding: 6px 12px; white-space: nowrap; margin-right:5px;">
                        <i class="fas fa-image mr-2"></i> Save Image
                    </button>
                    <button type="button" id="btnExportInternalExcel" class="btn btn-light btn-sm" title="Download Excel" style="padding: 6px 12px; white-space: nowrap; margin-right:5px;">
                        <i class="fas fa-file-excel mr-2"></i> Download Excel
                    </button>
                </div>
            </div>
            <div class="card-body" id="captureInternalTable">
                <small style="color: #666; display: block; margin-bottom: 15px;"><strong>Data Bulan:</strong> {{ $monthDisplay }}</small>
                <table class="table table-sm table-bordered table-hover align-middle">
                    <thead>
                        <tr class="text-center" style="background-color:#e3f2fd;">
                            <th>Area / Region</th>
                            <th>Target</th>
                            <th>Realisasi</th>
                            <th>Achievement (%)</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($grouped_internal as $area => $rows)

                        @php
                        $areaTarget = $rows->sum('target_amount');
                        $areaMitra = $rows->sum('internal');
                        $areaPct = $areaTarget > 0 ? round(($areaMitra / $areaTarget) * 100, 2) : 0;
                        @endphp

                        {{-- HEADER AREA --}}
                        <tr style="background: linear-gradient(90deg,#d1ecf1,#f8f9fa); font-weight:bold;">
                            <td class="text-start">
                                <i class="fas fa-layer-group text-info me-1"></i>
                                {{ $area }}
                            </td>
                            <td class="text-center">Rp {{ number_format($areaTarget) }}</td>
                            <td class="text-center">Rp {{ number_format($areaMitra) }}</td>
                            <td class="text-center">
                                <span class="badge {{ $areaPct >= 100 ? 'bg-success' : ($areaPct >= 75 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                    {{ $areaPct }}%
                                </span>
                            </td>
                        </tr>

                        {{-- DETAIL REGION --}}
                        @foreach($rows as $row)
                        @php
                        $pct = $row->target_amount > 0
                        ? round(($row->internal / $row->target_amount) * 100, 2)
                        : 0;
                        @endphp
                        <tr>
                            <td class="text-start ps-4">
                                <i class="fas fa-map-marker-alt text-secondary me-1"></i>
                                {{ $row->region_name }}
                            </td>
                            <td class="text-center">Rp {{ number_format($row->target_amount) }}</td>
                            <td class="text-center">Rp {{ number_format($row->internal) }}</td>
                            <td class="text-center">
                                <span class="text-{{ $pct >= 100 ? 'success' : ($pct >= 75 ? 'warning' : 'danger') }} fw-bold">
                                    {{ $pct }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach

                        @endforeach
                    </tbody>

                    {{-- TOTAL --}}
                    @php
                    $totalTarget = $data_internal->sum('target_amount');
                    $totalMitra = $data_internal->sum('internal');
                    $totalPct = $totalTarget > 0 ? round(($totalMitra / $totalTarget) * 100, 2) : 0;
                    @endphp

                    <tfoot>
                        <tr style="background-color:#fff3cd; font-weight:bold; border-top:2px solid #ffc107;">
                            <td class="text-start">
                                <i class="fas fa-calculator me-1"></i> TOTAL
                            </td>
                            <td class="text-center">Rp {{ number_format($totalTarget) }}</td>
                            <td class="text-center">Rp {{ number_format($totalMitra) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $totalPct >= 100 ? 'success' : ($totalPct >= 75 ? 'warning' : 'danger') }}">
                                    {{ $totalPct }}%
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
</div>




@endsection
@section('js')
{{-- ================= JS ================= --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#month').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Handle month change
        $('#month').on('change', function() {
            $('#filterForm').submit();
        });
    });

    // Handle Save Image Button
    document.getElementById('btnSaveMitraSBP').addEventListener('click', function() {
        html2canvas(document.getElementById('captureMitraSBPTable'), {
                scale: 2,
                allowTaint: true,
                useCORS: true
            })
            .then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = 'mitra_sbp_' + new Date().getTime() + '.png';
                link.click();
            })
            .catch(err => {
                console.error('Error capturing table:', err);
                alert('Gagal menyimpan gambar. Silakan coba lagi.');
            });
    });
    document.getElementById('btnExportMitraSBPExcel').addEventListener('click', function() {
        let monthValue = $('#month').val();
        window.location = "{{ route('export.mitra-sbp') }}?month=" + monthValue;
    });
    // Handle Save Image Button
    document.getElementById('btnSaveAgency').addEventListener('click', function() {
        html2canvas(document.getElementById('captureAgencyTable'), {
                scale: 2,
                allowTaint: true,
                useCORS: true
            })
            .then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = 'agency_' + new Date().getTime() + '.png';
                link.click();
            })
            .catch(err => {
                console.error('Error capturing table:', err);
                alert('Gagal menyimpan gambar. Silakan coba lagi.');
            });
    });
    document.getElementById('btnExportAgencyExcel').addEventListener('click', function() {
        let monthValue = $('#month').val();
        window.location = "{{ route('export.agency') }}?month=" + monthValue;
    });
    // Handle Save Image Button
    document.getElementById('btnSaveInternal').addEventListener('click', function() {
        html2canvas(document.getElementById('captureInternalTable'), {
                scale: 2,
                allowTaint: true,
                useCORS: true
            })
            .then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = 'internal_' + new Date().getTime() + '.png';
                link.click();
            })
            .catch(err => {
                console.error('Error capturing table:', err);
                alert('Gagal menyimpan gambar. Silakan coba lagi.');
            });
    });
    document.getElementById('btnExportInternalExcel').addEventListener('click', function() {
        let monthValue = $('#month').val();
        window.location = "{{ route('export.internal') }}?month=" + monthValue;
    });

    document.getElementById('btnExportAllExcel').addEventListener('click', function() {
        let monthValue = $('#month').val();
        window.location = "{{ route('export.performance-all') }}?month=" + monthValue;
    });
</script>

@endsection
