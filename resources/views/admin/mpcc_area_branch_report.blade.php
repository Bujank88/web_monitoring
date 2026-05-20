@extends('master')
@section('title') MPCC Area Branch Report @endsection
@section('css')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet"/>
<style>
    .summary-card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border: 1px solid #e3e6f0;
        border-left-width: 0.35rem;
        border-radius: 0.35rem;
    }
    .summary-card .card-body {
        padding: 1rem 1.25rem;
    }
    .border-left-primary { border-left-color: #4e73df !important; }
    .border-left-success { border-left-color: #1cc88a !important; }
    .border-left-info { border-left-color: #36b9cc !important; }
    .border-left-warning { border-left-color: #f6c23e !important; }
    .border-left-danger { border-left-color: #e74a3b !important; }
    .border-left-secondary { border-left-color: #858796 !important; }
    .table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }
    .table td {
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }
</style>
@endsection
@section('content')
@php
    $startDate = request('start_date', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
    $endDate = request('end_date', \Carbon\Carbon::now()->format('Y-m-d'));
@endphp
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">MPCC Area / Branch Report</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-filter mr-2"></i>Filter Periode</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-3">
                            <label for="startDateAreaBranch">Start Date</label>
                            <input type="date" id="startDateAreaBranch" class="form-control" value="{{ $startDate }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="endDateAreaBranch">End Date</label>
                            <input type="date" id="endDateAreaBranch" class="form-control" value="{{ $endDate }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="button" id="btnExportMpccAreaBranch" class="btn btn-success btn-block">
                                <i class="fas fa-file-excel mr-2"></i>Download Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card summary-card border-left-primary"><div class="card-body"><div class="text-xs text-uppercase text-primary font-weight-bold">Total Area</div><div class="h5 mb-0 font-weight-bold" id="totalAreaCount">0</div></div></div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card summary-card border-left-success"><div class="card-body"><div class="text-xs text-uppercase text-success font-weight-bold">Total Branch</div><div class="h5 mb-0 font-weight-bold" id="totalBranchCount">0</div></div></div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card summary-card border-left-info"><div class="card-body"><div class="text-xs text-uppercase text-info font-weight-bold">Total MPCC</div><div class="h5 mb-0 font-weight-bold" id="totalMpccCount">0</div></div></div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card summary-card border-left-danger"><div class="card-body"><div class="text-xs text-uppercase text-danger font-weight-bold">Actual Visit</div><div class="h5 mb-0 font-weight-bold" id="totalActualVisit">0</div></div></div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card summary-card border-left-secondary"><div class="card-body"><div class="text-xs text-uppercase text-secondary font-weight-bold">Actual Leads</div><div class="h5 mb-0 font-weight-bold" id="totalLeadsCount">0</div></div></div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card summary-card border-left-primary"><div class="card-body"><div class="text-xs text-uppercase text-primary font-weight-bold">Actual Akun</div><div class="h5 mb-0 font-weight-bold" id="totalAkunCount">0</div></div></div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card summary-card border-left-success"><div class="card-body"><div class="text-xs text-uppercase text-success font-weight-bold">Achievement (%)</div><div class="h5 mb-0 font-weight-bold" id="totalAchievement">0%</div></div></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-gradient-info text-white">
                    <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Rekap Area / Branch MPCC</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover w-100" id="mpccAreaBranchTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Area</th>
                                    <th>Branch</th>
                                    <th>Cluster</th>
                                    <th>Jumlah MPCC</th>
                                    <th>Target Revenue Cluster (B)</th>
                                    <th>Target Revenue Branch (B)</th>
                                    <th>Target Visit</th>
                                    <th>Target Leads</th>
                                    <th>Target Registrasi</th>
                                    <th>Actual Visit</th>
                                    <th>Actual Leads</th>
                                    <th>Actual Akun</th>
                                    <th>Total Top Up</th>
                                    <th>Achievement (%)</th>
                                    <th>Tgl Transaksi Terakhir</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr style="font-weight: 700; background: #f8f9fc;">
                                    <th colspan="4" style="text-align:center;">TOTAL</th>
                                    <th id="tfootJumlahMpcc">0</th>
                                    <th id="tfootTargetRevenueCluster">0</th>
                                    <th id="tfootTargetRevenueBranch">0</th>
                                    <th id="tfootTargetVisit">0</th>
                                    <th id="tfootTargetLeads">0</th>
                                    <th id="tfootTargetRegistrasi">0</th>
                                    <th id="tfootActualVisit">0</th>
                                    <th id="tfootActualLeads">0</th>
                                    <th id="tfootActualAkun">0</th>
                                    <th id="tfootTotalTopup">Rp 0</th>
                                    <th id="tfootAchievement">0%</th>
                                    <th>-</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script>
$(function () {
    const table = $('#mpccAreaBranchTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        ajax: {
            url: "{{ route('mpcc.report.area-branch.data') }}",
            type: 'GET',
            data: function (d) {
                d.start_date = $('#startDateAreaBranch').val();
                d.end_date = $('#endDateAreaBranch').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', className: 'text-center' },
            { data: 'area', className: 'text-center' },
            { data: 'branch', className: 'text-center' },
            { data: 'cluster', className: 'text-center' },
            { data: 'jumlah_mpcc', className: 'text-center' },
            { data: 'target_revenue_cluster_billion', className: 'text-center' },
            { data: 'target_revenue_branch_billion', className: 'text-center' },
            { data: 'target_visit', className: 'text-center' },
            { data: 'target_leads', className: 'text-center' },
            { data: 'target_registrasi', className: 'text-center' },
            { data: 'actual_visit', className: 'text-center' },
            { data: 'jumlah_leads', className: 'text-center' },
            { data: 'jumlah_akun', className: 'text-center' },
            { data: 'total_topup', className: 'text-center' },
            { data: 'achievement', className: 'text-center' },
            { data: 'tgl_transaksi_terakhir', className: 'text-center' }
        ],
        rowCallback: function (row, data) {
            applyAchievementStyle($('td', row).eq(14), data.achievement);
        },
        drawCallback: function () {
            calculateTotals();
        }
    });

    function getAchievementStyle(percent) {
        if (percent >= 100) {
            return {
                background: 'linear-gradient(135deg, #2e7d32 0%, #43a047 100%)',
                color: '#ffffff',
                fontWeight: '700'
            };
        }

        if (percent >= 70) {
            return {
                background: 'linear-gradient(135deg, #f9a825 0%, #fbc02d 100%)',
                color: '#1f2937',
                fontWeight: '700'
            };
        }

        return {
            background: 'linear-gradient(135deg, #c62828 0%, #e53935 100%)',
            color: '#ffffff',
            fontWeight: '700'
        };
    }

    function parsePercent(value) {
        return parseFloat(String(value || '0').replace('%', '').replace(/\./g, '').replace(',', '.')) || 0;
    }

    function applyAchievementStyle(cell, value) {
        cell.css(getAchievementStyle(parsePercent(value)));
    }


    function parseCurrency(value) {
        return parseFloat(String(value || '0').replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.')) || 0;
    }
    function calculateTotals() {
        let totalMpcc = 0;
        let totalLeads = 0;
        let totalAkun = 0;
        let totalActualVisit = 0;
        let totalTargetVisit = 0;
        let totalTargetLeads = 0;
        let totalTargetRegistrasi = 0;
        let totalRevenueCluster = 0;
        let totalRevenueBranch = 0;
        let totalTopupValue = 0;
        const areas = new Set();
        const clusters = new Set();
        const branches = new Set();

        table.rows().data().each(function (row) {
            areas.add(row.area);
            clusters.add(row.cluster);
            branches.add(row.branch);
            totalMpcc += parseInt(row.jumlah_mpcc) || 0;
            totalLeads += parseInt(row.jumlah_leads) || 0;
            totalAkun += parseInt(row.jumlah_akun) || 0;
            totalActualVisit += parseInt(row.actual_visit) || 0;
            totalTargetVisit += parseInt(row.target_visit) || 0;
            totalTargetLeads += parseInt(row.target_leads) || 0;
            totalTargetRegistrasi += parseInt(row.target_registrasi) || 0;
            totalRevenueCluster += parsePercent(row.target_revenue_cluster_billion);
            totalRevenueBranch += parsePercent(row.target_revenue_branch_billion);
            totalTopupValue += parseCurrency(row.total_topup);
        });

        const totalTargetRevenueBranchRp = totalRevenueBranch * 1000000000;
        const totalAchievement = totalTargetRevenueBranchRp > 0 ? (totalTopupValue / totalTargetRevenueBranchRp) * 100 : 0;
        const totalAchievementText = totalAchievement.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';

        $('#totalAreaCount').text(areas.size);
        $('#totalBranchCount').text(branches.size);
        $('#totalMpccCount').text(totalMpcc);
        $('#totalActualVisit').text(totalActualVisit.toLocaleString('id-ID'));
        $('#totalLeadsCount').text(totalLeads.toLocaleString('id-ID'));
        $('#totalAkunCount').text(totalAkun.toLocaleString('id-ID'));
        $('#totalAchievement').text(totalAchievementText);

        $('#tfootJumlahMpcc').text(totalMpcc.toLocaleString('id-ID'));
        $('#tfootTargetRevenueCluster').text(totalRevenueCluster.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#tfootTargetRevenueBranch').text(totalRevenueBranch.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#tfootTargetVisit').text(totalTargetVisit.toLocaleString('id-ID'));
        $('#tfootTargetLeads').text(totalTargetLeads.toLocaleString('id-ID'));
        $('#tfootTargetRegistrasi').text(totalTargetRegistrasi.toLocaleString('id-ID'));
        $('#tfootActualVisit').text(totalActualVisit.toLocaleString('id-ID'));
        $('#tfootActualLeads').text(totalLeads.toLocaleString('id-ID'));
        $('#tfootActualAkun').text(totalAkun.toLocaleString('id-ID'));
        $('#tfootAchievement').text(totalAchievementText);
        $('#tfootTotalTopup').text('Rp ' + Math.round(totalTopupValue).toLocaleString('id-ID'));
        applyAchievementStyle($('#tfootAchievement'), totalAchievement);
    }

    $('#startDateAreaBranch, #endDateAreaBranch').on('change', function () {
        table.ajax.reload();
    });

    $('#btnExportMpccAreaBranch').on('click', function () {
        const query = new URLSearchParams();
        const startDate = $('#startDateAreaBranch').val();
        const endDate = $('#endDateAreaBranch').val();

        if (startDate) query.append('start_date', startDate);
        if (endDate) query.append('end_date', endDate);

        const baseUrl = "{{ route('mpcc.report.area-branch.export') }}";
        window.location.href = query.toString() ? `${baseUrl}?${query.toString()}` : baseUrl;
    });
});
</script>
@endsection



