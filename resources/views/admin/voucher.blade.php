@extends('master')
@section('title') Manajemen Voucher @endsection

{{-- CSS Tambahan (jika perlu) --}}
@section('css')
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

    #loading-message {
        font-size: 24px;
        color: white;
        text-align: center;
    }

    .btn-ref {
        position: fixed;
        top: 50px;
        left: 1000px;
    }

    .table {
        background-color: #f9f9f9;
        border-radius: 8px;
        overflow: hidden;
        width: auto;
        max-width: 100%;
        margin-top: 15px;
        margin: 0 auto;
        border: 0.5px solid #ccc;
        table-layout: auto;
        margin-left: auto;
        /* << DITAMBAHKAN AGAR KE TENGAH */
        margin-right: auto;
        /* << DITAMBAHKAN AGAR KE TENGAH */

        /* Allow dynamic column sizing */
    }

    .table th,
    .table td {
        padding: 4px !important;
        font-size: 16px;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
    }

    .table th {
        font-weight: bold;
        text-align: center;
        background-color: #343a40;
        color: #ffffff;
        /* Align text to the left */
    }


    .table tbody tr {
        transition: background-color 0.3s;
    }

    .table tbody tr:hover {
        background-color: #6b6b6bff;
    }

    .table tbody tr:nth-child(odd) {
        background-color: #f2f2f2;
        /* Odd row background color */
    }

    .table tbody tr:nth-child(even) {
        background-color: #ffffff;
        /* Even row background color */
    }

    /* Atur agar tombol aksi tidak terpisah baris */
    .btn-group-sm>.btn,
    .btn-sm {
        margin: 0 2px;
    }
</style>
@endsection

@section('content')

<div class="row mt-3" id="summaryBox">
    <div class="col-md-4 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-tags"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><strong>Total Voucher</strong></span>
                <span class="info-box-number" id="totalVoucher">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><strong>Voucher Terpakai</strong></span>
                <span class="info-box-number" id="totalClaimed">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-box-open"></i></span>
            <div class="info-box-content">
                <span class="info-box-text"><strong>Sisa Voucher</strong></span>
                <span class="info-box-number" id="totalNotClaimed">0</span>
            </div>
        </div>
    </div>
</div>
{{-- Overlay Loading --}}
<div id="loading-overlay">
    <div id="loading-message">Loading, mohon tunggu...</div>
</div>

