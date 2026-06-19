@extends('master')
@section('title') {{ $pageTitle ?? 'Referral CDSI' }} @endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<style>
    .referral-panel {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .referral-header {
        background: linear-gradient(135deg, #b91c1c, #ef4444);
        color: #fff;
        border-radius: 14px 14px 0 0;
        padding: 16px 20px;
    }

    .code-group {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: end;
    }

    .table th,
    .table td {
        vertical-align: middle;
        font-size: 13px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card referral-panel">
            <div class="referral-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-user-plus mr-2"></i>{{ $pageTitle ?? 'Referral CDSI' }}
                </h3>
            </div>
            <div class="card-body">
                <form id="cdsiReferralForm">
                    @csrf
                    <div class="form-group">
                        <label for="name">Nama</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Masukkan nama PIC / owner referral">
                        <small class="text-danger d-none" data-error-for="name"></small>
                    </div>

                    <div class="form-group">
                        <label for="referral_code">Code Referral</label>
                        <div class="code-group">
                            <div>
                                <input type="text" id="referral_code" name="referral_code" class="form-control" placeholder="Generate dari nama referral atau isi manual">
                                <small class="text-danger d-none" data-error-for="referral_code"></small>
                            </div>
                            <button type="button" id="generateReferralBtn" class="btn btn-warning">
                                <i class="fas fa-random mr-1"></i> Generate
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save mr-1"></i> Simpan Referral
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mb-4">
        <div class="card referral-panel">
            <div class="referral-header">
                <h3 class="card-title mb-0">
                    <i class="fas fa-list mr-2"></i>Daftar Referral CDSI
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="cdsiReferralTable" class="table table-bordered table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Code Referral</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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
        const form = $('#cdsiReferralForm');

        const table = $('#cdsiReferralTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: "{{ route('cdsi.referrals.data') }}",
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                { data: 'name', name: 'name' },
                { data: 'referral_code', name: 'referral_code', className: 'text-center font-weight-bold' },
                { data: 'status', name: 'status', className: 'text-center', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at', className: 'text-center' },
                { data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false }
            ],
            order: [[4, 'desc']]
        });

        function clearErrors() {
            $('[data-error-for]').addClass('d-none').text('');
        }

        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: message,
                confirmButtonColor: '#dc3545'
            });
        }

        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                text: message,
                confirmButtonColor: '#dc3545'
            });
        }

        function loadGeneratedCode() {
            const name = $('#name').val().trim();

            if (!name) {
                clearErrors();
                $('[data-error-for="name"]').removeClass('d-none').text('Isi nama dulu sebelum generate code referral.');
                $('#name').focus();
                showError('Isi nama dulu sebelum generate code referral.');
                return;
            }

            $.get("{{ route('cdsi.referrals.generate') }}", { name: name }, function(response) {
                $('#referral_code').val(response.referral_code || '');
            });
        }

        $('#generateReferralBtn').on('click', function() {
            loadGeneratedCode();
        });

        form.on('submit', function(e) {
            e.preventDefault();
            clearErrors();

            $.ajax({
                url: "{{ route('cdsi.referrals.store') }}",
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        form[0].reset();
                        table.ajax.reload(null, false);
                        showSuccess(response.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        Object.keys(xhr.responseJSON.errors).forEach(function(field) {
                            const el = $('[data-error-for="' + field + '"]');
                            el.removeClass('d-none').text(xhr.responseJSON.errors[field][0]);
                        });
                        showError('Mohon cek kembali form referral CDSI.');
                        return;
                    }

                    showError('Terjadi kesalahan saat menyimpan referral.');
                }
            });
        });

        $(document).on('click', '.btnToggleReferralStatus', function() {
            const button = $(this);
            const referralId = button.data('id');
            const referralName = button.data('name');
            const nextStatus = button.data('status');

            Swal.fire({
                icon: 'question',
                title: 'Ubah Status Referral',
                text: 'Yakin ingin mengubah status referral ' + referralName + ' menjadi ' + (nextStatus === 'active' ? 'Active' : 'Non Active') + '?',
                showCancelButton: true,
                confirmButtonText: 'Ya, ubah',
                cancelButtonText: 'Batal',
                confirmButtonColor: nextStatus === 'active' ? '#28a745' : '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ url('cdsi/referrals') }}/" + referralId + "/status",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        status: nextStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload(null, false);
                            showSuccess(response.message);
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'Terjadi kesalahan saat mengubah status referral ' + referralName + '.';

                        showError(message);
                    }
                });
            });
        });
    });
</script>
@endsection
