@extends('master')
@section('title') Rekruter KOL as a Influencer @endsection
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

            <h3 style="font-weight: bold" class="card-title">DATA NEW ACCOUNT REGISTER FROM KOL as a Influencer</h3>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm w-100 table-striped table-hover"
                    id="datatablenya" style="font-size: 13px;">

                    <thead>

                        <tr>
                            <th class="text-center">Tanggal<br>Isi Form</th>
                            <th class="text-center">Referral<br> Code</th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">No.HP</th>
                            <th class="text-center">Nilai<br>Min.Top Up</th>
                            <th class="text-center">Saldo Top UP</th>
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
                url: "{{ route('rekruter_kol_influencer_data') }}",
                type: "GET",
                dataSrc: function(json) {
                    // support dua kemungkinan: { data: [...] } atau [...]
                    return (json && json.data) ? json.data : json || [];
                }
            },
            columns: [{
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data) {
                        if (!data) return '';
                        const dateObj = new Date(data);
                        if (isNaN(dateObj)) return data;
                        const day = String(dateObj.getDate()).padStart(2, '0');
                        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                        const month = monthNames[dateObj.getMonth()];
                        const year = dateObj.getFullYear();
                        return `<div style="text-align:center;">${day} ${month} ${year}</div>`;
                    }
                },
                {
                    data: 'referral_code',
                    name: 'referral_code',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'nama',
                    name: 'nama',
                    render: data => `<div style="text-align:left;">${data||''}</div>`
                },
                {
                    data: 'email',
                    name: 'email',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'no_hp',
                    name: 'no_hp',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'nilai_min_topup',
                    name: 'nilai_min_topup',
                    render: function(data) {
                        if (!data) return '';
                        // Format as currency: Rp.xxx.xxx
                        let num = parseInt(data.toString().replace(/\D/g, '')) || 0;
                        return `<div style="text-align:center;">Rp.${num.toLocaleString('id-ID')}</div>`;
                    }
                },
                {
                    data: 'jumlah_top_up',
                    name: 'jumlah_top_up',
                    render: function(data) {
                        if (!data) return '';
                        let num = parseInt(data.toString().replace(/\D/g, '')) || 0;
                        return `<div style="text-align:center;">Rp.${num.toLocaleString('id-ID')}</div>`;
                    }
                },
                {
                    data: 'remarks',
                    name: 'remarks',
                    render: function(data) {
                        if (!data) return '<div style="text-align:center;"><span style="background:#adb5bd; color:#fff; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:500; box-shadow:0 2px 8px rgba(0,0,0,0.08);">-</span></div>';
                        let html = '';
                        let text = data.charAt(0).toUpperCase() + data.slice(1).toLowerCase();
                        if (data.toLowerCase() === 'eligible') {
                            html = `<span style="display:inline-flex; align-items:center; background:linear-gradient(90deg,#43e97b 0%,#38f9d7 100%); color:#fff; padding:5px 16px; border-radius:20px; font-weight:600; font-size:14px; box-shadow:0 2px 8px rgba(67,233,123,0.15);">
                                        <i class="fa fa-check-circle" style="margin-right:7px; font-size:16px; color:#fff;"></i> Eligible
                                    </span>`;
                        } else if (data.toLowerCase() === 'not eligible') {
                            html = `<span style="display:inline-flex; align-items:center; background:linear-gradient(90deg,#ff5858 0%,#f09819 100%); color:#fff; padding:5px 16px; border-radius:20px; font-weight:600; font-size:14px; box-shadow:0 2px 8px rgba(255,88,88,0.12);">
                                        <i class="fa fa-times-circle" style="margin-right:7px; font-size:16px; color:#fff;"></i> Not Eligible
                                    </span>`;
                        } else {
                            html = `<span style="display:inline-flex; align-items:center; background:linear-gradient(90deg,#6a82fb 0%,#fc5c7d 100%); color:#fff; padding:5px 16px; border-radius:20px; font-weight:500; font-size:13px; box-shadow:0 2px 8px rgba(106,130,251,0.10);">
                                        <i class="fa fa-info-circle" style="margin-right:7px; font-size:15px; color:#fff;"></i> ${text}
                                    </span>`;
                        }
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