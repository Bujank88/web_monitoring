@extends('master')
@section('title') Referral Champion Canvasser @endsection
@section('css')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet"/>

<style>
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

        .h3,
        .h4,
        .h5 {
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

    /* Enhanced Table Styling */
    .table {
        background-color: #fff;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        margin-top: 15px;
        border-collapse: separate;
        border-spacing: 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px !important;
        font-size: 13px;
        font-weight: 600;
        text-align: center !important;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table td {
        padding: 12px !important;
        font-size: 13px;
        border-bottom: 1px solid #e9ecef;
        color: #495057;
        text-align: center !important;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9ff;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }

    .table tbody tr:nth-child(odd) {
        background-color: #f9f9fc;
    }

    .table tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    /* Kolom Nomor */
    .table tbody tr td:nth-child(1) {
        font-weight: 600;
        color: #667eea;
        background-color: #f0f2f9;
        width: 5%;
    }

    /* Kolom Referral Code */
    .table tbody tr td:nth-child(2) {
        font-weight: 600;
        color: #333;
        letter-spacing: 0.5px;
    }

    /* Kolom Canvasser */
    .table tbody tr td:nth-child(3) {
        font-weight: 500;
        color: #333;
    }

    /* Kolom Jumlah New Akun */
    .table tbody tr td:nth-child(4) {
        background-color: #e3f2fd;
        color: #1976d2;
        font-weight: 600;
    }

    /* Kolom Top Up */
    .table tbody tr td:nth-child(5) {
        background: linear-gradient(135deg, #fff5e1 0%, #ffe0b2 100%);
        color: #e65100;
        font-weight: 600;
        font-size: 12px;
    }

    /* Kolom Insentif */
    .table tbody tr td:nth-child(6) {
        background: linear-gradient(135deg, #c8e6c9 0%, #81c784 100%);
        color: #2e7d32;
        font-weight: 600;
        font-size: 12px;
    }

    /* Highlight untuk insentif dengan nilai 0 */
    .table tbody tr td:nth-child(6):contains("-") {
        background: linear-gradient(135deg, #ffebee 0%, #ef9a9a 100%);
        color: #c62828;
    }

    /* DataTables wrapper styling */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }

    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label {
        font-weight: 500;
        color: #495057;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 6px 10px;
        font-size: 13px;
    }

    /* Custom Select2 Styling */
    .select2-container--bootstrap-5 .select2-selection {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        min-height: 38px;
        padding: 0.375rem 0.75rem;
        font-size: 0.9rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .select2-container--bootstrap-5 .select2-selection:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        padding-left: 0;
        color: #212529;
        line-height: 1.5;
    }

    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 8px;
    }

    .select2-dropdown {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .select2-container--bootstrap-5 .select2-results__option--highlighted {
        background-color: #0d6efd;
        color: #fff;
    }

    .select2-container--bootstrap-5 .select2-results__option--selected {
        background-color: #e7f1ff;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-label i {
        margin-right: 5px;
        color: #6c757d;
    }

    /* Filter Card Styling */
    .filter-section {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
    }

    .quick-nav-section {
        background: #fff;
        padding: 1rem;
        border-radius: 0.375rem;
        border: 1px solid #dee2e6;
    }

    /* Button Styling */
    .btn-group-custom .btn {
        margin-bottom: 0.5rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-group-custom .btn:hover {
        transform: translateX(5px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* Input Month Styling */
    input[type="month"] {
        cursor: pointer;
        padding: 0.5rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        transition: all 0.15s ease-in-out;
    }

    input[type="month"]:focus {
        border-color: #86b7fe;
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    /* Divider */
    .divider {
        height: 1px;
        background: linear-gradient(to right, transparent, #dee2e6, transparent);
        margin: 1rem 0;
    }

    /* Quick Navigation in Header */
    .btn-light {
        transition: all 0.3s ease;
        padding: 0.5rem 0.25rem;
        font-weight: 500;
    }

    .btn-light:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .btn-light i {
        font-size: 1.2rem;
    }

    .btn-light small {
        font-size: 0.75rem;
        display: block;
    }

    @media (max-width: 768px) {
        .btn-light i {
            font-size: 1rem;
        }
        .btn-light small {
            font-size: 0.65rem;
        }

        .table th,
        .table td {
            padding: 8px !important;
            font-size: 11px;
        }
    }

    /* Header dengan flexbox untuk download button */
    .card-header.d-flex {
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
    }

    .card-header .btn-actions {
        display: flex;
        gap: 8px;
        align-items: right;
    }

    .card-header .btn-success-custom {
        background-color: #28a745;
        color: white;
        border: 1px solid #28a745;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        white-space: nowrap;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
    }

    .card-header .btn-success-custom:hover {
        background-color: #218838;
        border-color: #1e7e34;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    }

    .card-header .btn-success-custom i {
        margin-right: 2px;
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

<!-- Summary Report Referral Champion Canvasser -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="canvaserSummaryCard">
            <div class="card-header bg-gradient-danger text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-chart-pie"></i> Summary Report Referral Champion Canvasser</h4>
                <div class="btn-actions" style="margin-right: -300px;">
                    <button type="button" id="btnSaveCanvasserSummaryImage" class="btn btn-success-custom btn-sm" title="Save as Image">
                        <i class="fas fa-image"></i> Save Image
                    </button>
                    <a href="{{ route('export.canvasser_voucher_summary') }}" class="btn btn-success-custom btn-sm" title="Download Excel">
                        <i class="fas fa-file-excel"></i> Download Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="captureCanvasserSummaryTable" class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="canvasserSummaryTable" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr style="background-color: #e3f2fd; font-weight: bold;">
                                <th colspan="6" style="text-align: center; padding: 10px; border-bottom: 2px solid #36b9cc;">Summary Referral Champion Canvasser</th>
                            </tr>
                            <tr>
                                <th style="text-align: center; width: 5%;">No</th>
                                <th style="text-align: center;">Referral Code</th>
                                <th style="text-align: center;">Nama Canvasser</th>
                                <th style="text-align: center;">Total Client</th>
                                <th style="text-align: center;">Total Top Up</th>
                                <th style="text-align: center;">Total Insentif</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Referral Champion Canvasser -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="canvaserTableCard">
            <div class="card-header bg-gradient-danger text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-table"></i> Detail Report Referral Champion Canvasser</h4>
                <div class="btn-actions" style="margin-right: -500px;">
                    <button type="button" id="btnSaveCanvasserImage" class="btn btn-success-custom btn-sm" title="Save as Image">
                        <i class="fas fa-image"></i> Save Image
                    </button>
                    <a href="{{ route('export.canvasser_voucher') }}" class="btn btn-success-custom btn-sm" title="Download Excel">
                        <i class="fas fa-file-excel"></i> Download Excel
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="captureCanvasserTable" class="table-responsive">
                    <table class="table table-sm w-100 table-bordered table-hover" id="regionalTable" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr style="background-color: #e8eaf6; font-weight: bold;">
                                <th colspan="7" style="text-align: center; padding: 10px; border-bottom: 2px solid #667eea;">Report Referral Champion Canvasser</th>
                            </tr>
                            <tr>
                                <th style="text-align: center; width: 5%;">No</th>
                                <th style="text-align: center;">Referral Code</th>
                                <th style="text-align: center;">Canvasser</th>
                                <th style="text-align: center;">Email Client</th>
                                <th style="text-align: center;">Top Up</th>
                                <th style="text-align: center;">Insentif</th>
                                <th style="text-align: center;">Tgl Transaksi Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables for Detail
        var table = $('#regionalTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('canvasser_voucher_data') }}",
                type: 'GET'
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'referral_code', name: 'referral_code', className: 'text-center' },
                { data: 'canvasser', name: 'canvasser', className: 'text-center' },
                { data: 'email_client', name: 'email_client', className: 'text-center' },
                { data: 'total_topup', name: 'total_topup', className: 'text-center' },
                { data: 'insentif', name: 'insentif', className: 'text-center' },
                { data: 'tgl_transaksi_terakhir', name: 'tgl_transaksi_terakhir', className: 'text-center' }
            ],
            order: [[1, 'asc']],
            rowCallback: function(row, data, index) {
                // Highlight insentif jika bernilai "-"
                var insentifCell = $('td', row).eq(5);
                if (insentifCell.text().trim() === '-') {
                    insentifCell.css({
                        'background': 'linear-gradient(135deg, #ffebee 0%, #ef9a9a 100%)',
                        'color': '#c62828'
                    });
                }
            }
        });

        // Initialize DataTables for Summary
        var summaryTable = $('#canvasserSummaryTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            searching: false,
            paging: false,
            ajax: {
                url: "{{ route('canvasser_voucher_summary') }}",
                type: 'GET'
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'referral_code', name: 'referral_code', className: 'text-center' },
                { data: 'canvasser', name: 'canvasser', className: 'text-center' },
                { data: 'total_client', name: 'total_client', className: 'text-center' },
                { data: 'total_topup', name: 'total_topup', className: 'text-center' },
                { data: 'total_insentif', name: 'total_insentif', className: 'text-center' }
            ],
            order: [[1, 'asc']],
            rowCallback: function(row, data, index) {
                // Highlight row in red if insentif is 0
                var insentifCell = $('td', row).eq(5);
                if (data.total_insentif == 0 || data.total_insentif == '0' || data.total_insentif == 'Rp 0') {
                    
                    insentifCell.css({
                        'background': 'linear-gradient(135deg, #ffebee 0%, #ef9a9a 100%)',
                        'color': '#c62828'
                    });
                }
            }
        });

        // Handle Save Image Button
        document.getElementById('btnSaveCanvasserImage').addEventListener('click', function () {
            html2canvas(document.getElementById('captureCanvasserTable'), { 
                scale: 2,
                allowTaint: true,
                useCORS: true
            })
                .then(canvas => {
                    const link = document.createElement('a');
                    link.download = 'canvasser-report-' + new Date().getTime() + '.png';
                    link.href = canvas.toDataURL();
                    link.click();
                })
                .catch(err => {
                    console.error('Error capturing image:', err);
                    alert('Gagal menyimpan gambar. Silakan coba lagi.');
                });
        });

        // Handle Save Image Button for Summary
        document.getElementById('btnSaveCanvasserSummaryImage').addEventListener('click', function () {
            html2canvas(document.getElementById('captureCanvasserSummaryTable'), { 
                scale: 2,
                allowTaint: true,
                useCORS: true
            })
                .then(canvas => {
                    const link = document.createElement('a');
                    link.download = 'canvasser-summary-' + new Date().getTime() + '.png';
                    link.href = canvas.toDataURL();
                    link.click();
                })
                .catch(err => {
                    console.error('Error capturing image:', err);
                    alert('Gagal menyimpan gambar. Silakan coba lagi.');
                });
        });
    });
</script>
@endsection