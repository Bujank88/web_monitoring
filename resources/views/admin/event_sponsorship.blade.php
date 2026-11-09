@extends('master')
@section('title') Monitoring Event Sponsorship @endsection
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
                    <label for="tanggal">Pilih Bulan:</label>
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

            <h3 style="font-weight: bold" class="card-title">DATA GFORM EVENT SPONSORSHIP</h3>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm w-100 table-striped table-hover"
                    id="datatablenya" style="font-size: 13px;">

                    <thead>

                        <tr>
                            <th class="text-center">Tanggal<br>Isi Form</th>
                            <th class="text-center">Area</th>
                            <th class="text-center">Regional</th>
                            <th class="text-center">Nama Event</th>
                            <th class="text-center">Lokasi Event</th>
                            <th class="text-center">Tanggal Event</th>
                            <th class="text-center">Nama PIC Event</th>
                            <th class="text-center">No. Telp<br>PIC Event</th>
                            <th class="text-center">Nama PIC Tsel</th>
                            <th class="text-center">No. Telp<br>PIC Tsel</th>
                            <th class="text-center">Link <br>Proposal </th>

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

                url: "{{ route('event_sponsorship_data') }}",

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

                    data: 'area',

                    name: 'nama',

                    render: data => `<div style="text-align: left;">${data}</div>`

                },

                {

                    data: 'regional',

                    name: 'regional',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'nama_event',

                    name: 'nama_event',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'lokasi_event',

                    name: 'lokasi_event',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },
                {

                    data: 'tanggal_event',

                    name: 'tanggal_event',

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

                    data: 'pic_event',

                    name: 'pic_event',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'telp_pic_event',

                    name: 'telp_pic_event',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'pic_tsel',

                    name: 'pic_tsel',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },

                {

                    data: 'telp_pic_tsel',

                    name: 'telp_pic_tsel',

                    orderable: true,

                    render: data => `<div style="text-align: center;">${data}</div>`

                },
                {

                    data: 'upload_proposal',

                    name: 'upload_proposal',

                    orderable: true,

                    render: function(data, type, row) {

                        if (data) {

                            return `<a href="${data}" target="_blank" style="color: blue; text-decoration: underline;">Lihat Proposal</a>`;

                        } else {

                            return '';

                        }

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
    $(document).ready(function() {
        $('#filterDropdownToggle').on('click', function(e) {
            e.preventDefault();
            $('#filterDropdownContent').toggle();
        });
    });
</script>
@endsection