<div class="card card-danger">
    <div class="card-header">
        <h3 style="font-weight: bold" class="card-title">MANAJEMEN DATA VOUCHER</h3>
    </div>
    <div class="card-body">
        {{-- Tombol untuk memunculkan modal tambah data --}}
        <div class="d-flex justify-content-end mb-3">

            <button class="btn btn-success" id="btn-tambah-voucher">
                <i class="fas fa-plus-circle"></i> Tambah Voucher
            </button>

        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover" id="datatablenya" style="font-size: 14px;">
                <thead>
                    <tr>
                        <th class="text-center">Tanggal Dibuat</th>
                        <th class="text-center">Voucher</th>
                        <th class="text-center">Status</th>
                        <th class="text-center" style="width: auto;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Data akan diisi oleh DataTables --}}
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="voucherModal" tabindex="-1" role="dialog" aria-labelledby="voucherModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voucherModalLabel">Form Voucher</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="voucherForm">
                    {{-- ID voucher, disembunyikan. Digunakan untuk proses update --}}
                    <input type="hidden" id="voucher_id" name="voucher_id">

                    <div class="form-group">
                        <label for="voucher">Kode Voucher</label>
                        <input type="text" class="form-control" id="voucher" name="voucher" placeholder="Masukkan kode voucher" required>
                        {{-- Tempat untuk menampilkan pesan error validasi --}}
                        <span class="text-danger" id="voucher-error"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-simpan">Simpan</button>
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
        // Muat statistik voucher saat halaman dimuat
        loadVoucherStats();
        // Setup CSRF Token untuk semua request AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Inisialisasi DataTables
        var table = $('#datatablenya').DataTable({
            processing: true,

            serverSide: true,

            ordering: false,

            orderable: false,

            lengthMenu: [

                [10, 25, 50, 100],

                [10, 25, 50, 100]

            ],
            ajax: "{{ route('vouchers.data') }}", // Route untuk mengambil data
            columns: [{
                    data: 'created_at',
                    name: 'created_at',
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
                {
                    data: 'voucher',
                    name: 'voucher',
                    className: 'text-center'
                },
                {
                    data: 'status_klaim',
                    name: 'status_klaim',
                    orderable: false, // Sebaiknya non-aktifkan order/search
                    searchable: false,
                    className: 'text-center',
                    render: function(data, type, row) {
                        // 'data' akan berisi 'claimed' atau 'not_claimed'
                        if (data === 'claimed') {
                            return '<span class="badge badge-success">Sudah Diklaim</span>';
                        }
                        // Selain itu, pasti 'not_claimed'
                        return '<span class="badge badge-secondary">Belum Diklaim</span>';
                    }
                },
                {
                    data: 'aksi',
                    name: 'aksi',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
            ],
            preDrawCallback: function(settings) {
                $('#loading-overlay').show();
            },
            drawCallback: function(settings) {
                $('#loading-overlay').hide();
            }
        });

        // 1. Script untuk Tambah Data
        $('#btn-tambah-voucher').click(function() {
            $('#voucherForm').trigger("reset"); // Reset form
            $('#voucher_id').val(''); // Kosongkan ID
            $('#voucher-error').text(''); // Hapus pesan error
            $('#voucherModalLabel').html("Tambah Voucher Baru"); // Ubah judul modal
            $('#voucherModal').modal('show'); // Tampilkan modal
        });

        // 2. Script untuk Edit Data
        $('body').on('click', '.editVoucher', function() {
            var voucher_id = $(this).data('id');
            var row = $(this).closest('tr');
            var data = table.row(row).data(); // Ambil data dari baris yang dipilih

            $('#voucherForm').trigger("reset");
            $('#voucher-error').text('');
            $('#voucherModalLabel').html("Edit Voucher");
            $('#voucher_id').val(data.id);
            $('#voucher').val(data.voucher); // Isi form dengan data yang ada
            $('#voucherModal').modal('show');
        });

        // 3. Script untuk Simpan (Create & Update)
        $('#btn-simpan').click(function(e) {
            e.preventDefault();
            $(this).html('Menyimpan..');

            var voucherId = $('#voucher_id').val();
            var url = voucherId ? "{{ url('vouchers/update') }}/" + voucherId : "{{ route('vouchers.tambah') }}";
            var method = 'POST';

            $.ajax({
                data: $('#voucherForm').serialize(),
                url: url,
                type: method,
                dataType: 'json',
                success: function(response) {
                    $('#voucherForm').trigger("reset");
                    $('#voucherModal').modal('hide');
                    table.draw(); // Refresh tabel
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.success,
                        timer: 1500
                    });
                },
                error: function(response) {
                    // Tampilkan error validasi
                    if (response.responseJSON.errors) {
                        $('#voucher-error').text(response.responseJSON.errors.voucher[0]);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Terjadi kesalahan!'
                        });
                    }
                },
                complete: function() {
                    $('#btn-simpan').html('Simpan');
                }
            });
        });

        // 4. Script untuk Hapus Data
        $('body').on('click', '.hapusVoucher', function() {
            var voucher_id = $(this).data("id");
            Swal.fire({
                title: 'Apakah Anda Yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "DELETE",
                        url: "{{ url('vouchers/hapus') }}/" + voucher_id,
                        success: function(data) {
                            table.draw();
                            Swal.fire({
                                icon: 'success',
                                title: 'Dihapus!',
                                text: 'Data voucher berhasil dihapus.',
                                timer: 1500
                            });
                        },
                        error: function(data) {
                            console.error('Error:', data);
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: 'Terjadi kesalahan saat menghapus data.'
                            });
                        }
                    });
                }
            });
        });

    });
    // FUNGSI BARU UNTUK MEMUAT STATISTIK VOUCHER
    function loadVoucherStats() {
        // Tampilkan loading spinner jika ada
        $('#totalVoucher, #totalClaimed, #totalNotClaimed').html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: "{{ route('vouchers.stats') }}", // Panggil route yang baru dibuat
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update angka di setiap info box dengan data dari controller
                    $('#totalVoucher').text(response.data.total_voucher);
                    $('#totalClaimed').text(response.data.total_claimed);
                    $('#totalNotClaimed').text(response.data.total_not_claim);
                }
            },
            error: function(xhr, status, error) {
                console.error("Gagal mengambil statistik voucher:", error);
                // Tampilkan pesan error jika gagal
                $('#totalVoucher, #totalClaimed, #totalNotClaimed').text('Error');
            }
        });
    }
</script>
<script>
    
</script>
@endsection