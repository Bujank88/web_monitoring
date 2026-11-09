@extends('master')
@section('title') Monitoring Padi UMKM @endsection
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
        /* Sudah benar, pastikan ini tetap */
        /* Hapus min-width atau width pada th/td jika ada */
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
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-end align-items-center">
        <button id="showFilterBtn" class="btn btn-outline-primary" type="button" title="Filter Bulan" style="padding: 6px 12px;">
            <i class="fas fa-filter"></i>
        </button>
        <div id="filterContainer" style="display: none; min-width: 220px; margin-left: 10px;">
            <select id="tanggal" name="tanggal" class="form-control select2"
                style="background-color: #313131; color: white;">
                @foreach ($months as $month)
                <option value="{{ $month['value'] }}" {{ $month['selected'] ? 'selected' : '' }}>
                    {{ $month['label'] }}
                </option>
                @endforeach
            </select>
        </div>
    </div>
</div>



<div class="row mt-3" id="summaryBox">
    <div class="col-md-4 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-file-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Isi Form</span>
                <span class="info-box-number" id="totalForm">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-sort-amount-up"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Jumlah Top Up</span>
                <span class="info-box-number" id="jumlahTopup">0</span>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-money-bill-wave"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Top Up (Rp.)</span>
                <span class="info-box-number" id="totalTopup">Rp0</span>
            </div>
        </div>
    </div>
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

            <h3 style="font-weight: bold" class="card-title">DATA GFORM PADI UMKM X MYADS</h3>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm w-100 table-striped table-hover"
                    id="datatablenya" style="font-size: 13px;">

                    <thead>

                        <tr>
                            <th class="text-center">Tanggal<br>Isi Form</th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">Nomor HP</th>
                            <th class="text-center">Nama Usaha</th>
                            <th class="text-center">Jumlah Topup</th>
                            <th class="text-center">Total Topup (Rp.)</th>

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

                url: "{{ route('padi_umkm_data') }}",

                type: "GET",
                data: function(d) {
                    d.tanggal = $('#tanggal').val(); // Kirim nilai filter ke server
                },

                dataSrc: function(json) {

                    console.log("Response dari server:", json); // Debugging response dari server

                    return json.data || []; // Pastikan tidak ada undefined/null

                }

            },



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

                    data: 'nama',

                    name: 'nama',

                    render: data => `<div style="text-align: left;">${data}</div>`

                },

                {

                    data: 'email',

                    name: 'email',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'no_hp',

                    name: 'no_hp',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'nama_usaha',

                    name: 'nama_usaha',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },
                {

                    data: 'jumlah_topup',

                    name: 'jumlah_topup',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },
                {
                    data: 'total_topup',
                    name: 'total_topup',
                    orderable: true,
                    render: function(data) {
                        if (!data) return '<div style="text-align: center;">Rp0</div>';
                        // Format number as Rupiah: RpXXX.XXX
                        let formatted = 'Rp' + parseInt(data).toLocaleString('id-ID');
                        return `<div style="text-align: center;">${formatted}</div>`;
                    }
                }

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
        // Event onchange untuk filter
        $('#tanggal').on('change', function() {
            table.ajax.reload();
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(function() {
        $('#showFilterBtn').on('click', function() {
            $('#filterContainer').toggle();
        });
    });

    function loadSummary() {
        $.ajax({
            url: "{{ route('padi_umkm.summary') }}",
            data: {
                tanggal: $('#tanggal').val()
            },
            success: function(res) {
                $('#totalForm').text(new Intl.NumberFormat().format(res.total_form));
                $('#jumlahTopup').text(new Intl.NumberFormat().format(res.jumlah_topup));
                $('#totalTopup').text("Rp" + new Intl.NumberFormat('id-ID').format(res.total_topup));
            }
        });
    }
    $(document).ready(function() {
        loadSummary(); // Load summary saat halaman pertama kali dimuat
        $('#tanggal').on('change', function() {
            loadSummary(); // Load summary saat filter tanggal berubah
        });
    });
</script>
@endsection