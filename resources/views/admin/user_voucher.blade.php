@extends('master')
@section('title') Manajemen Klaim Voucher @endsection

{{-- CSS Tambahan (jika perlu) --}}
@section('css')
<style>
    /* CSS Anda dari file sebelumnya bisa dipertahankan karena sudah bagus */
    #loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
    }
    #loading-message {
        font-size: 24px;
        color: white;
        text-align: center;
    }
    .table {
        background-color: #f9f9f9;
        border-radius: 8px;
        overflow: hidden;
        width: 100%; /* Agar tabel responsif */
        max-width: 100%;
        margin-top: 15px;
        border: 0.5px solid #ccc;
        table-layout: auto;
    }
    .table th, .table td {
        padding: 8px !important;
        font-size: 16px !important;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
        vertical-align: middle;
    }
    .table th {
        font-weight: bold;
        background-color: #343a40;
        color: #ffffff;
    }
    .btn-group-sm > .btn, .btn-sm {
        margin: 0 2px;
    }
</style>
@endsection

@section('content')

{{-- Overlay Loading --}}
<div id="loading-overlay">
    <div id="loading-message">Loading, mohon tunggu...</div>
</div>

<div class="card card-primary"> {{-- Ubah warna card agar berbeda --}}
    <div class="card-header">
        <h3 style="font-weight: bold" class="card-title">MANAJEMEN KLAIM VOUCHER</h3>
    </div>
    <div class="card-body">
        
        <div class="d-flex justify-content-end mb-3">
            {{-- Tombol Download Raw Data yang sudah diklaim --}}
            <a href="{{ route('vouchers.download-claim-voucher') }}" class="btn btn-info" id="btn-download-raw">
                <i class="fas fa-download"></i> Download Raw Data
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover w-100" id="claimedVoucherTable" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th class="text-center">Tanggal <br> Klaim Voucher</th>
                        <th class="text-center">Nama User</th>
                        <th class="text-center">Usaha</th>
                        <th class="text-center">Email</th>
                        <th class="text-center">Nomor HP</th>
                        <th class="text-center">Kode Voucher</th>
                        <th class="text-center" style="width: 20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Data akan diisi oleh DataTables --}}
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Edit Data User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="user_id" name="user_id">
                    
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                        <span class="text-danger" id="nama-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="usaha">Usaha</label>
                        <input type="text" class="form-control" id="usaha" name="usaha">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <span class="text-danger" id="email-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="nomor_hp">Nomor HP</label>
                        <input type="text" class="form-control" id="nomor_hp" name="nomor_hp">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan-user">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
{{-- SweetAlert2 untuk notifikasi yang lebih cantik --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Setup CSRF Token
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Inisialisasi DataTables untuk data klaim voucher
        var table = $('#claimedVoucherTable').DataTable({
            processing: true,

            serverSide: true,

            ordering: false,

            orderable: false,

            lengthMenu: [

                [10, 25, 50, 100],

                [10, 25, 50, 100]

            ],
            ajax: "{{ route('vouchers.claimed.data') }}", // Panggil route data klaim
            columns: [
                {
                    data: 'tanggal_daftar',
                    name: 'mu.created_at',
                    orderable: true,
                    render: function(data) {
                        if (!data) return '';
                        // Parse date string and format as "07 Aug 2025 13:25"
                        const dateObj = new Date(data);
                        if (isNaN(dateObj)) return data;
                        const day = String(dateObj.getDate()).padStart(2, '0');
                        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                        const month = monthNames[dateObj.getMonth()];
                        const year = dateObj.getFullYear();
                        const hours = String(dateObj.getHours()).padStart(2, '0');
                        const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                        return `<div style="text-align: center;">${day} ${month} ${year}</div>`;
                    }
                },
                { data: 'nama', name: 'mu.nama' },
                { data: 'usaha', name: 'mu.usaha' },
                { data: 'email', name: 'mu.email' },
                { data: 'nomor_hp', name: 'mu.nomor_hp' },
                { data: 'kode_voucher', name: 'mv.voucher' },
                { data: 'aksi', name: 'aksi', orderable: false, searchable: false },
            ],
            preDrawCallback: function(settings) { $('#loading-overlay').show(); },
            drawCallback: function(settings) { $('#loading-overlay').hide(); }
        });

        // 1. Script untuk tombol EDIT USER
        $('body').on('click', '.editUser', function() {
            var user_id = $(this).data('user-id');
            var data = table.row($(this).parents('tr')).data();
            
            // Reset dan isi form di modal
            $('#userForm').trigger("reset");
            $('.text-danger').text('');
            $('#userModalLabel').html("Edit Data User");
            $('#user_id').val(data.user_id);
            $('#nama').val(data.nama);
            $('#usaha').val(data.usaha);
            $('#email').val(data.email);
            $('#nomor_hp').val(data.nomor_hp);
            $('#userModal').modal('show');
        });

        // 2. Script untuk SIMPAN PERUBAHAN USER
        $('#btn-simpan-user').click(function(e) {
            e.preventDefault();
            $(this).html('Menyimpan..');
            var userId = $('#user_id').val();
            
            $.ajax({
                data: $('#userForm').serialize(),
                url: "{{ url('vouchers/update-user') }}/" + userId,
                type: "POST",
                dataType: 'json',
                success: function(response) {
                    $('#userModal').modal('hide');
                    table.draw();
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.success, timer: 1500 });
                },
                error: function(response) {
                    // Tampilkan error validasi
                    let errors = response.responseJSON.errors;
                    if(errors.nama) { $('#nama-error').text(errors.nama[0]); }
                    if(errors.email) { $('#email-error').text(errors.email[0]); }
                },
                complete: function() {
                    $('#btn-simpan-user').html('Simpan Perubahan');
                }
            });
        });

        // 3. Script untuk tombol LEPAS KLAIM (Unclaim)
        $('body').on('click', '.unclaimVoucher', function() {
            var voucher_id = $(this).data("voucher-id");
            Swal.fire({
                title: 'Apakah Anda Yakin?',
                text: "Klaim voucher ini akan dilepaskan dari user!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Lepaskan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('vouchers/unclaim') }}/" + voucher_id,
                        success: function(data) {
                            table.draw();
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.success, timer: 1500 });
                        },
                        error: function(data) {
                            Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan.' });
                        }
                    });
                }
            });
        });

    });
</script>
@endsection