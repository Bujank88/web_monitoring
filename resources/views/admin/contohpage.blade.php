@extends('master')
@section('title') Monitoring Creator Partner @endsection
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
<!-- Filter Dropdown Trigger -->
<div class="d-flex align-items-center mb-3" style="justify-content: flex-end;">
    <span style="font-weight: bold; font-size: 18px; margin-right: 10px;">Filter Data</span>
    <a href="#" id="filterDropdownToggle" style="color: #343a40; font-size: 22px;">
        <i class="fas fa-filter"></i>
    </a>
</div>

<!-- Filter Dropdown Content -->
<div id="filterDropdownContent" style="display: none; margin-bottom: 20px;">
    <div class="card card-primary">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="area">Area</label>
                    <select id="area" name="area" class="form-control select2"
                        style="background-color: #313131; color: white;">
                        <option value="">Semua</option>
                        <option value="Area 1">Area 1</option>
                        <option value="Area 2">Area 2</option>
                        <option value="Area 3">Area 3</option>
                        <option value="Area 4">Area 4</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="region">Regional</label>
                    <select id="region" name="region" class="form-control select2"
                        style="background-color: #313131; color: white;">
                        <option value="">Semua</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cluster">Cluster</label>
                    <select class="form-control select2" style="background-color: #313131; color: white;" id="cluster"
                        name="cluster">
                        <option value="">Semua Cluster</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="grapari">Grapari</label>
                    <select class="form-control select2" style="background-color: #313131; color: white;" id="grapari"
                        name="grapari">
                        <option value="">Semua Grapari</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="far fa-envelope"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Messages</span>
                <span class="info-box-number">1,410</span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="far fa-flag"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Bookmarks</span>
                <span class="info-box-number">410</span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="far fa-copy"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Uploads</span>
                <span class="info-box-number">13,648</span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="far fa-star"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Likes</span>
                <span class="info-box-number">93,139</span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
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
                            <th class="text-center">Area</th>
                            <th class="text-center">Regional</th>
                            <th class="text-center">Jenis KOL</th>
                            <th class="text-center">Nama KOL</th>
                            <th class="text-center">Email KOL</th>
                            <th class="text-center">No HP KOL</th>
                            <th class="text-center">Tanggal<br>Isi Form</th>

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

                url: "{{ route('creator_partner_data') }}",

                type: "GET",

                dataSrc: function(json) {

                    console.log("Response dari server:", json); // Debugging response dari server

                    return json.data || []; // Pastikan tidak ada undefined/null

                }

            },



            columns: [{

                    data: 'area',

                    name: 'area',

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'regional',

                    name: 'regional',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'jenis_kol',

                    name: 'jenis_kol',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'nama_kol',

                    name: 'nama_kol',

                    orderable: true,

                    render: data => `<div style="text-align: left;">${data}</div>`

                },
                {

                    data: 'email_kol',

                    name: 'email_kol',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'no_hp_kol',

                    name: 'no_hp_kol',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {
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
                        return `<div style="text-align: center;">${day} ${month} ${year} ${hours}:${minutes}</div>`;
                    }
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
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Toggle filter dropdown
    $(document).ready(function() {
        $('#filterDropdownToggle').on('click', function(e) {
            e.preventDefault();
            $('#filterDropdownContent').slideToggle(200);
        });
    });
</script>

@endsection