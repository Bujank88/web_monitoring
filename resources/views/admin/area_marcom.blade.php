@extends('master')
@section('title') Area Markom @endsection
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

<div class="spinner-wrapper">

    <div id="loading-overlay" style="display: none;">



        <div id="loading-message"
            style="font-size: 24px; color: white; text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            Loading, please wait...

        </div>

    </div>

    <div class="card card-danger">

        <div class="card-header">

            <h3 style="font-weight: bold" class="card-title">DATA AREA MARKOM KOL</h3>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm w-100 table-striped table-hover"
                    id="datatablenya" style="font-size: 13px;">

                    <thead>

                        <tr>
                            <th class="text-center">Area</th>
                            <th class="text-center">Jumlah KOL <br> As Buzzer</th>
                            <th class="text-center">Jumlah KOL <br> As Influencer</th>
                            <th class="text-center">Total <br> Rekruter</th>
                            <th class="text-center">Total <br> Top Up</th>
                            <th class="text-center">Remarks</th>
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
            serverSide: false, // karena kita mengirim semua data dari server sebagai JSON
            ordering: false,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            ajax: {
                url: "{{ route('area_marcom_kol_data') }}",
                type: "GET",
                dataSrc: function(json) {
                    // support dua kemungkinan: { data: [...] } atau [...]
                    return (json && json.data) ? json.data : json || [];
                }
            },
            columns: [{
                    data: 'area',
                    name: 'area',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'jumlah_buzzer',
                    name: 'jumlah_buzzer',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'jumlah_influencer',
                    name: 'jumlah_influencer',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'total_rekruter',
                    name: 'total_rekruter',
                    render: data => `<div style="text-align:center;">${data||0}</div>`
                },
                {
                    data: 'total_topup',
                    name: 'total_topup',
                    render: function(data) {
                        if (!data) return '';
                        // Format as currency: Rp.xxx.xxx
                        let num = parseInt(data.toString().replace(/\D/g, '')) || 0;
                        return `<div style="text-align:center;">Rp.${num.toLocaleString('id-ID')}</div>`;
                    }
                },
                {
                    data: 'remarks',
                    name: 'remarks',
                    render: function(data) {
                        if (!data || data === 0) {
                            return `
                <div style="text-align:center;">
                    <span style="background:#f1f3f5; color:#495057; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:500; box-shadow:0 2px 8px rgba(0,0,0,0.05);">-</span>
                </div>`;
                        }

                        // bikin deretan bintang emas
                        let stars = '';
                        for (let i = 0; i < data; i++) {
                            stars += `<i class="fa fa-star" style="color:#f9c80e; font-size:16px; margin:0 2px; text-shadow:0 1px 2px rgba(0,0,0,0.2);"></i>`;
                        }

                        let html = `<span style="
            display:inline-flex; 
            align-items:center; 
            background:#fff;
            padding:6px 12px; 
            border-radius:25px; 
            font-weight:600; 
            font-size:14px; 
            border:1px solid #dee2e6;
            box-shadow:0 2px 6px rgba(0,0,0,0.05);">
            ${stars}
        </span>`;

                        return `<div style="text-align:center;">${html}</div>`;
                    }
                }




            ],
            preDrawCallback: function() {
                $('#loading-overlay').show();
            },
            drawCallback: function() {
                $('#loading-overlay').hide();
            }
        });

        $('#datatablenya').on('error.dt', function(e, settings, techNote, message) {
            console.error("DataTables Error:", message);
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


@endsection