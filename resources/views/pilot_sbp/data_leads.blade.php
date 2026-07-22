@extends('master')

@section('title', $pageTitle ?? 'Data Leads')

@section('css')
<style>
    .data-leads-toolbar {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .data-leads-toolbar .filter-block {
        min-width: 280px;
        flex: 1 1 320px;
    }

    .data-leads-table-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }

    #pilotSbpDataLeadsTable {
        margin-bottom: 0;
    }

    #pilotSbpDataLeadsTable thead th {
        background: #f8fafc;
        color: #1f2937;
        font-weight: 700;
        white-space: nowrap;
        vertical-align: middle;
    }

    #pilotSbpDataLeadsTable tbody td {
        vertical-align: top;
    }

    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 8px;
        border: 1px solid #ced4da;
        min-height: 36px;
    }

    .dataTables_wrapper .dataTables_filter input {
        margin-left: 0.5rem;
        padding: 0.375rem 0.75rem;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 0.75rem;
    }

    .dataTables_wrapper .dataTables_info {
        color: #6b7280;
        font-size: 0.875rem;
        padding-top: 0.85rem;
    }

    .dataTables_wrapper .dataTables_paginate {
        padding-top: 0.5rem;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 8px !important;
        border: 1px solid #dbe3ef !important;
        background: #fff !important;
        color: #2563eb !important;
        margin-left: 0.25rem;
        min-width: 38px;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: #2563eb !important;
        border-color: #2563eb !important;
        color: #fff !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: #eff6ff !important;
        border-color: #93c5fd !important;
        color: #1d4ed8 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: #f8fafc !important;
        color: #9ca3af !important;
        border-color: #e5e7eb !important;
    }

    @media (max-width: 767.98px) {
        .data-leads-toolbar {
            align-items: stretch;
        }

        .data-leads-toolbar .filter-block,
        .data-leads-toolbar .action-block {
            width: 100%;
        }

        .data-leads-toolbar .action-block .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">{{ $pageTitle ?? 'Data Leads' }}</h3>
    </div>
    <div class="card-body">
        <div class="data-leads-toolbar">
            <div class="filter-block">
                <label for="filterJenisNomor">Jenis Nomor</label>
                <select id="filterJenisNomor" class="form-control" {{ $jenisNomorColumn ? '' : 'disabled' }}>
                    <option value="">Semua Jenis Nomor</option>
                    <option value="Non Telkomsel">Non Telkomsel</option>
                    <option value="Telkomsel">Telkomsel</option>
                    <option value="Nomor Rumah">Nomor Rumah</option>
                </select>
                @if(!$jenisNomorColumn)
                <small class="text-muted">Kolom jenis nomor tidak ditemukan di tabel `data_google_maps`.</small>
                @endif
            </div>
            <div class="action-block d-flex align-items-end justify-content-md-end">
                <a href="{{ route('pilot-sbp-sme.data-leads.export') }}" id="btnDownloadDataLeads" class="btn btn-success">
                    <i class="fas fa-download mr-1"></i> Download
                </a>
            </div>
        </div>

        <div class="table-responsive data-leads-table-wrap">
            <table id="pilotSbpDataLeadsTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        @foreach($columns as $column)
                        <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($leadRows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        @foreach($columns as $column)
                        <td>{{ data_get($row, $column, '-') }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(function () {
        const jenisNomorColumn = @json($jenisNomorColumn);
        const jenisNomorColumnIndex = @json($jenisNomorColumn ? array_search($jenisNomorColumn, $columns, true) : null);
        const table = $('#pilotSbpDataLeadsTable').DataTable({
            paging: true,
            pageLength: 10,
            lengthMenu: [[10, 50, 100, 500], [10, 50, 100, 500]],
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            pagingType: 'simple_numbers',
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                zeroRecords: 'Data tidak ditemukan',
                paginate: {
                    previous: 'Prev',
                    next: 'Next'
                }
            }
        });

        function updateExportLink() {
            const url = new URL("{{ route('pilot-sbp-sme.data-leads.export') }}", window.location.origin);
            const jenisNomor = $('#filterJenisNomor').val();

            if (jenisNomor) {
                url.searchParams.set('jenis_nomor', jenisNomor);
            }

            $('#btnDownloadDataLeads').attr('href', url.toString());
        }

        if (jenisNomorColumn) {
            $('#filterJenisNomor').on('change', function () {
                updateExportLink();
                const selectedValue = $(this).val();
                const dataTableColumnIndex = jenisNomorColumnIndex !== null ? jenisNomorColumnIndex + 1 : null;

                if (dataTableColumnIndex !== null) {
                    table.column(dataTableColumnIndex).search(selectedValue || '').draw();
                }
            });
        }

        updateExportLink();
    });
</script>
@endsection
