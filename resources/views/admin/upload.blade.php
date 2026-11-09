@extends('master')
@section('title') Upload File Revenue & Program @endsection

@section('css')
<style>
    .spinner-border {
        width: 3rem;
        height: 3rem;
    }
</style>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<!-- Loading overlay -->
<div id="loading-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:9999; text-align:center;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%)">
        <div class="spinner-border text-danger" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p style="margin-top:10px; font-weight:bold;">Uploading, please wait...</p>
    </div>
</div>

<div class="card card-danger">
    <div class="card-header">
        <h3 style="font-weight: bold" class="card-title">UPLOAD FILE REVENUE/PROGRAM </h3>
    </div>
    <div class="card-body">
        <h2>Pilih Kategori Program atau Revenue</h2>
        <form action="{{ route('upload.myads.store') }}" method="POST" enctype="multipart/form-data">
            @csrf


            <label>Pilih Goal Dept:</label>
            <select id="kategoriSelect" name="kategori" required class="form-control mb-3">
                <option value="">-- Pilih Goal Dept --</option>
                @foreach($myAdsUploads as $dept)
                <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
            </select>

            <label>Pilih File CSV:</label>
            <input type="file" name="csvFile" accept=".csv" required class="form-control mb-3">


            <a id="downloadFormatBtn" href="#" class="btn btn-outline-primary mb-3" style="display: none;" target="_blank">Download Format</a>


            <button type="submit" class="btn btn-danger">Upload</button>
        </form>
    </div>
</div>

@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('form').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            $('#loading-overlay').fadeIn(); // Tampilkan loading overlay

            $.ajax({
                url: "{{ route('upload.myads.store') }}",
                method: "POST",
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    $('#loading-overlay').fadeOut();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    $('#loading-overlay').fadeOut();

                    let errorMessage = "Terjadi kesalahan saat mengunggah file.";

                    // Ambil pesan error dari backend jika tersedia
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengunggah!',
                        html: `<strong>${errorMessage}</strong><br><br>Pastikan file Excel sesuai format dan jumlah kolom.`,
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });
    });
    $('#kategoriSelect').on('change', function() {
        var selectedKategori = $(this).val();

        if (selectedKategori) {
            $.ajax({
                url: "{{ route('upload.myads.getTableName') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    kategori: selectedKategori
                },
                success: function(res) {
                    if (res.table_name) {
                        $('#downloadFormatBtn')
                            .attr('href', '/download-format/' + res.table_name)
                            .show();
                    } else {
                        $('#downloadFormatBtn').hide();
                    }
                }
            });
        } else {
            $('#downloadFormatBtn').hide();
        }
    });
</script>
@endsection