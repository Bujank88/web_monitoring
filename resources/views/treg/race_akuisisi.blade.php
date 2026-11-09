@extends('master')
@section('title') Adsvantage Race: Aquisition Details @endsection
@section('css')

<style>
    .btn-ref {
        position: fixed;
        top: 50px;
        left: 1000px;
    }

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

    .table {
        background-color: #f9f9f9;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        margin-top: 15px;
        border: 0.5px solid #ccc;
        table-layout: auto;

        /* Allow dynamic column sizing */
    }

    .table th,
    .table td {
        padding: 8px !important;
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

    @media (max-width: 768px) {

        .table th,
        .table td {
            font-size: 12px;
        }
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

<!-- Modal Upload Voucher -->
<div class="modal fade" id="modalUploadVoucher" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Upload Voucher TREG</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <div class="form-group">
                    <label><strong>Upload File CSV</strong></label>
                    <input type="file" id="csvFileModal" class="form-control" accept=".csv">
                </div>

                <div class="mt-3">
                    <a href="{{ route('download.format.voucher.treg') }}"
                        class="btn btn-info btn-block">
                        <i class="fas fa-download"></i> Download Format CSV
                    </a>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" id="btnUploadModal" class="btn btn-primary">
                    Upload
                </button>
            </div>

        </div>
    </div>
</div>

<div class="mb-3 d-flex justify-content-between align-items-center">
    <button id="btnUpload" class="btn btn-primary">
        <i class="fas fa-upload"></i> Upload Transaction Voucher (CSV)
    </button>
    <input type="file" id="csvFile" accept=".csv" style="display:none;">
</div>



<div class="spinner-wrapper">

    <div id="loading-overlay" style="display: none;">



        <div id="loading-message"
            style="font-size: 24px; color: white; text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            Loading, please wait...

        </div>

    </div>

    <div class="card card-danger">

        <div class="card-header">

            <h3 style="font-weight: bold" class="card-title">DATA Acquisition Details</h3>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm w-100 table-striped table-hover"
                    id="datatablenya" style="font-size: 13px;">

                    <thead>

                        <tr>
                            <th class="text-center">Tanggal<br>Penukaran</th>
                            <th class="text-center">Kode<br>Voucher</th>
                            <th class="text-center">Email<br>Akun MyAds</th>
                            <th class="text-center">Saldo<br>TopUp</th>
                        </tr>

                    </thead>

                    <tbody>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection
@section('js')
<script>
    $(document).ready(function() {
        var table = $('#datatablenya').DataTable({

            processing: true,

            serverSide: true,

            ordering: false,

            orderable: false,

            lengthMenu: [

                [10, 25, 50, 100],

                [10, 25, 50, 100]

            ],

            ajax: {

                url: "{{ route('akuisisi_data') }}",

                type: "GET",

                dataSrc: function(json) {

                    console.log("Response dari server:", json); // Debugging response dari server

                    return json.data || []; // Pastikan tidak ada undefined/null

                }

            },



            columns: [{
                    data: 'tanggal_penukaran',
                    name: 'tanggal_penukaran',
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

                    data: 'voucher_code',

                    name: 'voucher_code',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },
                {

                    data: 'client_email',

                    name: 'client_email',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },
                {

                    data: 'total_amount',

                    name: 'total_amount',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },


            ],

            preDrawCallback: function(settings) {

                $('#loading-overlay').show();

            },

            drawCallback: function(settings) {

                $('#loading-overlay').hide();

            }

        });
        $('#datatablenya').on('error.dt', function(e, settings, techNote, message) {

            console.log("DataTables Error:", message);

        });
        $('#btnUpload').on('click', function() {
            $('#modalUploadVoucher').modal('show');
        });


        $('#btnUploadModal').on('click', function() {
            const file = $('#csvFileModal')[0].files[0];
            if (!file) {
                Swal.fire("Error", "Pilih file CSV terlebih dahulu", "error");
                return;
            }

            let formData = new FormData();
            formData.append('file', file);

            $('#loading-overlay').show();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: "{{ route('upload.voucher.csv') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#loading-overlay').hide();
                    $('#modalUploadVoucher').modal('hide');

                    Swal.fire({
                        icon: 'success',
                        title: 'Upload Berhasil',
                        text: response.message,
                    });

                    $('#datatablenya').DataTable().ajax.reload(null, false);
                },
                error: function(xhr) {
                    $('#loading-overlay').hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Gagal',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan saat upload file.',
                    });
                }
            });
        });



    });
</script>

@endsection