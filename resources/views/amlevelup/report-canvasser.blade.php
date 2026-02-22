@extends('master')
@section('title') AM Level UP Report @endsection
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

    /* Kolom Jumlah Leads */
    .table tbody tr td:nth-child(5) {
        background-color: #f3e5f5;
        color: #6a1b9a;
        font-weight: 600;
        font-size: 12px;
    }

    /* Kolom Jumlah Visit */
    .table tbody tr td:nth-child(6) {
        background-color: #e8f5e9;
        color: #1b5e20;
        font-weight: 600;
        font-size: 12px;
    }

    /* Kolom Top Up */
    .table tbody tr td:nth-child(7) {
        background: linear-gradient(135deg, #fff5e1 0%, #ffe0b2 100%);
        color: #e65100;
        font-weight: 600;
        font-size: 12px;
    }

    /* Kolom Insentif */
    .table tbody tr td:nth-child(8) {
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

    .card-header .btn-light {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .card-header .btn-light:hover {
        background-color: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        color: white;
        transform: translateY(-2px);
    }

    .card-header .btn-light i {
        margin-right: 5px;
    }

    /* Button Actions Styling */
    .card-header .btn-actions {
        display: flex;
        gap: 8px;
        align-items: center;
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
        margin-right: 6px;
    }

    /* Filter Button Styling */
    .btn-outline-primary {
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
    }

    /* Gap utility class */
    .gap-2 {
        gap: 8px;
    }</style>
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



<!-- Filter Section -->
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-end align-items-center gap-2">
        <select id="filterMonthCanvasser" name="filterMonthCanvasser" class="form-control" style="background-color: #313131; color: white; min-width: 180px; max-width: 200px;">
            @foreach ($months as $month)
            <option value="{{ $month['value'] }}" {{ $month['selected'] ? 'selected' : '' }}>
                {{ $month['label'] }}
            </option>
            @endforeach
        </select>
    </div>
</div>

<!-- Report amlevelup Canvasser -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card" id="amlevelupTableCard">
            <div class="card-header bg-gradient-danger text-white">
                <h4 class="mb-0"><i class="fas fa-table"></i> Report amlevelup Summary</h4>
            </div>
            <div class="card-body">
                <div id="captureamlevelupTable" class="table-responsive">
            <table class="table table-sm w-100 table-bordered table-hover" id="amlevelupTable" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr style="background-color: #e8eaf6; font-weight: bold;">
                                <th colspan="5" style="text-align: center; padding: 10px; border-bottom: 2px solid #667eea;">Report amlevelup Canvasser | Bulan: <span id="displayedMonthCanvasser">{{ $months[array_search(true, array_column($months, 'selected'))]['label'] ?? now()->format('F Y') }}</span></th>
                            </tr>
                            <tr>
                                <th style="text-align: center; width: 5%;">No</th>
                                <th style="text-align: center;">Canvasser Name</th>
                                <th style="text-align: center;">Jumlah Terdaftar</th>
                                <th style="text-align: center;">Jumlah Akun Punya Poin</th>
                                <th style="text-align: center;">Jumlah Poin</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr style="background: linear-gradient(135deg, #fffb00 0%, #ffee00 100%); color: white; font-weight: 600;">
                                <td colspan="2" style="text-align: right; padding: 12px;">TOTAL</td>
                                <td id="totalJumlahTerdaftar" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalJumlahAkunPunyaPoin" style="text-align: center; padding: 12px;">0</td>
                                <td id="totalJumlahPoin" style="text-align: center; padding: 12px;">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

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

@section('js')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables
        var table = $('#amlevelupTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            paging: false,
            searching: false,
            info: false,
            ajax: {
                url: "{{ route('amlevelup.report-canvasser-data') }}",
                type: 'GET',
                data: function(d) {
                    d.tanggal = $('#filterMonthCanvasser').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'nama_canvasser', name: 'nama_canvasser', className: 'text-center' },
                { data: 'jumlah_terdaftar', name: 'jumlah_terdaftar', className: 'text-center' },
                { data: 'jumlah_akun_punya_poin', name: 'jumlah_akun_punya_poin', className: 'text-center' },
                { data: 'jumlah_poin', name: 'jumlah_poin', className: 'text-center' }
            ],
            order: [[1, 'asc']],
            rowCallback: function(row, data, index) {
                // Highlight poin cells dengan warna biru gradient untuk nilai > 0
                var poinCell = $('td', row).eq(4);
                var poinValue = parseInt(poinCell.text().trim());
                
                if (poinValue > 0) {
                    poinCell.css({
                        'background': 'linear-gradient(135deg, #c3e7ff 0%, #90caf9 100%)',
                        'color': '#0d47a1',
                        'font-weight': '600'
                    });
                } else {
                    poinCell.css({
                        'background': 'linear-gradient(135deg, #ffebee 0%, #ef9a9a 100%)',
                        'color': '#c62828',
                        'font-weight': '600'
                    });
                }

                // Calculate totals after each row is rendered
                calculateTotals();
            },
            drawCallback: function() {
                // Recalculate totals when table is fully drawn
                calculateTotals();
            }
        });

        // Handle month filter change
        $('#filterMonthCanvasser').on('change', function() {
            var selectedText = $('#filterMonthCanvasser option:selected').text();
            $('#displayedMonthCanvasser').text(selectedText);
            table.ajax.reload();
        });

        // Function to calculate totals
        function calculateTotals() {
            let totalTerdaftar = 0;
            let totalAkunPunyaPoin = 0;
            let totalPoin = 0;

            // Iterate through all rows in tbody
            $('#amlevelupTable tbody tr').each(function() {
                const cells = $(this).find('td');
                
                // Column 3: Jumlah Terdaftar
                totalTerdaftar += parseInt(cells.eq(2).text().trim()) || 0;

                // Column 4: Jumlah Akun Punya Poin
                totalAkunPunyaPoin += parseInt(cells.eq(3).text().trim()) || 0;

                // Column 5: Jumlah Poin
                totalPoin += parseInt(cells.eq(4).text().trim()) || 0;
            });

            // Update total row
            $('#totalJumlahTerdaftar').text(totalTerdaftar);
            $('#totalJumlahAkunPunyaPoin').text(totalAkunPunyaPoin);
            $('#totalJumlahPoin').text(totalPoin);
        }

    });
</script>
@endsection

