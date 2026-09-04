@extends('master')
@section('title') {{ $pageTitle ?? 'Brand List' }} @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<style>
    .brand-list-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .brand-list-header {
        background: linear-gradient(135deg, #0f766e, #14b8a6);
        color: #fff;
        border-radius: 14px 14px 0 0;
        padding: 16px 20px;
    }

    .brand-list-table-wrap {
        display: none;
    }

    .table th,
    .table td {
        vertical-align: middle;
        font-size: 13px;
        white-space: nowrap;
    }

    .helper-text {
        font-size: 12px;
        color: #6b7280;
    }

    .brand-list-empty-state {
        display: block;
    }

    .brand-loading-overlay {
        position: absolute;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.78);
        backdrop-filter: blur(2px);
        z-index: 20;
        border-radius: 14px;
    }

    .brand-loading-card {
        background: #fff;
        border-radius: 16px;
        padding: 18px 22px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.14);
        text-align: center;
        min-width: 220px;
    }

    .brand-spinner {
        width: 42px;
        height: 42px;
        margin: 0 auto 12px;
        border: 4px solid #d1fae5;
        border-top-color: #0f766e;
        border-radius: 50%;
        animation: brandSpin 0.9s linear infinite;
    }

    @keyframes brandSpin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12 mb-4">
        <div class="card brand-list-card">
            <div class="brand-list-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-tags mr-2"></i>{{ $pageTitle ?? 'Brand List' }}
                </h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="brandFilter" class="font-weight-bold">Filter Brand</label>
                    <div class="row align-items-start">
                        <div class="col-md-9">
                            <input type="text" id="brandFilter" class="form-control" placeholder="Ketik brand atau beberapa kata lalu tekan Tampilkan">
                        <small class="helper-text">Masukkan nama perusahaan. Kalau ada spasi, hasil muncul jika salah satu kata cocok. Gunakan tanpa PT, CV, dan sejenisnya. Contoh: PT. Serasi Auto Raya cukup ketik Serasi Auto Raya.</small>
                        </div>
                        <div class="col-md-3 mt-3 mt-md-0">
                            <button type="button" id="btnFilterBrand" class="btn btn-success btn-block">
                                <i class="fas fa-search mr-1"></i>Tampilkan
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="alert alert-info mb-0">
                        Data tabel akan muncul setelah brand diisi.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div style="position: relative;">
            <div id="brandLoadingOverlay" class="brand-loading-overlay">
                <div class="brand-loading-card">
                    <div class="brand-spinner"></div>
                    <div class="font-weight-bold">Mencari brand...</div>
                    <small class="text-muted">Mohon tunggu sebentar</small>
                </div>
            </div>

            <div id="brandListEmptyState" class="card brand-list-empty-state">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-search fa-2x mb-3"></i>
                    <div>Masukkan brand di atas untuk menampilkan data.</div>
                </div>
            </div>

            <div id="brandListTableWrap" class="card brand-list-table-wrap">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-table mr-2"></i>Hasil Brand List
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="brandListTable" class="table table-bordered table-hover w-100">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        let brandListTable = null;
        let loadingTimer = null;

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showNotice(title, message, icon) {
            Swal.fire({
                icon: icon,
                title: title,
                text: message,
                confirmButtonColor: '#0f766e'
            });
        }

        function showLoading() {
            clearTimeout(loadingTimer);
            $('#brandLoadingOverlay').css('display', 'flex');
        }

        function hideLoading() {
            clearTimeout(loadingTimer);
            $('#brandLoadingOverlay').hide();
        }

        function showResultPopup(foundCount, brand) {
            if (foundCount > 0) {
                Swal.fire({
                    icon: 'success',
                    title: 'Data ditemukan',
                    html: '<strong>' + foundCount + '</strong> data cocok untuk filter <strong>' + escapeHtml(brand) + '</strong>.',
                    confirmButtonColor: '#0f766e'
                });
                return;
            }

            Swal.fire({
                icon: 'info',
                title: 'Tidak ada hasil',
                text: 'Tidak ada brand yang cocok untuk filter "' + brand + '".',
                confirmButtonColor: '#0f766e'
            });
        }

        function renderTable(columns, rows) {
            $('#brandListEmptyState').hide();
            $('#brandListTableWrap').show();

            if ($.fn.DataTable.isDataTable('#brandListTable')) {
                $('#brandListTable').DataTable().destroy();
                $('#brandListTable thead').empty();
                $('#brandListTable tbody').empty();
            }

            const headerHtml = '<tr>' + columns.map(function(column) {
                return '<th>' + escapeHtml(column.title) + '</th>';
            }).join('') + '</tr>';

            $('#brandListTable thead').html(headerHtml);

            brandListTable = $('#brandListTable').DataTable({
                data: rows,
                responsive: true,
                paging: true,
                searching: false,
                ordering: true,
                autoWidth: false,
                pageLength: 25,
                columns: columns.map(function(column) {
                    return {
                        data: column.data,
                        render: function(data) {
                            if (data === null || data === undefined || data === '') {
                                return '-';
                            }

                            return '<span>' + escapeHtml(data) + '</span>';
                        }
                    };
                })
            });
        }

        function loadBrandList() {
            const brand = $('#brandFilter').val().trim();

            if (!brand) {
                $('#brandListTableWrap').hide();
                $('#brandListEmptyState').show();
                showNotice('Filter kosong', 'Silakan ketik brand terlebih dahulu.', 'info');
                return;
            }

            showLoading();

            $.ajax({
                url: "{{ route('cdsi.brand-list.data') }}",
                method: 'GET',
                data: {
                    brand: brand
                },
                success: function(response) {
                    hideLoading();

                    if (!response.success) {
                        showNotice('Gagal', response.message || 'Data tidak dapat dimuat.', 'error');
                        return;
                    }

                    if (!response.columns || response.columns.length === 0) {
                        $('#brandListTableWrap').hide();
                        $('#brandListEmptyState').show();
                        showNotice('Tidak ada kolom', response.message || 'Skema tabel tidak tersedia.', 'warning');
                        return;
                    }

                    renderTable(response.columns, response.rows || []);
                    showResultPopup((response.rows || []).length, brand);

                    if ((response.rows || []).length === 0) {
                        $('#brandListTableWrap').hide();
                        $('#brandListEmptyState').show();
                    }
                },
                error: function(xhr) {
                    hideLoading();

                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Terjadi kesalahan saat memuat data brand.';

                    showNotice('Gagal', message, 'error');
                }
            });
        }

        $('#btnFilterBrand').on('click', function() {
            loadBrandList();
        });

        $('#brandFilter').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                loadBrandList();
            }
        });
    });
</script>
@endsection
